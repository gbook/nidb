# NiDB — 21 CFR Part 11 Gap Analysis (Electronic Records)

**Status:** Draft for internal review
**Date:** 2026-07-16
**Scope decision:** **Electronic records only. No electronic signatures.**

---

## 1. Purpose & scope

This document assesses the NiDB software against the technical controls of **21 CFR Part 11
(Electronic Records; Electronic Signatures)** and identifies the work needed to make NiDB
*Part 11 capable* for **electronic records**.

Because the organization has decided **not** to use electronic signatures in NiDB, the following
are **out of scope** and are not addressed further except to note the constraint:

- **Subpart C** — §11.100 (general signature requirements), §11.200 (signature components/controls),
  §11.300 (identification code / password controls *as they pertain to signatures*).
- **§11.50** (signature manifestations) and **§11.70** (signature/record linking).

> **Constraint that must be enforced organizationally:** if e-signatures are out of scope, then NiDB
> must **not** be used to apply or represent signed records (approvals, sign-offs, "reviewed by")
> that are relied upon as the equivalent of a handwritten signature. Where such sign-offs are needed,
> they must occur in a separate, validated, signature-capable system, or e-signatures must be brought
> back into scope. Document this decision in a scoping/risk-assessment memo.

Note that **§11.300 password controls still apply** to electronic-records access control (they are
referenced by §11.10(d)/(g) as part of limiting access), even though signatures are out of scope.

### Important framing

Part 11 compliance is **not a property of the source code alone**. It is a property of the deployed
system **plus** the operating organization. Roughly half of the obligations below are **procedural**
(validation, SOPs, training, documentation control) and cannot be satisfied by code. This document
marks each item as **[CODE]**, **[PROCESS]**, or **[BOTH]**.

This is a technical gap analysis, **not regulatory advice**. A QA/regulatory owner must confirm scope,
own the validation and SOP program, and sign off on the compliance determination.

### System classification

NiDB is a **closed system** per §11.3(b)(4) — system access is controlled by the persons responsible
for the content of the electronic records. Therefore **§11.30 (open-system controls, e.g. record
encryption in transit/at rest for third-party-hosted data) does not primarily apply**, though TLS for
the web tier remains a general security expectation.

---

## 2. Control-by-control assessment

Legend: ✅ met · ⚠️ partial · ❌ gap · ⛔ out of scope (no e-signatures)

| § | Control | Status | Basis |
|---|---|---|---|
| 11.10(a) | **Validation** of systems for accuracy, reliability, consistent performance, and ability to discern altered records | ❌ [PROCESS] | No validation package (requirements, IQ/OQ/PQ, traceability). |
| 11.10(b) | Ability to generate **accurate and complete copies** of records (human-readable and electronic) for inspection | ⚠️ [BOTH] | Strong export (CSV, squirrel, BIDS, NDA) exists but is not formalized/validated as record copies for inspection. |
| 11.10(c) | **Protection of records** to enable accurate/ready retrieval throughout the retention period | ⚠️ [BOTH] | DB + tape-backup module exist; no formal retention/legal-hold policy. **Risk:** the timeseries table's yearly partition + scheduled MariaDB event could purge data — must be reviewed against retention rules. |
| 11.10(d) | **Limit system access** to authorized individuals | ⚠️ [CODE] | Login + roles exist (`user_enabled`, `user_isadmin`, `user_issiteadmin`, per-project `user_project`), but access controls are undermined by weak auth (see §3.2). |
| 11.10(e) | **Secure, computer-generated, time-stamped audit trail**; changes must not obscure prior values; retained as long as the record and available for review/copy | ❌ **[CODE]** | **Primary gap.** `changelog` table exists but is essentially unused (0 rows, referenced in 3 files), captures **no before/after values**, and its own timestamp is **mutable** (`on update current_timestamp()`). No systematic trail across edit paths. |
| 11.10(f) | **Operational system checks** to enforce permitted sequencing | ⚠️ [BOTH] | Workflow/status states exist (pipeline/analysis) but not formalized as enforced sequencing. |
| 11.10(g) | **Authority checks** — only authorized individuals may use the system / access operations | ⚠️ [CODE] | Role model exists; weakened by the same auth issues as (d). |
| 11.10(h) | **Device checks** to validate source of data input | ❌ [BOTH] | No source-of-input validation (e.g., trusted DICOM sources, ingest source verification). |
| 11.10(i) | Personnel **education/training/experience** | ❌ [PROCESS] | Organizational training records. |
| 11.10(j) | Written **accountability policies** for actions taken | ❌ [PROCESS] | SOP. |
| 11.10(k) | **Controls over systems documentation** (distribution, access, revision/change control) | ⚠️ [PROCESS] | Source is version-controlled (git); formal controlled-documentation SOP not established. |
| 11.50 | Signature manifestations | ⛔ | No e-signatures. |
| 11.70 | Signature/record linking | ⛔ | No e-signatures. |
| 11.100/200 | Electronic signature requirements & components | ⛔ | No e-signatures. |
| 11.300 | Identification code / password controls | ⚠️ [CODE] | Applies to **access control** even without signatures; largely unmet for web login (see §3.2). |

