# NiDB → NiDB data transfer — design

Status: **design / living document**. Started 2026-10-29. This version covers how the user interacts with a transfer and the overall flow. Technical details (tables, API actions, file formats, module internals) are left as `TBD` and will be filled in later.

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
| squirrel export | `archiveIO::WriteSquirrel()` / `WriteExportPackage()` | Has known coverage gaps, listed in `doc/nidb-export.md`. These must be fixed for the transfer to be complete. |
| squirrel import | `archiveIO::ArchiveSquirrelPackage()` | Archives a squirrel package on upload. Has no notion of where the data came from. |
| Resumable upload | `tus_functions.php`, `tusupload.php`, `tuscontext.php` (`doc/tus.md`) | tus 1.0.0 server. Today it only receives browser uploads. |
| API | `api2.php` (`doc/api2-client-guide.md`) | Username + API key authentication, session, transactions. |

### 1.2 Decisions

| # | Decision | Date | Consequence |
|---|---|---|---|
| T1 | **No reuse of the existing transfer code** (`ExportToRemoteNiDB()` and its helpers). | 2026-10-29 | New module `moduleTransfer`, new API actions. The old path can be retired once this works. |
| T2 | **squirrel is the transfer format.** | 2026-10-29 | Any gap in squirrel export/import is a gap in transfer, so `doc/nidb-export.md` items become prerequisites. |
| T3 | **The receiver accepts data through `api2.php`, and large files go over tus.** | 2026-10-29 | The sender needs a tus *client* in C++. |
| T4 | **The transfer selection pages reuse code from `packages.php`.** | 2026-10-29 | Packages already handle a mixed selection across all object types. Only the transfer code is off-limits (T1), not the selection UI. |
| T5 | **Every NiDB install gets a new, stable instance ID** (a UUID generated once). NiDB doesn't have one today. | 2026-10-29 | Needed for origin records (§5.1). Created at install, or on upgrade for existing installs, and never changed. |
| T6 | **The user's own API key on the receiver is the only authorization** (T18). No separate trusted-sender list for now. | 2026-10-29 | An admin on the receiver stops a user from sending by revoking that user's key, and can pause or block individual transfers (T19). |
| T7 | **PHI is a fixed set of fields, with one choice per transfer: send as-is or anonymize.** The fields are name, DOB, alternate IDs, and the DICOM patient fields. | 2026-10-29 | Simplest for the user; nothing is configured per project. Anonymized data can't be matched to the receiver's existing data by those fields (§3.2, §5.1). |
| T8 | **Received parts are archived automatically**, with a permanent log of every object transferred. A transfer only stops for a person when there is a **collision** (§5.5). | 2026-10-29 | No receiver-side review step through the `upload_*` staging tables. |
| T9 | **Deferred:** bandwidth limits, transfer time windows, and scheduled/repeating transfers ("sync this project every night"). | 2026-10-29 | Not in v1. The origin mapping keeps scheduled syncs possible later. |
| T10 | **Peers are one-way.** A peer record on the sender holds the receiver's URL, and only allows sending to that receiver. Two-way transfer is two one-way peers, one configured on each side. | 2026-10-29 | Each side stores only the key it uses and controls what it lets in. Origin records must still carry the original source, so data that goes A → B → A is recognized on A and not duplicated (§5.1). |
| T11 | **Transferred data is not versioned.** Conflicts are shown to the user; once the user approves a resolution and it is archived on the receiver, it's final. Data a resend replaces or removes is deleted with `SafeDeletePath()`, not kept. | 2026-10-29 | There's no undo or restore. The transfer log still records what was replaced (old field values, removed/replaced file names, sizes, checksums) for the audit trail, but not the data itself. The review page must make clear that approving a resend is permanent. |
| T12 | **Possible matches (C1) are searched across all projects on the receiver**, not just those the receiving user can access. | 2026-10-29 | Finds more duplicates, since a subject is global and its enrollments are per project. The compare response says only that a match exists, with its receiver UID; it doesn't show which project it's in or any of its data. Merging into a subject from another project adds an enrollment in the destination project; that subject's other enrollments are not changed. |
| T13 | **Parts are at most 5 GB**, set by the system config value `[transferpartsize]` (default 5 GB). Admin-only; the transfer page doesn't offer it. | 2026-10-29 | Each side needs only ~10 GB of temporary space per running transfer, and a failed part redoes at most 5 GB. A 400 GB transfer is ~80 parts, which is fine since per-part overhead is small. |
| T14 | **A transfer is one object that moves through states from submission to completion, and all of its rules run in C++ (`moduleTransfer`) on both sides.** The user submits the transfer; the module compares it with the receiver as a queued step. If there are conflicts, the transfer waits for the user to resolve them and approve; if there are none, it sends automatically. | 2026-10-29 | No separate compare page or synchronous compare call. PHP pages only create the transfer, show its state, and record the user's decisions. On the receiver, `api2.php` only stores what it's sent and reports state; matching, collision checks, and archiving are done by `moduleTransfer` there. The same C++ matching code is used for the compare and at archive time. |
| T15 | **Locks:** the sender locks a transfer's objects when `moduleTransfer` picks up the submitted transfer; the receiver locks destination objects only when sending starts. | 2026-10-29 | The sender's data can't change while a transfer waits in Needs review, and a transfer waiting for review doesn't hold the receiver's data (§5.4). |
| T16 | **A stale comparison is re-checked on approval.** If it is older than a set limit when the user approves, the module compares again first; new conflicts send the transfer back to Needs review. | 2026-10-29 | The archive-time re-check (§5.5) remains the final safety net. |
| T17 | **The transfer is managed by the sender.** All decisions (resolve, approve, pause, cancel) are made on the sender by the user who owns the transfer. The user must have access to both NiDB servers to start a transfer, so they can watch it from either end; the receiver's incoming view is read-only. | 2026-10-29 | No decision UI on the receiver. |
| T18 | **Per-user API keys.** An admin defines the peer (name, URL); each user who sends to it attaches their own API key from the receiver. Every transfer runs as that user on the receiver. | 2026-10-29 | The receiver's permissions and audit log apply to the person actually sending. Enforces T17's "access to both servers". A user without a working key for a peer can't send to it. |
| T19 | **Admin interface on both sides** to view all transfers and pause or block them. An admin can act on any transfer on their own server: the sender's admin on outgoing transfers, the receiver's admin on incoming ones. | 2026-10-29 | The one exception to T17: admins can stop a transfer from either end, and a receiver admin's block takes effect on the receiver even if the sender can't be told. **Block is final**: a blocked transfer can't be resumed or retried. Admins don't resolve conflicts or approve transfers (§3.5). |
| T20 | **Lock checks in the rest of NiDB are deferred** until the transfer process works end to end. At first, only the transfer (and later tier migration) code takes, refreshes, releases, and respects `object_locks`. The other pages and modules that modify data get their `GetObjectLock()` / `GetLock()` checks afterwards (§9.6). | 2026-10-29 | Until then a lock only keeps transfers from colliding with each other. A user can still change locked data from other pages: on the sender that makes a part fail its export or verify (an error, not damage to the sender's data); on the receiver it can leave an archived part inconsistent. Fine for development and testing; the checks must be in place before the feature is released. |

---

## 2. Concepts

- **Sender / receiver:** the two NiDB instances. The same instance can be both, for different transfers.
- **Peer:** a receiver that the sender's admin has registered: name and URL. Peers are one-way (T10): a peer only allows sending to that receiver. Replaces the old remote connection for this feature.
- **Peer key:** one user's own API key on a peer, stored on the sender for that user only (T18). Every transfer uses its owner's peer key.
- **Transfer:** one request by one user to send one selection of data to one peer and destination project. It has a single ID on each side, and the two IDs are linked.
- **Manifest:** the list of objects in a transfer, with their identifiers, sizes and fingerprints, but no data. It is sent to the receiver first so the receiver can say what it already has.
- **Part:** a transfer is split into parts of at most 5 GB (T13), each a separate squirrel package. Parts are exported, sent, verified, and archived independently, so a failure only repeats one part.
- **Origin:** every object the receiver creates from a transfer remembers the sender's instance ID and the object's ID/UID on the sender. This is what lets later transfers match up data and what makes UID collisions harmless (§5.1).
- **Conflict:** an object the receiver can't handle without a person deciding (a *collision*, §5.5). A transfer with conflicts waits in **Needs review** until the user resolves them.
- **Lock:** a database record saying "this object is part of an active transfer". It stops changes that would break the transfer, on both sides (§5.4).

---

## 3. What the user sees

### 3.1 One-time setup: peer and peer key

**Add a peer** (once per receiver, by an admin on the sender):

1. **Admin → Transfer peers → Add**: enter a name and the receiver URL. HTTPS is required; a plain `http://` URL is refused.
2. **Check**: the sender calls the receiver's `GetNidbInfo` and shows its name and NiDB version, so the admin knows the URL is right. No credentials are stored on the peer.

**Add a peer key** (once per user per peer, by the user):

1. On the **receiver**, the user creates an API key in their own account there. They need an account on the receiver with write access to the projects they'll send into (T17, T18).
2. On the **sender**, under **My transfer keys** (TBD where), the user picks the peer and enters their receiver username and API key.
3. **Test**: the sender authenticates as that user and shows the projects they can write to on the receiver.
4. Optionally pick a default destination project, and save. The key is stored for that user only; other users and admins can't see it or use it.

If a key stops working (revoked or expired on the receiver), the user is told when they submit or when a running transfer next authenticates, and the transfer waits for a new key.

There is no trusted-sender list on the receiver (T6). An admin there stops a user from sending by revoking that user's API key, or acts on individual transfers (§3.5).

### 3.2 Start a transfer: choose what to send

A **Send to another NiDB** action is available wherever the user can already select data: search results, a project's subject list, a subject page, a study page, and the package page. The selection code comes from `packages.php` (T4). All of them open the same transfer page with the selection filled in.

The page has two columns. The left is the selection tree (project → subject → study → series, with observations, interventions, analyses, and so on hanging off the subject/study they belong to). The right is the set of options.

```
Send to another NiDB                                         Transfer #T-0142
-------------------------------------------------------------------------------
Destination  [ UCHC NiDB  v ]  project [ ABCD-Hartford  v ]   (your key: ok, v2026.10)

Include       [x] Imaging (series files)     modalities [x]MR [x]EEG [x]ET [ ]Other
              [x] Observations   [x] Interventions   [x] Experiments
              [x] Analyses       ( ) metadata only   (o) metadata + files
              [x] Pipelines      [ ] Mini-pipelines  [x] Data dictionary
Patient info  (o) Send as-is    ( ) Anonymize (remove name, DOB, alternate IDs,
                                    and DICOM patient fields)

Selection: 38 subjects, 112 studies, 1,904 series, 6,211 observations

                                [ Submit transfer ]
```

The **Include** options narrow the selection; they don't add objects. **Submit transfer** creates the transfer and returns the user to its status page (§3.3). Nothing is sent yet.

**Patient info** is one choice per transfer (T7). The PHI fields are always the same: name, DOB, alternate IDs, and the DICOM patient fields. **Anonymize** removes them on the sender before the data leaves; DICOM files are changed in the exported copy only, never in the sender's archive. The page warns that anonymized data can't be matched to data the receiver already has from elsewhere, because those are the fields that matching uses. Repeat transfers from the same sender still match, because origin records (§5.1) don't use PHI.

### 3.3 The transfer status page

Every transfer has one status page, and it is the only page the user needs after submitting. It always shows the current state, the comparison summary once there is one, and whatever the user can do in that state. Transfers run in the background; the user can close the browser at any point.

| State | What the user sees | What the user can do |
|---|---|---|
| **Submitted** | Waiting for `moduleTransfer` to pick it up. | Cancel |
| **Comparing** | The manifest is being sent to the receiver and matched there. Progress as objects are checked. | Cancel |
| **Needs review** | The comparison found conflicts (§5.5). The summary and a review tree are shown. | Resolve each conflict, then **Approve and send**; or Cancel |
| **Sending** | Per-part progress (export, upload, verify, archive). | Pause, Cancel |
| **Paused** | Stopped after the current upload chunk. Shows who paused it (the user, or an admin on either side). | Resume, Cancel. If an admin paused it, only an admin on that side can resume. |
| **Blocked** | An admin on either side stopped the transfer, with a reason (§3.5). Parts already archived stay. | Nothing; it can't be resumed or retried |
| **Needs decision** | One part hit a conflict at archive time that no earlier decision covers. Other parts continue. | Resolve it; the part continues |
| **Complete** | The report (§3.4). | Download the mapping CSV |
| **Cancelled** / **Error** | What was archived before it stopped, and why it stopped. | For Error: Retry |

If the comparison finds **no conflicts**, the transfer goes straight from Comparing to Sending, with no action from the user. The comparison summary stays on the page either way, so the user can always see how much was already on the receiver.

**Comparison summary.** Each object gets a status from the receiver:

| Status | Meaning | Handled |
|---|---|---|
| **New** | The receiver doesn't have it. | Sent automatically |
| **Already there** | The receiver has it from an earlier transfer, and it matches. | Skipped automatically |
| **UID in use** | The sender's UID is already used on the receiver by an unrelated subject. | Sent automatically; the receiver assigns a new UID (§5.1) |
| **Can't send** | Permission, missing files on the sender, unsupported type, or the receiver refuses it. | Skipped automatically, with the reason |
| **Possible match** (C1) | The receiver has an object that looks like the same thing but didn't come from this sender. | Conflict: user decides |
| **Changed** (C2) | Sent before, but it has changed since (§5.2). | Conflict: user decides |
| **Origin gone** (C3) | Sent before, but the receiver's copy has since been deleted or merged. | Conflict: user decides |

```
Transfer #T-0142 -> UCHC NiDB / ABCD-Hartford     submitted by gbook 14:02
-------------------------------------------------------------------------------
State: NEEDS REVIEW - 14 conflicts                      compared 2026-10-29 14:04

                     objects       size
New                  1,377      412.6 GB     will be sent
Already there          512      140.2 GB     will be skipped
UID in use               1        —          receiver will assign a new UID
Can't send               3        —          [ details ]
Possible match           2        0.8 GB     2 to resolve
Changed                 11        3.1 GB     11 to resolve
Origin gone              1        0.2 GB     1 to resolve

Receiver free space: 2.1 TB      Estimated time at 85 MB/s: ~1 h 21 m

Show [ conflicts only v ]
  v S3301QRS   changed
      Study 3  MR   series 7  T1w     +2 files   ( ) resend ( ) send as new (o) skip
  v S7781KLM   possible match: receiver S1190PQR (same alternate ID)
                                                 ( ) merge  ( ) create new
  ...
                         [ Cancel transfer ]  [ Approve and send ] (13 unresolved)
```

The user can expand any subject to see per-study and per-series status, and filter the tree to just the conflicts. Possible matches have no default, so **Approve and send** stays disabled until each one has a decision. Changed and Origin-gone rows default to **skip**. Approving a resend is permanent (T11), and the page says so before the user approves.

A transfer can sit in Needs review for a while. When the user approves, if the comparison is older than a set limit, the module checks it again first (T16). Anything new goes back to Needs review; otherwise sending starts.

**While sending:**

```
Transfer #T-0142 -> UCHC NiDB / ABCD-Hartford     submitted by gbook 14:02
-------------------------------------------------------------------------------
State: SENDING     [###########---------------]  41%   171 of 412.6 GB   ~48 min left

Part     contents             size     export   send            verify   archive
  1      pipelines, dd, ...   0.1 GB   done     done            done     done
  2..34  S1234ABC ... S3301   164 GB   done     done            done     done
  35     S4410DEF studies 1-2 4.8 GB   done     [######--] 74%  -        -
  36     S4410DEF study 3     4.9 GB   running  -               -        -
  37..84 ...                           queued
                                             [ Pause ]  [ Cancel transfer ]
```

- **Pause** stops after the current upload chunk; **Resume** picks up at the last byte the receiver confirmed.
- **Cancel** stops everything that hasn't been archived. Parts already archived on the receiver stay there (they are complete and consistent on their own). Locks are released.
- Network drops and restarts of either side are retried automatically with backoff; the user only sees "waiting for receiver" until it comes back or a time limit is hit and the transfer errors.
- A **Transfers** list page (on both sides) shows all transfers with state, size, user, and peer, and highlights those in Needs review or Needs decision.

On the receiver, the receiving user and admins see the same transfer as an **incoming** transfer, with the same state, per-part progress, and a link to each archived subject. Decisions are made only on the sender, by the user who owns the transfer (T17). Since that user has access to both servers, they can watch from either end.

### 3.4 Result

When the last part is archived, both sides show a report:

- counts sent / skipped / failed, per object type;
- every object's sender ID → receiver ID mapping, including any UIDs the receiver changed;
- warnings (for example "UID S9876ABC was in use; archived as S5544TTT, with S9876ABC saved as an alternate ID");
- a downloadable CSV of the mapping.

The mapping is saved, so the next transfer of the same subjects is mostly "already there".

### 3.5 Admin interface

Both servers have an **Admin → Transfers** page (T19). The sender's admin sees all outgoing transfers; the receiver's admin sees all incoming ones. A server that both sends and receives shows both, in separate tabs.

```
Admin > Transfers                                   [ Outgoing ]  [ Incoming ]
-------------------------------------------------------------------------------
Filter: state [ active v ]  peer [ all v ]  user [ all v ]

 #       user     peer        project         state          size     progress
 T-0142  gbook    UCHC NiDB   ABCD-Hartford   sending        412 GB   41%    [ Pause ] [ Block ]
 T-0143  jsmith   UCHC NiDB   ABCD-Hartford   needs review   12 GB    -      [ Pause ] [ Block ]
 T-0139  akim     Yale NiDB   PING            paused (admin) 88 GB    77%    [ Resume ] [ Block ]
```

For each transfer the admin can see the same status page the user sees (§3.3), read-only, plus the full log. Admin actions:

| Action | What it does |
|---|---|
| **Pause** | Stops after the current upload chunk, like a user pause. The user can't resume it; only an admin on the same side can. Locks are kept. |
| **Resume** | Lifts an admin pause. |
| **Block** | Ends the transfer, with a required reason. Nothing more is exported, sent or archived. Parts already archived on the receiver stay (they are complete on their own). Locks on both sides are released. The user sees "Blocked by an admin on <server>: <reason>". A blocked transfer can't be resumed or retried; the user would have to submit a new one. |

A receiver admin's pause or block reaches the sender through `api2.php`: the receiver refuses further parts for that transfer and reports its state, and the sender's module shows it. If the sender can't be told (the receiver can't contact it), the receiver still refuses the parts, so a block always takes effect on the receiver.

