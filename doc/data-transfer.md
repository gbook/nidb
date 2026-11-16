# NiDB → NiDB data transfer: overview

Status: **design / living document**. Started 2026-10-29. Split into an overview and three parts on 2026-11-13 (T23). This overview holds the goal, the decisions, and the shared concepts; the details are in the parts:

| Doc | Covers |
|---|---|
| **Overview:** this doc | Goal, decisions (T1–T34), concepts, open questions |
| [Part 1: `data-transfer-1-content.md`](data-transfer-1-content.md) | **Data export/import.** What data and metadata survive a round trip from instance A to instance B, the gaps in NiDB's squirrel export and import, proposed squirrel format changes, and the ID trail carried in the package. Replaces `doc/nidb-export.md` (T24). |
| [Part 2: `data-transfer-2-transport.md`](data-transfer-2-transport.md) | **Transfer of data.** How packages get from A to B: peers and keys, what the user sees, the transfer flow and states, parts and tus, locking, security, database tables. |
| [Part 3: `data-transfer-3-conflicts.md`](data-transfer-3-conflicts.md) | **Resolving conflicts at the destination.** Identity and origin matching, collisions, changed data, and the procedure for each object type. |

---

## 1. Goal

Let a user send any data stored in one NiDB instance (the **sender**) to another NiDB instance (the **receiver**), not just DICOM. That includes subjects, enrollments, studies, all series modalities (MR, EEG, ET, etc.) with their files, observations, interventions, experiments, pipelines, analyses, and data dictionaries. The receiver should end up with an equivalent copy, linked back to where it came from, so a later transfer of the same data sends only what's new.

The transfer has three parts:

1. **Export** the selected data on the sender as one or more squirrel packages.
2. **Transmit** the packages from a new sender-side module (`moduleTransfer.cpp/h`) to the receiver, which accepts them through `api2.php`.
3. **Archive** the received packages on the receiver.

### 1.1 Starting point

| Piece | Where | Notes |
|---|---|---|
| Current transfer | `moduleExport::ExportToRemoteNiDB()`, `exports.remotenidb_*` | Sends DICOM only; the receiver archives it as a normal DICOM import. **Not reused.** |
| Remote connections | `remoteconnections.php`, `remotenidbconnection.{h,cpp}` | Stores server, username, password, instance/project/site per connection. Tied to the old transfer. |
| Packages | `packages.php`, `packages` + `package_*` tables, `archiveIO::WriteExportPackage()` | User-built selection of subjects/studies/series/observations/etc., written as squirrel. Its selection code is reused for the transfer page (T4). |
| squirrel export | `archiveIO::WriteSquirrel()` / `WriteExportPackage()` | Has known coverage gaps, listed in Part 1. These must be fixed for the transfer to be complete. |
| squirrel import | `archiveIO::ArchiveSquirrelPackage()` | Archives a squirrel package on upload. Only archives NIfTI MR series and has no notion of where the data came from (Part 1 §6). |
| Resumable upload | `tus_functions.php`, `tusupload.php`, `tuscontext.php` (`doc/tus.md`) | tus 1.0.0 server. Today it only receives browser uploads. |
| API | `api2.php` (`doc/api2-client-guide.md`) | Username + API key authentication, session, transactions. |

### 1.2 Decisions