---

## 3. In-scope gaps and required work

### 3.1 Audit trail (§11.10(e)) — the largest and most consequential lift

**Current state.** The `changelog` table has a reasonable shape (performing user, affected entity IDs,
`change_datetime`, `change_event`, `change_desc`) but:
- It is **not populated** in practice (0 rows; only referenced in `functions.php`, `pipelines.php`,
  `studies.php`).
- It records **no old/new values** — only a free-text `change_desc`.
- The audit row is **mutable**: `change_datetime timestamp … on update current_timestamp()` means the
  row can be silently updated. `changelog_subject` covers only subject delete/obliterate/move.

**Requirements to meet §11.10(e):**
1. **Append-only, tamper-evident store.** Audit rows must never be updated or deleted.
   - Remove `ON UPDATE CURRENT_TIMESTAMP` from the audit timestamp.
   - Revoke `UPDATE`/`DELETE` on the audit table(s) from the application DB user; consider a dedicated
     restricted grant.
   - Consider tamper-evidence (e.g., per-row or periodic hash chaining) for higher assurance.
2. **Before/after capture.** For every create/modify/delete of a regulated record, store *what changed*
   — field, old value, new value — not just a description. Changes must not obscure previously recorded
   information (i.e., prior values remain retrievable).
3. **Complete coverage of write paths.** NiDB writes are scattered across many PHP files (largely direct,
   string-built SQL) **and** the C++ backend modules. Two implementation strategies:
   - **Database triggers** on regulated tables — catches *every* path including the C++ modules and
     ad-hoc SQL, but captures DB-level context (DB user, not the application end-user) and adds trigger
     maintenance overhead. The application end-user identity must be threaded to the trigger (e.g., via a
     per-connection session variable set at request start).
   - **Application-layer logging** through a single write/service layer — richer user context, but
     requires routing all writes through it (a significant refactor from the current direct-query style),
     and does not cover the C++ backend unless it is likewise refactored.
   - A **hybrid** is common: DB triggers for guaranteed coverage + an app-set "current user" session
     variable so the trigger can attribute the change.
4. **Attribution.** Who (authenticated user), when (server time, from a trusted clock), what action, on
   which record.
5. **Retention & review.** Retain the audit trail at least as long as the underlying record; provide a
   read-only review/export UI for inspectors (an admin-only audit viewer).

**Effort:** High. This is the defining piece of work and should be designed first. Recommend a short
design doc that (a) enumerates the "regulated record" tables in scope, (b) picks trigger vs. app-layer
vs. hybrid, and (c) defines the audit schema (append-only, before/after).

### 3.2 Authentication & access control (§11.10(d)/(g), §11.300) — [CODE]

**Current state / issues:**
- **Unsalted SHA1** password storage (`login.php:237`, `adminusers.php:142` and `:299`,
  `register.php:162`, `publicdownloads.php:68`). Cryptographically broken; unacceptable for access
  control over regulated records.
- **SQL injection** in the login path (username/password interpolated directly into the query), and
  systemic SQLi elsewhere (per prior pentest findings).
- **No web session idle/absolute timeout** (only the token API `api2.php` implements idle expiry).
- **No account lockout / failed-login throttle** on web login (again, only `api2.php` throttles).
- **No password complexity or aging**, no formal unique-ID / no-reuse enforcement, no
  lost-credential deactivation workflow.
- Runtime is **EOL PHP 7.2** — an unsupported runtime is itself an audit finding.

**Required work:**
- Replace SHA1 with `password_hash()` (bcrypt or argon2id) + transparent rehash-on-login migration.
  **Model already exists in-repo:** the API-key path uses `password_hash`/`password_verify` with
  argon2id→bcrypt fallback (`adminusers.php:259`, `api2.php`).