Admins don't resolve conflicts or approve transfers; that stays with the owner (T17). To stop one user from sending at all, a receiver admin revokes that user's API key; a sender admin can remove the user's peer key or the peer.

Every admin action, with who did it and why, is written to the transfer log on both sides.

---

## 4. Overall flow

```
SENDER                                          RECEIVER
(transfer.php + moduleTransfer)                 (api2.php + moduleTransfer)
-------------------------------                 ---------------------------
 1. User selects data, peer, project,
    options; clicks Submit           [Submitted]
 2. Module picks it up; locks source
    objects; builds manifest         [Comparing]
 3. Send manifest  ---- open transfer ----->   4. Store manifest
                                                5. Module matches each object
                                                   (§5.1), checks collisions
                                                   (§5.5), reports free space
 6. Poll  <------------ statuses -----------
 7. Conflicts?
      yes -> [Needs review]: user resolves,
             approves; re-compare if stale
      no  -> continue
 8. Send decisions  ----------------------->   9. Store identity decisions;
                                       [Sending]  lock destination objects;
                                                   reserve disk space
10. For each part:
    a. Export part as squirrel (staging)
    b. Checksum package
    c. Upload over tus  ======= bytes =====>   d. Receive into transfer dir
    e. Finalize part  ---------------------->   f. Verify size + checksum
    g. Delete local staging copy                h. Module re-checks the part's
                                                   objects (same rules as 5);
                                                   new conflict -> part waits
                                                   [Needs decision]
                                                i. Archive part (with origin
                                                   info + identity decisions)
    j. Poll part status  <-- archived + map --
11. All parts archived  -- close transfer -->  12. Release destination locks
13. Release source locks, save mapping,
    show report                      [Complete]
```