| # | Decision | Date | Consequence |
|---|---|---|---|
| T1 | **No reuse of the existing transfer code** (`ExportToRemoteNiDB()` and its helpers). | 2026-10-29 | New module `moduleTransfer`, new API actions. The old path can be retired once this works. |
| T2 | **squirrel is the transfer format.** | 2026-10-29 | Any gap in squirrel export/import is a gap in transfer, so the gaps in Part 1 become prerequisites. |
| T3 | **The receiver accepts data through `api2.php`, and large files go over tus.** | 2026-10-29 | The sender needs a tus *client* in C++. |
| T4 | **The transfer selection pages reuse code from `packages.php`.** | 2026-10-29 | Packages already handle a mixed selection across all object types. Only the transfer code is off-limits (T1), not the selection UI. |
| T5 | **Every NiDB install gets a new, stable instance ID** (a UUID generated once). NiDB doesn't have one today. | 2026-10-29 | Needed for origin records (Part 3 §2). Created at install, or on upgrade for existing installs, and never changed. |
| T6 | **The user's own API key on the receiver is the only authorization** (T18). No separate trusted-sender list for now. | 2026-10-29 | An admin on the receiver stops a user from sending by revoking that user's key, and can pause or block individual transfers (T19). |
| T7 | **PHI is a fixed set of fields, with one choice per transfer: send as-is or anonymize.** The fields are name, DOB, alternate IDs, and the DICOM patient fields. | 2026-10-29 | Simplest for the user; nothing is configured per project. Anonymized data can't be matched to the receiver's existing data by those fields (Part 2 §2.2, Part 3 §2). |
| T8 | **Received parts are archived automatically**, with a permanent log of every object transferred. A transfer only stops for a person when there is a **collision** (Part 3 §3). | 2026-10-29 | No receiver-side review step through the `upload_*` staging tables. |
| T9 | **Deferred:** bandwidth limits, transfer time windows, and scheduled/repeating transfers ("sync this project every night"). | 2026-10-29 | Not in v1. The origin mapping keeps scheduled syncs possible later. |
| T10 | **Peers are one-way.** A peer record on the sender holds the receiver's URL, and only allows sending to that receiver. Two-way transfer is two one-way peers, one configured on each side. | 2026-10-29 | Each side stores only the key it uses and controls what it lets in. Origin records must still carry the original source, so data that goes A → B → A is recognized on A and not duplicated (Part 3 §2). |
| T11 | **Transferred data is not versioned.** Conflicts are shown to the user; once the user approves a resolution and it is archived on the receiver, it's final. Data a resend replaces or removes is deleted with `SafeDeletePath()`, not kept. | 2026-10-29 | There's no undo or restore. The transfer log still records what was replaced (old field values, removed/replaced file names, sizes, checksums) for the audit trail, but not the data itself. The review page must make clear that approving a resend is permanent. |
| T12 | **Possible matches (C1) are searched across all projects on the receiver**, not just those the receiving user can access. | 2026-10-29 | Finds more duplicates, since a subject is global and its enrollments are per project. The compare response says only that a match exists, with its receiver UID; it doesn't show which project it's in or any of its data. Merging into a subject from another project adds an enrollment in the destination project; that subject's other enrollments are not changed. |
| T13 | **Parts are at most 5 GB**, set by the system config value `[transferpartsize]` (default 5 GB). Admin-only; the transfer page doesn't offer it. | 2026-10-29 | Each side needs only ~10 GB of temporary space per running transfer, and a failed part redoes at most 5 GB. A 400 GB transfer is ~80 parts, which is fine since per-part overhead is small. |
| T14 | **A transfer is one object that moves through states from submission to completion, and all of its rules run in C++ (`moduleTransfer`) on both sides.** The user submits the transfer; the module compares it with the receiver as a queued step. If there are conflicts, the transfer waits for the user to resolve them and approve; if there are none, it sends automatically. | 2026-10-29 | No separate compare page or synchronous compare call. PHP pages only create the transfer, show its state, and record the user's decisions. On the receiver, `api2.php` only stores what it's sent and reports state; matching, collision checks, and archiving are done by `moduleTransfer` there. The same C++ matching code is used for the compare and at archive time. |
| T15 | **Locks:** the sender locks a transfer's objects when `moduleTransfer` picks up the submitted transfer; the receiver locks destination objects only when sending starts. | 2026-10-29 | The sender's data can't change while a transfer waits in Needs review, and a transfer waiting for review doesn't hold the receiver's data (Part 2 §5). |
| T16 | **A stale comparison is re-checked on approval.** If it is older than a set limit when the user approves, the module compares again first; new conflicts send the transfer back to Needs review. | 2026-10-29 | The archive-time re-check (Part 3 §3) remains the final safety net. |
| T17 | **The transfer is managed by the sender.** All decisions (resolve, approve, pause, cancel) are made on the sender by the user who owns the transfer. The user must have access to both NiDB servers to start a transfer, so they can watch it from either end; the receiver's incoming view is read-only. | 2026-10-29 | No decision UI on the receiver. |
| T18 | **Per-user API keys.** An admin defines the peer (name, URL); each user who sends to it attaches their own API key from the receiver. Every transfer runs as that user on the receiver. | 2026-10-29 | The receiver's permissions and audit log apply to the person actually sending. Enforces T17's "access to both servers". A user without a working key for a peer can't send to it. |
| T19 | **Admin interface on both sides** to view all transfers and pause or block them. An admin can act on any transfer on their own server: the sender's admin on outgoing transfers, the receiver's admin on incoming ones. | 2026-10-29 | The one exception to T17: admins can stop a transfer from either end, and a receiver admin's block takes effect on the receiver even if the sender can't be told. **Block is final**: a blocked transfer can't be resumed or retried. Admins don't resolve conflicts or approve transfers (Part 2 §2.5). |
| T20 | **Lock checks in the rest of NiDB are deferred** until the transfer process works end to end. At first, only the transfer (and later tier migration) code takes, refreshes, releases, and respects `object_locks`. The other pages and modules that modify data get their `GetObjectLock()` / `GetLock()` checks afterwards (Part 2 §7.6). | 2026-10-29 | Until then a lock only keeps transfers from colliding with each other. A user can still change locked data from other pages: on the sender that makes a part fail its export or verify (an error, not damage to the sender's data); on the receiver it can leave an archived part inconsistent. Fine for development and testing; the checks must be in place before the feature is released. |
| T21 | **The ID trail travels inside the squirrel package**: a package-level source system UUID, plus a `Provenance` table with each object's origin and source IDs (Part 1 §7.1). It isn't only in the transfer manifest. | 2026-11-13 | A package carried by any route (transfer, copied by hand, web upload) can be matched on the receiver. Needs a squirrel format change, to be agreed with the squirrel maintainer. |
| T22 | **Round-trip scope:** subjects, enrollments and alt IDs, studies, series of all modalities (data and behavioral files), observations, interventions, analyses (metadata only, T27), pipelines, mini-pipelines, experiments, data dictionaries/instruments, **ratings**, and **diagnoses (ICD-10)**. | 2026-11-13 | QA/QC, analysis results, families, tags, groups, vitals, checklists and history are out of scope for now (Part 1 §8). Ratings and diagnoses need new squirrel objects (Part 1 F11, F12). |
| T23 | **The design is split into an overview and three parts**: content (export/import), transport, and conflicts. | 2026-11-13 | Section references across documents are written as "Part N §x". |
| T24 | **Part 1 replaces `doc/nidb-export.md`.** Its findings were re-checked and folded in. | 2026-11-13 | `nidb-export.md` is reduced to a pointer. |
| T25 | **Projects are instance-specific and aren't carried over.** Every object in a transfer goes into the one destination project the user picks on the receiver. | 2026-11-13 | squirrel's new `Enrollment` object has no project identity (Part 1 §7.2). A subject's enrollments from several source projects go into one destination enrollment (Part 3 §5.1). |
| T26 | **squirrel gets a `Subject.Name` field** (PHI; blank when anonymized). Contact information (phone, email, can-contact) stays out. | 2026-11-13 | "Send as-is" (T7) can send the name. A squirrel format change (Part 1 F3). |
| T27 | **Analysis output files and `analysis_results` can be exported but aren't imported**, for now. They live outside the archive, in a shared environment, and can be 200 GB+ per analysis. | 2026-11-13 | The receiver imports analysis metadata only. A transfer always sends analyses as metadata only (Part 2 §2.2). User packages may include the files as an option (Part 1 E13). May be revisited later. |
| T28 | **Identical possible matches are linked automatically** for observations, interventions, instruments, pipelines, mini-pipelines, experiments, ratings and diagnoses. Subjects and studies always ask. | 2026-11-13 | Far fewer conflicts for the user. Each link is recorded as an origin link and logged (Part 3 §5). |
| T29 | **The ID trail keeps every hop**, not just the origin and the last sender: a JSON list of every system the object passed through, with its ID, label and arrival date there. | 2026-11-13 | `Provenance.Hops` in squirrel (Part 1 §7.1); `object_origins.origin_hops` and `transfer_incomingobjects.object_hops` on the receiver (Part 2 §7.1). Matching still uses only the origin. |
| T30 | **NiDB fields that squirrel doesn't model go into one `Metadata` JSON column** on Subject, Study, Series and Analysis, not into explicit squirrel columns. | 2026-11-13 | No squirrel format change each time NiDB adds a column (Part 1 §7.3). |
| T31 | **Bulk decisions** for observations and interventions, per instrument and per subject. | 2026-11-13 | Part 3 §6.3. |
| T32 | **A DOB or sex mismatch on a subject merge warns and allows the merge, and proposes a fix**: keep the receiver's value, change it, or flag for review. | 2026-11-13 | Part 3 §5.2. |
| T33 | **A rater who doesn't exist on the receiver is kept by name in `rating_notes`**; the rating is attributed to the transferring user. | 2026-11-13 | No schema change (Part 3 §5.13). |
| T34 | **A new, shared conflict resolution interface** is used for transfers, web-uploaded packages, and packages dropped in the import directory. Uploads reuse the incoming-transfer tables and matching code. | 2026-11-13 | Part 3 §6. For uploads, decisions are made on the receiver, an exception to T17. Adds `incoming_source` and `upload_id` to `transfers_incoming` (Part 2 §7.1). |

---

## 2. Concepts

- **Sender / receiver:** the two NiDB instances. The same instance can be both, for different transfers.
- **Peer:** a receiver that the sender's admin has registered: name and URL. Peers are one-way (T10): a peer only allows sending to that receiver. Replaces the old remote connection for this feature.
- **Peer key:** one user's own API key on a peer, stored on the sender for that user only (T18). Every transfer uses its owner's peer key.
- **Transfer:** one request by one user to send one selection of data to one peer and destination project. It has a single ID on each side, and the two IDs are linked.
- **Manifest:** the list of objects in a transfer, with their identifiers, sizes and fingerprints, but no data. It is sent to the receiver first so the receiver can say what it already has.
- **Part:** a transfer is split into parts of at most 5 GB (T13), each a separate squirrel package. Parts are exported, sent, verified, and archived independently, so a failure only repeats one part.
- **Origin:** every object the receiver creates from a transfer remembers the sender's instance ID and the object's ID/UID on the sender. This is what lets later transfers match up data and what makes UID collisions harmless (Part 3 §2).
- **Conflict:** an object the receiver can't handle without a person deciding (a *collision*, Part 3 §3). A transfer with conflicts waits in **Needs review** until the user resolves them.
- **Lock:** a database record saying "this object is part of an active transfer". It stops changes that would break the transfer, on both sides (Part 2 §5).
- **Round trip:** export from A, import into B, and B holds an equivalent copy that can be traced back to A (Part 1 §1).
- **ID trail:** the origin system and object ID of every object, carried in the squirrel package (`Provenance`, Part 1 §7.1) and stored on the receiver in `object_origins` (Part 2 §7.1). It's how a later transfer, from any direction, recognizes data it has seen before.

---

## 3. Open questions

None at the moment. Each part keeps its own list of questions, with the answers given so far: Part 1 §11, Part 3 §7.