- Remediate SQL injection in `login.php` and across the app (convert to prepared statements — a sweep is
  already underway on individual pages).
- Add web **session idle + absolute timeout**, **account lockout** and failed-login throttling,
  **password complexity + expiry**, unique/non-reused user IDs, and an account-deactivation workflow.
- Upgrade off EOL PHP.

**Effort:** Medium. Overlaps with existing security-hardening debt, so it pays down two obligations at once.

### 3.3 Record protection & retention (§11.10(b)/(c)) — [BOTH]

- Define and document a **retention policy**; ensure backup/restore is validated and periodically tested.
- **Review the timeseries partitioning + yearly MariaDB purge event** against retention requirements so
  regulated data is not auto-deleted.
- Formalize the **record-copy/export** capability (human-readable + electronic) as a validated function
  for producing inspection copies.

### 3.4 Operational & device checks (§11.10(f)/(h)) — [BOTH]

- Document and, where needed, enforce **workflow sequencing** for regulated processes.
- Add **source-of-input checks** where applicable (e.g., trusted/known DICOM senders, verified ingest
  sources).

### 3.5 Procedural obligations (§11.10(a)/(i)/(j)/(k)) — [PROCESS]

Not code, but required for compliance:
- **Validation package:** requirements spec, risk assessment, IQ/OQ/PQ protocols + executed evidence,
  requirements-to-test traceability.
- **SOPs:** system security & access management, backup/restore & retention, change control,
  audit-trail review, incident response, training.
- **Training records** for developers, administrators, and users.
- **Controlled documentation** (this analysis, SOPs, validation docs) under revision control with
  distribution/access controls.

---

## 4. Suggested phasing

1. **Auth hardening + SQLi remediation + PHP upgrade** (§11.10(d)/(g), §11.300). Foundational; also clears
   standing security debt. Reuse the existing `password_hash` API-key pattern.
2. **Audit-trail design + implementation** (§11.10(e)). The heavy lift. Start with a design doc
   (scope tables, trigger vs. app-layer vs. hybrid, append-only before/after schema), then build,
   then add the admin audit-review/export UI.
3. **Retention & backup formalization; record-copy/export validation** (§11.10(b)/(c)).
4. **Operational/device checks** (§11.10(f)/(h)).
5. **Validation program & SOPs** (§11.10(a)/(i)/(j)/(k)) — run in parallel from the start; QA/regulatory owned.

---

## 5. Compliance checklist (electronic records)

- [ ] Scoping memo recorded: electronic records only; no e-signatures; NiDB not used for signed records.
- [ ] **Audit trail:** append-only store; no UPDATE/DELETE on audit rows; mutable-timestamp removed.
- [ ] **Audit trail:** before/after values captured for create/modify/delete.
- [ ] **Audit trail:** coverage of all regulated write paths (PHP **and** C++ backend).
- [ ] **Audit trail:** end-user attribution + trusted timestamp.
- [ ] **Audit trail:** retained ≥ record lifetime; admin review/export UI.
- [ ] Passwords hashed with bcrypt/argon2id; SHA1 migrated out.
- [ ] SQL injection remediated (login + app-wide prepared statements).
- [ ] Web session idle + absolute timeout.
- [ ] Account lockout / failed-login throttling on web login.
- [ ] Password complexity + expiry; unique, non-reused IDs; lost-credential deactivation.
- [ ] Off EOL PHP; supported runtime.
- [ ] Retention policy defined; timeseries auto-purge reviewed against retention.
- [ ] Backup/restore validated and periodically tested.
- [ ] Record-copy/export validated for inspection (human-readable + electronic).
- [ ] Workflow sequencing checks documented/enforced where required.
- [ ] Source-of-input (device) checks where applicable.
- [ ] Validation package (requirements, IQ/OQ/PQ, traceability).
- [ ] SOPs (security, backup/retention, change control, audit review, training).
- [ ] Training records.
- [ ] Controlled-documentation process.

---

## 6. Caveats

- This is a **technical gap analysis**, not a compliance certification or legal/regulatory advice.
- "Part 11 compliant" describes the **deployed system + operating organization**, not the source tree; a
  QA/regulatory owner must run the validation/SOP program and make the compliance determination.
- Several items (unsalted SHA1, systemic SQLi, no CSRF/rate-limiting, EOL PHP) are simultaneously Part 11
  blockers and general security debt — remediating them advances both goals.
- Code locations cited (file:line) reflect the repository at the date of this draft and may drift.