### 4.1 States

Transfer: `submitted → comparing → (needs review) → sending → (paused / needs decision) → complete`, plus `cancelled`, `blocked` and `error`. These are the states the user sees in §3.3. Every state change is made by `moduleTransfer`, except the ones that come from the user (approve, pause, resume, cancel, resolve, and admin pause/resume/block), which the page records and the module acts on.

Part: `queued → exporting → exported → uploading → uploaded → verifying → archiving → archived`, plus `needs decision` and `error`. Each part is restartable from its last completed step.

### 4.2 Who does what

| Step | Where it runs | Notes |
|---|---|---|
| Selection, status page, review decisions | PHP on the sender (`transfer.php`, TBD) | Creates the transfer, shows its state, records user decisions. No transfer logic (T14). |
| Compare, export, upload, polling | `moduleTransfer` on the sender | One transfer per worker; parts within a transfer run in sequence, so staging space is bounded (§5.3). |
| API actions + tus endpoint | `api2.php` on the receiver | Stores the manifest, decisions, and parts; reports state. New actions: open transfer, transfer status, send decisions, finalize part, part status, close transfer (TBD). No matching logic (T14). |
| Match, verify, archive | `moduleTransfer` on the receiver | Matching for the compare and the re-check at archive time are the same code. Archives with squirrel import plus an identity map, not the plain `ArchiveSquirrelPackage()` path. |

---

## 5. Design issues

### 5.1 UIDs and identity

**Problem.** Subject UIDs are random per instance, so a sender UID can already exist on the receiver for an unrelated person. Study numbers and series numbers are per subject, so they can also collide. And the receiver may already have the same person under a different UID (enrolled there directly).

**Approach.** The receiver never trusts a sender UID as an identity. It decides identity in this order:

1. **Origin match.** If an object on the receiver has origin = (this sender's instance ID, this object's sender ID), it is the same object. This is the normal case for repeat transfers and is fully automatic. Origin always means where the object was *first created*, and is passed along unchanged when transferred data is sent on. So data that goes A → B → A matches its original on A (A is the origin, and it finds its own ID), and data that goes A → B → C matches anything C already received directly from A.
2. **User-confirmed match.** If the compare step found a likely match (same alternate ID / GUID in any project on the receiver (T12), same DICOM StudyInstanceUID, same SeriesInstanceUID), the user decides on the review page (§3.3): *merge into the existing object* or *create new*. The decision is stored as an origin link, so it is never asked again.
3. **New object.** Otherwise the receiver creates a new object. For subjects it keeps the sender's UID if it's free; if it's taken, it assigns a new UID and stores the sender's UID as an alternate ID (type "transfer origin", TBD). Study and series numbers are assigned the next free number under the destination subject, and the original numbers are kept in the origin record.

So collisions are handled, not prevented, and the report tells the user about every renumbered object.

Every NiDB instance needs a stable, unique **instance ID** (a UUID generated once at install) for origin records to work. NiDB doesn't have one today, so it will be created (T5).

### 5.2 Changed data

An object that was transferred before (it has an origin match) but no longer matches shows as **Changed**. To tell which side changed, the receiver keeps a fingerprint (file list, sizes, checksums, field values) of each object as it was when last transferred, and both sides compare against it:

| Changed on | Meaning | Options |
|---|---|---|
| Sender only | The sender has a newer version; the receiver's copy is untouched since the last transfer. | **Resend**, **send as new**, or **skip** |
| Receiver only | Someone edited the receiver's copy; the sender's is unchanged. | Nothing to send; shown for information and skipped |
| Both | Edited on both sides since the last transfer. | **Send as new** or **skip**; resend isn't offered, so a receiver's edits are never overwritten |

**Resend** means: send the sender's current version of the object and make the receiver's copy match it, *in place*. The object keeps its receiver ID, UID, study/series number, and its links (enrollment, parent study, analyses that point to it). Compare with **send as new**, which creates a second, separate object next to the old one, and **skip**, which leaves the receiver as it is.

What "make it match" does depends on the object:

| Object | Resend does |
|---|---|
| Series | Files the sender added are copied in; files that changed are replaced; files the sender no longer has are removed. Series fields (description, protocol, etc.) are updated. |
| Subject, study, enrollment | Fields are updated to the sender's values. Child objects are not touched unless they're also selected and changed. |
| Observation, intervention | Values are updated to the sender's values. |
| Analysis | Files and results are replaced, as for a series. |
| Pipeline, experiment, data dictionary | Definition is updated to the sender's version. |

Resend only touches what came from the sender. Anything the receiver added under the object (its own analyses of a received series, its own series in a received study) stays. Those may now be out of date, so the report lists them.

If the sender only *added* things (new files, new observations), a resend loses nothing. Data is only displaced when a resend replaces or removes something: changed or removed files, and overwritten field values. That data is deleted with `SafeDeletePath()` and is not kept or versioned (T11). Once a resend is approved and archived there is no going back. The transfer log records what was replaced (old field values, file names, sizes, checksums), but not the data. The review page warns that approving a resend is permanent.

Default for every Changed row is **skip**, so nothing is overwritten without the user asking. Objects deleted on the sender are not deleted on the receiver; transfer only adds or replaces.

### 5.3 Large transfers (> 100 GB)

- **Parts.** The transfer is split into parts of at most `[transferpartsize]` (5 GB, T13). Whole subjects (with their studies, series, observations, etc.) are packed into a part up to the limit, so each archived part stands on its own. A subject bigger than the limit is split at study, then series, boundaries. A series is never split; a single series bigger than the limit becomes its own part. Objects that other data depends on (pipelines, experiments, data dictionary) go in the first part.
- **Bounded staging on the sender.** Parts are exported just before they are sent and deleted once the receiver confirms them, so the sender needs staging space for about one or two parts, not the whole transfer.
- **Resumable upload.** Each part is uploaded with tus, so a dropped connection resumes at the last byte the receiver has. This needs a C++ tus client in `moduleTransfer` (TBD: libcurl vs. Qt network) and a tus endpoint on the receiver that authenticates with the API session instead of the web login.
- **Space checks.** The comparison reports receiver free space; the receiver reserves space when the transfer opens and refuses a part that wouldn't fit. The sender checks its own staging space before each export.
- **Integrity.** Each part carries a checksum, verified by the receiver before archiving. squirrel's per-file checksums (if present) are checked during archive.
- **Throughput.** Measured per transfer and shown as an estimate. Bandwidth limits and time-of-day windows are deferred (T9).

### 5.4 Locking (mutex) on both sides

**Sender.** When `moduleTransfer` picks up a submitted transfer, every object in it is locked, so the sender's side can't change between the comparison and the send. While locked, the object can be read (viewing, downloading, pipelines) but not deleted, merged, moved to another project, renumbered/UID-changed, or moved between storage tiers. Adding new data to a locked subject is allowed; it just isn't part of this transfer. A part's locks are released once it's archived on the receiver, and the rest when the transfer ends or is cancelled.

**Receiver.** When sending starts, the destination subjects that will be merged into or replaced are locked against the same operations, and against a second transfer writing into them at the same time. New subjects are locked as soon as they're created. The receiver's incoming transfer directory is owned by that transfer only.

**How.** Locks live in the database, not as lock files, in the general-purpose `object_locks` table (§9.1). That table is shared with other features, starting with tier migration, so a transfer and a tier move can't work on the same data at the same time. The sender takes **shared** locks (reading only, so two transfers can send the same subject) and the receiver takes **exclusive** locks. The web UI can show "locked by transfer T-0142" and refuse an action with a clear message. The pages and modules that delete/merge/move objects must check for a lock. A lock whose owner hasn't refreshed it for a set time is stale and can be cleared by an admin (or by `moduleManager`).

### 5.5 Collisions

Archiving is automatic (T8). A transfer only stops for a person when there is a **collision**: the receiver would have to guess whether incoming data is the same as data it already has, or would have to change data it already has.

**Not collisions.** These are handled automatically and recorded in the log:

| Case | What happens |
|---|---|
| New object | Created. |
| Origin match, identical | Skipped ("already there"). |
| Sender's subject UID is used by an unrelated subject on the receiver | New UID assigned; the sender's UID is saved as an alternate ID. |
| Study or series number already used under the destination subject | Next free number assigned; the original number is kept in the origin record. |

**Collisions.** These need a decision:

| # | Case | Example | Choices |
|---|---|---|---|
| C1 | **Possible match.** The receiver has an object that isn't linked to this sender's object but looks like the same thing. | A subject in any project on the receiver (T12) with the same alternate ID or GUID; a study with the same DICOM StudyInstanceUID; a series with the same SeriesInstanceUID. | Merge into the existing object, or create a new one. |
| C2 | **Changed.** Origin match, but the content differs. | Files added to a series, or an observation value edited on the sender. | Resend (replace), send as new, or skip (§5.2). |
| C3 | **Origin target gone.** An origin record points to an object that has since been deleted on the receiver, or merged into another one. | The receiver merged two subjects after an earlier transfer. | Send as new, follow the merge (if known), or skip. |

**When decisions are made.** Most collisions are found when the transfer is compared, and the sending user resolves them in **Needs review** (§3.3) before anything is sent. The receiver can still change between the compare and the archive of a part. If the receiver finds a collision at archive time that no decision covers, it doesn't guess: that part stops as **needs decision**, the other parts continue, and the sending user resolves it on the transfer status page. The decision and who made it are logged.

**Log.** The receiver keeps a permanent database record for each transfer, with one row per object: sender instance and ID, receiver ID, action (created, skipped, merged, replaced, renumbered), any collision, and the decision taken. This is the "what was transferred" record, and it is what the §3.4 report is built from. Logs are kept in the database, not in `[logdir]`.

---

## 6. Security and permissions

- Sender: the user must have **Data** permission (and **PHI** permission for PHI fields) on every object in the selection, using the rules in `doc/permissions.md`. Only users with a transfer right (TBD: per-project or admin) can start a transfer.
- Receiver: every transfer runs as the owner's own account on the receiver (T18), so the receiver's project permissions and audit log apply to that person. They need write access to the destination project.
- HTTPS only. Peer keys are stored encrypted on the sender, one per user per peer, and are only used for that user's transfers (TBD how; the old remote connection stores a password).
- Admins on either side can pause or block any transfer on their server (§3.5).
- The manifest carries IDs and sizes, not PHI values. PHI fields are only sent inside parts, and not at all when the user chose **Anonymize** (T7).
- Both sides keep an audit record of every transfer: who, what, when, from/to where, result.

---

## 7. Prerequisites

- Fix the squirrel export gaps that would lose data in transit (`doc/nidb-export.md` §1–§3), at least for the object types offered on the transfer page.
- squirrel import must handle every type the export writes (observations, interventions, analyses with files, pipelines, experiments, data dictionary), not just imaging.
- squirrel import needs an "identity map" input, so the archive step applies the receiver's §5.1 decisions instead of matching on its own.
- A stable instance ID on every NiDB install (T5).

---

## 8. Open questions

None at the moment.

---

## 9. Technical details

### 9.1 Database tables

Thirteen new tables, all InnoDB with `utf8` / `utf8_general_ci` to match the data tables they join to (`subjects`, `studies`, `*_series`). Columns use the table-prefix naming of `uploads`. Primary keys and indexes are listed after each table; in `nidb.sql` they go in the usual `ALTER TABLE` sections. These tables are permanent records: nothing deletes rows from them except the staging/tus files they point to.

A server that both sends and receives has all of them. Sender tables are used only for outgoing transfers, receiver tables only for incoming ones.

| Table | Side | One row per |
|---|---|---|
| `nidb_system` | both | the server (one row, holds the system UUID, T5) |
| `transfer_peers` | sender | registered receiver |
| `transfer_peerkeys` | sender | user × peer API key (T18) |
| `transfers` | sender | outgoing transfer |
| `transfer_selection` | sender | object the user selected (before expansion) |
| `transfer_objects` | sender | object in the manifest, with its comparison status, decision, and result |
| `transfer_parts` | sender | squirrel package (part) |
| `transfers_incoming` | receiver | incoming transfer |
| `transfer_incomingobjects` | receiver | object in a received manifest, with its match, decision, and result (the permanent per-object log, §5.5) |
| `transfer_incomingparts` | receiver | received part |
| `object_origins` | receiver | object this server received, linking it to where it was first created (§5.1) |
| `object_locks` | both | locked object (§5.4); shared with tier migration and other features |
| `transfer_log` | both | event: state change, decision, admin action, error |

**Naming.** NiDB already has an `instance` table, but a NiDB "instance" is an organizational unit inside one server, not the server itself. To avoid confusion, the server's identity is called the **system UUID** (`nidb_system.system_uuid`), and peers are "systems".

**Object identity.** Throughout, an object is identified by `(object_type, modality, object_id)`. `modality` is needed only for series, since each modality has its own `*_series` table; it is `''` for all other types. IDs are `bigint` so one column fits every source table.

```sql
-- object types used in every transfer table
-- enum('subject','enrollment','study','series','observation','intervention',
--      'experiment','pipeline','minipipeline','analysis','datadictionary')
```

#### `nidb_system` (both)

```sql
CREATE TABLE `nidb_system` (
  `system_uuid` char(36) NOT NULL COMMENT 'generated once at install/upgrade; never changes (T5)',
  `system_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'display name shown to peers',
  `system_createdate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`system_uuid`). Exactly one row.
```

If a server is cloned (database copied to a new machine), both copies have the same UUID. `setup.php` should offer **Regenerate system UUID** for that case, with a warning that existing peers will see it as a new system.

#### `transfer_peers` (sender)

```sql
CREATE TABLE `transfer_peers` (
  `peer_id` int(11) NOT NULL,
  `peer_name` varchar(255) NOT NULL,
  `peer_url` varchar(255) NOT NULL COMMENT 'https:// base URL of the receiver',
  `peer_systemuuid` char(36) DEFAULT NULL COMMENT 'receiver system UUID, learned on first check; a change means the URL now points at a different server',
  `peer_systemname` varchar(255) DEFAULT NULL,
  `peer_version` varchar(50) DEFAULT NULL COMMENT 'receiver NiDB version at last check',
  `peer_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = no new transfers; running ones are not affected',
  `peer_lastcheck` datetime DEFAULT NULL,
  `peer_createdby` int(11) NOT NULL COMMENT 'users.user_id of the admin who added it',
  `peer_createdate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`peer_id`), UNIQUE (`peer_url`)
```

#### `transfer_peerkeys` (sender)

```sql
CREATE TABLE `transfer_peerkeys` (
  `peerkey_id` int(11) NOT NULL,
  `peer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'sender user who owns the key; nobody else can use it',
  `peerkey_username` varchar(64) NOT NULL COMMENT 'the user''s username on the receiver',
  `peerkey_apikey` text NOT NULL COMMENT 'encrypted API key (encryption TBD)',
  `peerkey_defaultprojectid` int(11) DEFAULT NULL COMMENT 'receiver project_id',
  `peerkey_defaultprojectname` varchar(255) DEFAULT NULL COMMENT 'cached for display',
  `peerkey_status` enum('untested','ok','failed') NOT NULL DEFAULT 'untested',
  `peerkey_lastcheck` datetime DEFAULT NULL,
  `peerkey_createdate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`peerkey_id`), UNIQUE (`peer_id`,`user_id`), KEY (`user_id`)
```

#### `transfers` (sender)

The transfer object of T14. PHP inserts the row and records user/admin requests; `moduleTransfer` makes every state change.

```sql
CREATE TABLE `transfers` (
  `transfer_id` int(11) NOT NULL,
  `transfer_uuid` char(36) NOT NULL COMMENT 'shared ID of this transfer on both sides',
  `user_id` int(11) NOT NULL COMMENT 'owner (T17)',
  `peer_id` int(11) NOT NULL,
  `peerkey_id` int(11) NOT NULL,
  `remote_incomingid` int(11) DEFAULT NULL COMMENT 'transfers_incoming.incoming_id on the receiver',
  `dest_projectid` int(11) NOT NULL COMMENT 'receiver project_id',
  `dest_projectname` varchar(255) NOT NULL DEFAULT '' COMMENT 'cached for display',

  -- options chosen on the submit page (§3.2)
  `transfer_include` set('imaging','observations','interventions','experiments','analyses','pipelines','minipipelines','datadictionary') NOT NULL,
  `transfer_modalities` varchar(255) NOT NULL DEFAULT '' COMMENT 'comma-separated, or blank for all',
  `transfer_analysisfiles` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = analysis metadata only',
  `transfer_anonymize` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'T7',

  -- state (§3.3, §4.1)
  `transfer_status` enum('submitted','comparing','needsreview','sending','paused','needsdecision','complete','cancelled','blocked','error') NOT NULL DEFAULT 'submitted',
  `transfer_statusmsg` text DEFAULT NULL COMMENT 'human-readable detail for the current state',
  `transfer_request` enum('','approve','pause','resume','cancel','retry') NOT NULL DEFAULT '' COMMENT 'set by PHP, cleared by the module once acted on',
  `transfer_requestby` int(11) DEFAULT NULL COMMENT 'user_id who made the request',
  `transfer_pausedby` enum('','user','senderadmin','receiveradmin') NOT NULL DEFAULT '',
  `transfer_blockedby` enum('','senderadmin','receiveradmin') NOT NULL DEFAULT '',
  `transfer_blockedbyname` varchar(255) DEFAULT NULL COMMENT 'admin username (and server for receiver admins)',
  `transfer_blockedreason` text DEFAULT NULL,

  -- totals, recomputed by the module from transfer_objects / transfer_parts
  `transfer_objectcount` int(11) NOT NULL DEFAULT 0,
  `transfer_conflictcount` int(11) NOT NULL DEFAULT 0 COMMENT 'unresolved conflicts',
  `transfer_bytestotal` bigint(20) NOT NULL DEFAULT 0 COMMENT 'size of everything selected',
  `transfer_bytestosend` bigint(20) NOT NULL DEFAULT 0 COMMENT 'after skips',
  `transfer_bytessent` bigint(20) NOT NULL DEFAULT 0,
  `transfer_rate` double DEFAULT NULL COMMENT 'measured bytes/sec, for the estimate',
  `transfer_receiverfree` bigint(20) DEFAULT NULL COMMENT 'receiver free space reported at comparison',

  -- dates
  `transfer_submitdate` datetime NOT NULL DEFAULT current_timestamp(),
  `transfer_comparedate` datetime DEFAULT NULL COMMENT 'when the last comparison finished (T16)',
  `transfer_approvedate` datetime DEFAULT NULL,
  `transfer_startdate` datetime DEFAULT NULL COMMENT 'sending started',
  `transfer_enddate` datetime DEFAULT NULL,
  `transfer_lastcheckin` datetime DEFAULT NULL COMMENT 'module heartbeat; locks are stale when this is old (§5.4)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`transfer_id`), UNIQUE (`transfer_uuid`), KEY (`user_id`), KEY (`peer_id`), KEY (`transfer_status`)
```

#### `transfer_selection` (sender)

What the user picked on the submit page, before the module expands it. Kept so the transfer can always show what was asked for, separately from what the module found.

```sql
CREATE TABLE `transfer_selection` (
  `transferselection_id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `object_type` enum('subject','enrollment','study','series','observation','intervention','experiment','pipeline','minipipeline','analysis','datadictionary') NOT NULL,
  `modality` varchar(50) NOT NULL DEFAULT '',
  `object_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`transferselection_id`), KEY (`transfer_id`)
```

#### `transfer_objects` (sender)

The manifest. The module fills it by expanding `transfer_selection` with the `transfer_include` options, then updates each row with the receiver's comparison result, the user's decision, and the final result.

```sql
CREATE TABLE `transfer_objects` (
  `transferobject_id` bigint(20) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `parent_objectid` bigint(20) DEFAULT NULL COMMENT 'transferobject_id of the parent (study -> subject, series -> study, ...)',
  `transferpart_id` int(11) DEFAULT NULL COMMENT 'part that carries it; NULL if not sent',
  `object_type` enum('subject','enrollment','study','series','observation','intervention','experiment','pipeline','minipipeline','analysis','datadictionary') NOT NULL,
  `modality` varchar(50) NOT NULL DEFAULT '',
  `object_id` bigint(20) NOT NULL COMMENT 'local ID',
  `object_label` varchar(255) NOT NULL DEFAULT '' COMMENT 'for display: UID, study num, series desc, ...',

  -- origin carried to the receiver (§5.1, T10): this server, unless the object was itself received
  `origin_systemuuid` char(36) NOT NULL,
  `origin_objectid` bigint(20) NOT NULL,

  `object_size` bigint(20) NOT NULL DEFAULT 0,
  `object_filecount` int(11) NOT NULL DEFAULT 0,
  `object_fingerprint` char(64) NOT NULL DEFAULT '' COMMENT 'sha256 over file list, sizes, stored checksums, field values (§5.2)',

  -- comparison (§3.3)
  `compare_status` enum('pending','new','alreadythere','uidinuse','cantsend','possiblematch','changed','origingone') NOT NULL DEFAULT 'pending',
  `compare_changedon` enum('','sender','receiver','both') NOT NULL DEFAULT '' COMMENT 'for changed (§5.2)',
  `compare_matchid` bigint(20) DEFAULT NULL COMMENT 'receiver object ID it matched or may match',
  `compare_matchlabel` varchar(255) DEFAULT NULL COMMENT 'receiver UID etc.; never project or data (T12)',
  `compare_detail` text DEFAULT NULL COMMENT 'reason for cantsend, match basis for possiblematch',

  -- decision (§5.5)
  `decision` enum('','send','skip','resend','sendasnew','merge','createnew') NOT NULL DEFAULT '' COMMENT 'blank = default for the status',
  `decision_userid` int(11) DEFAULT NULL,
  `decision_date` datetime DEFAULT NULL,

  -- result, reported back by the receiver (§3.4)
  `result` enum('pending','created','skipped','merged','replaced','renumbered','failed') NOT NULL DEFAULT 'pending',
  `result_remoteid` bigint(20) DEFAULT NULL COMMENT 'receiver object ID',
  `result_remotelabel` varchar(255) DEFAULT NULL COMMENT 'receiver UID / study num / series num, if changed',
  `result_msg` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`transferobject_id`), KEY (`transfer_id`,`compare_status`), KEY (`transfer_id`,`parent_objectid`), KEY (`transferpart_id`),
-- KEY (`object_type`,`modality`,`object_id`)
```

#### `transfer_parts` (sender)

```sql
CREATE TABLE `transfer_parts` (
  `transferpart_id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `part_num` int(11) NOT NULL COMMENT '1-based, in send order',
  `part_status` enum('queued','exporting','exported','uploading','uploaded','verifying','archiving','archived','needsdecision','error') NOT NULL DEFAULT 'queued',
  `part_label` varchar(255) NOT NULL DEFAULT '' COMMENT 'for display: "S4410DEF studies 1-2"',
  `part_size` bigint(20) NOT NULL DEFAULT 0 COMMENT 'estimated from transfer_objects',
  `part_packagepath` varchar(255) DEFAULT NULL COMMENT 'staging path; cleared after the receiver confirms',
  `part_packagesize` bigint(20) DEFAULT NULL,
  `part_checksum` char(64) DEFAULT NULL COMMENT 'sha256 of the package',
  `part_tusurl` varchar(255) DEFAULT NULL COMMENT 'upload URL, for resume',
  `part_bytesuploaded` bigint(20) NOT NULL DEFAULT 0,
  `part_attempts` int(11) NOT NULL DEFAULT 0,
  `part_msg` text DEFAULT NULL,
  `part_startdate` datetime DEFAULT NULL,
  `part_enddate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`transferpart_id`), UNIQUE (`transfer_id`,`part_num`)
```

#### `transfers_incoming` (receiver)

```sql
CREATE TABLE `transfers_incoming` (
  `incoming_id` int(11) NOT NULL,
  `transfer_uuid` char(36) NOT NULL COMMENT 'same as transfers.transfer_uuid on the sender',
  `sender_systemuuid` char(36) NOT NULL,
  `sender_systemname` varchar(255) NOT NULL DEFAULT '',
  `sender_username` varchar(255) NOT NULL DEFAULT '' COMMENT 'owner''s username on the sender, for display',
  `apiuser_id` int(10) UNSIGNED NOT NULL COMMENT 'api_users row that authenticated',
  `user_id` int(11) NOT NULL COMMENT 'users row with the same username; permissions are checked against this (T18)',
  `dest_projectid` int(11) NOT NULL,
  `incoming_anonymized` tinyint(1) NOT NULL DEFAULT 0,

  `incoming_status` enum('comparing','compared','receiving','paused','needsdecision','complete','cancelled','blocked','error') NOT NULL DEFAULT 'comparing',
  `incoming_statusmsg` text DEFAULT NULL,
  `incoming_request` enum('','pause','resume','block') NOT NULL DEFAULT '' COMMENT 'receiver admin request, set by PHP',
  `incoming_pausedby` enum('','sender','receiveradmin') NOT NULL DEFAULT '' COMMENT 'sender = any pause on the sending side',
  `incoming_blockedby` enum('','senderadmin','receiveradmin') NOT NULL DEFAULT '',
  `incoming_blockedbyname` varchar(255) DEFAULT NULL,
  `incoming_blockedreason` text DEFAULT NULL,

  `incoming_stagingpath` varchar(255) DEFAULT NULL COMMENT 'tus context directory for this transfer',
  `incoming_bytesreserved` bigint(20) NOT NULL DEFAULT 0 COMMENT 'space reserved when receiving starts (§5.3)',
  `incoming_bytesreceived` bigint(20) NOT NULL DEFAULT 0,

  `incoming_opendate` datetime NOT NULL DEFAULT current_timestamp(),
  `incoming_startdate` datetime DEFAULT NULL,
  `incoming_enddate` datetime DEFAULT NULL,
  `incoming_lastcontact` datetime DEFAULT NULL COMMENT 'last API call from the sender',
  `incoming_lastcheckin` datetime DEFAULT NULL COMMENT 'receiver module heartbeat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`incoming_id`), UNIQUE (`transfer_uuid`), KEY (`sender_systemuuid`), KEY (`user_id`), KEY (`incoming_status`)
```

#### `transfer_incomingobjects` (receiver)

The receiver's copy of the manifest, and the permanent per-object log of what was transferred (§5.5). Old values of anything a resend replaced are kept here, never the data (T11).

```sql
CREATE TABLE `transfer_incomingobjects` (
  `incomingobject_id` bigint(20) NOT NULL,
  `incoming_id` int(11) NOT NULL,
  `incomingpart_id` int(11) DEFAULT NULL,
  `sender_objectid` bigint(20) NOT NULL COMMENT 'sender transfer_objects.transferobject_id, for reporting back',
  `sender_parentid` bigint(20) DEFAULT NULL,
  `object_type` enum('subject','enrollment','study','series','observation','intervention','experiment','pipeline','minipipeline','analysis','datadictionary') NOT NULL,
  `modality` varchar(50) NOT NULL DEFAULT '',
  `origin_systemuuid` char(36) NOT NULL,
  `origin_objectid` bigint(20) NOT NULL,
  `object_size` bigint(20) NOT NULL DEFAULT 0,
  `object_fingerprint` char(64) NOT NULL DEFAULT '',
  `object_hints` text DEFAULT NULL COMMENT 'JSON match hints: altuid/GUID hashes, StudyInstanceUID, SeriesInstanceUID (empty when anonymized)',

  `match_status` enum('pending','new','alreadythere','uidinuse','cantsend','possiblematch','changed','origingone') NOT NULL DEFAULT 'pending',
  `match_changedon` enum('','sender','receiver','both') NOT NULL DEFAULT '',
  `match_localid` bigint(20) DEFAULT NULL,
  `match_detail` text DEFAULT NULL,

  `decision` enum('','send','skip','resend','sendasnew','merge','createnew') NOT NULL DEFAULT '',

  `result` enum('pending','created','skipped','merged','replaced','renumbered','failed') NOT NULL DEFAULT 'pending',
  `result_localid` bigint(20) DEFAULT NULL,
  `result_locallabel` varchar(255) DEFAULT NULL,
  `result_replaced` longtext DEFAULT NULL COMMENT 'JSON: old field values; removed/replaced file names, sizes, checksums (T11)',
  `result_msg` text DEFAULT NULL,
  `result_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`incomingobject_id`), UNIQUE (`incoming_id`,`sender_objectid`), KEY (`incomingpart_id`),
-- KEY (`object_type`,`modality`,`result_localid`)
```

#### `transfer_incomingparts` (receiver)

```sql
CREATE TABLE `transfer_incomingparts` (
  `incomingpart_id` int(11) NOT NULL,
  `incoming_id` int(11) NOT NULL,
  `part_num` int(11) NOT NULL,
  `part_status` enum('uploading','uploaded','verifying','verified','archiving','archived','needsdecision','error') NOT NULL DEFAULT 'uploading',
  `part_expectedsize` bigint(20) NOT NULL,
  `part_expectedchecksum` char(64) NOT NULL,
  `part_path` varchar(255) DEFAULT NULL COMMENT 'received package; deleted after archive',
  `part_bytesreceived` bigint(20) NOT NULL DEFAULT 0,
  `part_msg` text DEFAULT NULL,
  `part_startdate` datetime DEFAULT NULL,
  `part_enddate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`incomingpart_id`), UNIQUE (`incoming_id`,`part_num`)
```

#### `object_origins` (receiver)

The identity table behind §5.1. Independent of any one transfer, so matching never has to search old transfer logs. Objects created natively on this server have no row: their origin is this server's own UUID and ID.

```sql
CREATE TABLE `object_origins` (
  `origin_id` bigint(20) NOT NULL,
  `object_type` enum('subject','enrollment','study','series','observation','intervention','experiment','pipeline','minipipeline','analysis','datadictionary') NOT NULL,
  `modality` varchar(50) NOT NULL DEFAULT '',
  `local_objectid` bigint(20) NOT NULL,
  `origin_systemuuid` char(36) NOT NULL COMMENT 'where the object was first created',
  `origin_objectid` bigint(20) NOT NULL,
  `origin_label` varchar(255) NOT NULL DEFAULT '' COMMENT 'UID / study num / series num at the origin',
  `via_systemuuid` char(36) NOT NULL COMMENT 'the server that sent it here (differs from origin for A -> B -> C)',
  `origin_fingerprint` char(64) NOT NULL COMMENT 'fingerprint as of the last transfer (§5.2)',
  `origin_firstincomingid` int(11) NOT NULL,
  `origin_lastincomingid` int(11) NOT NULL,
  `origin_lastdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`origin_id`),
-- UNIQUE (`origin_systemuuid`,`object_type`,`modality`,`origin_objectid`)   -- one local object per origin
-- KEY (`object_type`,`modality`,`local_objectid`)                           -- origin of a local object, when sending it on
```

When a subject gets a new UID because the sender's was taken (§5.1), the sender's UID is also added to `subject_altuid` as a non-primary alternate ID, so people can find the subject by it. `object_origins.origin_label` is the record that matching uses; the alternate ID is only for searching.

When this server sends an object on, it looks up the object's `object_origins` row: if there is one, the transfer carries that origin; if not, it carries this server's UUID and the local ID.

#### `object_locks` (both; shared with other features)

Locks are not specific to transfers. Any feature that needs to keep an object from changing while it works on it uses this one table and one set of helpers. Transfers (outgoing and incoming) and tier migration (`doc/tiered-storage.md` §2.5–2.6) are the first users. Because every lock is in one place, a page that deletes or moves something checks one table, and the features see each other's locks. For example, a study can't start a tier move while it's being transferred, and the reverse.

```sql
CREATE TABLE `object_locks` (
  `lock_id` bigint(20) NOT NULL,
  `object_type` enum('subject','study','experiment','pipeline','minipipeline','datadictionary') NOT NULL,
  `object_id` bigint(20) NOT NULL,
  `lock_mode` enum('shared','exclusive') NOT NULL,
  `owner_type` varchar(50) NOT NULL COMMENT 'feature holding the lock: transfer_out, transfer_in, tiermigration, ...',
  `owner_id` bigint(20) NOT NULL COMMENT 'row ID in the owner''s own table (transfers, transfers_incoming, storage_tier_migration, ...)',
  `owner_label` varchar(255) NOT NULL DEFAULT '' COMMENT 'for messages: "transfer T-0142 to UCHC NiDB", "tier move to archivedir2"',
  `lock_userid` int(11) DEFAULT NULL COMMENT 'user who started the work, if any',
  `lock_date` datetime NOT NULL DEFAULT current_timestamp(),
  `lock_heartbeat` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'refreshed by the owner; old = stale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`lock_id`), KEY (`object_type`,`object_id`), KEY (`owner_type`,`owner_id`)
```

**Owners.** `owner_type` is a `varchar`, not an `enum`, so a new feature can take locks without a schema change. The values in use are listed here:

| `owner_type` | Feature | `owner_id` refers to | Mode | Locks |
|---|---|---|---|---|
| `transfer_out` | Outgoing transfer | `transfers.transfer_id` | shared | subjects + top-level objects in the transfer |
| `transfer_in` | Incoming transfer | `transfers_incoming.incoming_id` | exclusive | destination subjects being written to |
| `tiermigration` | Tier move | `storage_tier_migration.migration_id` | exclusive | the study being moved |

**Modes.** A shared lock means "I'm reading this; don't change it." An exclusive lock means "I'm changing this; nobody else may lock it or start work on it."

| | No lock | Shared | Exclusive |
|---|---|---|---|
| View, search, download | yes | yes | yes |
| Add new data under it (new study, new series, new observations) | yes | yes | no |
| Start a job that reads its files (pipeline, QC, MRIQA, export) | yes | yes | no |
| Modify, delete, merge, move to another project, change UID/number | yes | no | no |
| Take another shared lock | yes | yes | no |
| Take another exclusive lock | yes | no | no |

Jobs that are already running when an exclusive lock is requested aren't stopped. Whoever wants the exclusive lock waits until the object is idle (tier migration's "study idle" rule), then takes it.

**Levels.** Objects form a hierarchy: subject → study. A lock covers everything under it, so a lock on a subject also covers its enrollments, studies, series, observations, and analyses. Checks and lock requests look both ways:

- **Is X locked?** Look for locks on X *and its ancestors*. For a series, that means its study and its subject.
- **Can I lock X?** Look for conflicting locks on X, its ancestors, *and its descendants*. For example, an exclusive lock on a subject conflicts with a tier move's lock on one of its studies.

Only subjects, studies, and top-level objects (experiments, pipelines, mini-pipelines, data dictionaries) are locked directly. Transfers lock subjects; tier migration locks studies. Series and smaller objects are always covered through their study/subject, so checks stay at two levels. More types can be added to the `object_type` enum if a feature needs them.

**Taking locks.** All-or-nothing: a feature asks for all the locks it needs in one call, inside one transaction (`SELECT ... FOR UPDATE` on the existing locks for those objects and their ancestors/descendants). Either it gets every lock, or none and a list of what's in the way. MariaDB has no partial unique index, so these rules are enforced in this code, not by a constraint. A feature that can't get its locks waits and tries again rather than failing. A transfer shows "waiting for lock: tier move to archivedir2 on S1234ABC study 3".

**Heartbeat and stale locks.** The owner refreshes all of its locks with one statement (`UPDATE object_locks SET lock_heartbeat = now() WHERE owner_type = ? AND owner_id = ?`) each time it checks in. A lock whose heartbeat is older than `[lockstaletime]` is stale. `moduleManager` reports stale locks and clears them when the owner's process is gone; an admin can also clear one from **Admin → Locks**. Because `lock_heartbeat` is on the lock row, neither needs to know anything about the owner's own tables.

**Releasing.** By owner (`DELETE ... WHERE owner_type = ? AND owner_id = ?`), or for one object when the owner is done with it early. An outgoing transfer releases each subject once its last part is archived.

**Helpers** (one implementation per language, used by every feature and every check):

| C++ (`objectlock.{h,cpp}`, TBD) | PHP (`functions.php`, TBD) | Does |
|---|---|---|
| `AcquireLocks(owner, objects, mode, &blockers)` | — | All-or-nothing lock, returns blockers on failure |
| `RefreshLocks(owner)` | — | Heartbeat |
| `ReleaseLocks(owner)`, `ReleaseLock(owner, object)` | — | Release |
| `GetLock(object, action)` | `GetObjectLock($type, $id, $action)` | Returns the lock that blocks `action` (modify, addchild, startjob) on the object, or none |

Pages and modules call `GetObjectLock()` / `GetLock()` before any blocked action and refuse with the lock's `owner_label`: "Can't delete this study: it's locked by transfer T-0142 to UCHC NiDB". §9.6 will list the places that need the check.

#### `transfer_log` (both)

Events, not objects: state changes, decisions, admin actions, errors. Per-object results are in `transfer_objects` / `transfer_incomingobjects`.

```sql
CREATE TABLE `transfer_log` (
  `transferlog_id` bigint(20) NOT NULL,
  `log_side` enum('sender','receiver') NOT NULL,
  `transfer_id` int(11) NOT NULL COMMENT 'transfers.transfer_id or transfers_incoming.incoming_id',
  `part_num` int(11) DEFAULT NULL,
  `log_type` enum('state','decision','admin','error','info') NOT NULL,
  `log_username` varchar(255) DEFAULT NULL COMMENT 'who did it; blank for the module',
  `log_msg` text NOT NULL,
  `log_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- PRIMARY KEY (`transferlog_id`), KEY (`log_side`,`transfer_id`,`log_date`)
```

#### Not new

- **API users.** `api_users` has no `user_id`; it is linked to `users` by username. The receiver resolves the key's `api_users.username` to `users.user_id` once, when the transfer opens, stores both in `transfers_incoming`, and checks project permissions against `user_id` (T18).
- **Old transfer tables.** `remote_connections` and the `exports.remotenidb_*` columns belong to the old transfer (T1) and aren't used. They can be dropped when it is retired.

### 9.2 – 9.7 (to be filled in)

- 9.2 `api2.php` actions and payloads (including admin pause/block reported to the sender)
- 9.3 tus endpoint for API clients
- 9.4 `moduleTransfer` structure and state machine
- 9.5 squirrel export per part, and the identity map on import
- 9.6 Lock checks: list of pages/modules that must call `GetObjectLock()` / `GetLock()`. Deferred until the transfer works end to end (T20), but required before release.
- 9.7 Config keys (`[moduletransferthreads]`, `[transferpartsize]`, `[lockstaletime]`, staging dir, timeouts)
