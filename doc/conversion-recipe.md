# PHP conversion recipe (prepared statements + PRG)

A compact, self-contained procedure for converting one PHP page to bound SQL
statements and/or the Post/Redirect/GET pattern. Read this first in a fresh
session so you don't have to re-derive the conventions. Trackers:
`doc/prepared-statements.md` and `doc/post-redirect-get.md`.

**Do one file per session, then `/clear`.** Context from prior files adds nothing
and multiplies token cost. The trackers are the durable memory between sessions.

---

## Golden rules (from CLAUDE.md)

- **Live site is source of truth.** Edit `/var/www/html/<file>` first, then copy
  to `src/web/<file>`. Keep them byte-identical.
- **The user commits.** Leave changes staged; never `git commit`/`push`.
- **LF line endings only.** If a touched file is CRLF/mixed, strip `\r` as part of the change.
- **PHP 7.2 *and* 8 must both run.** Guard `count()/min()/max()` on null, division by
  zero, `number_format("")`, undefined array keys (`?? ''` / `?? 0`).
- **Never hit the live DB without permission** (even read-only).
- **`rtk` can fabricate `git`/`ls`/`find`/lint output.** For real results run Bash with
  `dangerouslyDisableSandbox: true`.

---

## Per-file workflow

1. **Locate, don't slurp.** Find the query sites instead of reading the whole file:
   ```bash
   grep -n "MySQLiQuery(" /var/www/html/<file>          # direct (candidate) queries
   grep -n 'method="post"' /var/www/html/<file>          # PRG surface
   ```
   Then `Read` ~15-line windows around each hit. Only read the whole file when the
   queries are deeply interdependent.
2. **Convert** each query with user input (see idioms below).
3. **Lint:** `php -l /var/www/html/<file>` (must say "No syntax errors detected").
4. **Sync + verify:**
   ```bash
   cp -f /var/www/html/<file> /home/nidb/nidb/src/web/<file>
   diff -q /var/www/html/<file> /home/nidb/nidb/src/web/<file>   # must be identical
   ```
5. **Recount + update trackers** (`grep -o "MySQLiQuery(" … | wc -l`, same for
   `MySQLiBoundQuery(`). Set the row and flip Done/PRG to ✅ when justified.
6. Report what changed; leave staged. `/clear`.

---

## What must be converted vs. left alone

Convert a query **only when it interpolates user input as a string or an
un-sanitized scalar.** Leave a direct `MySQLiQuery(` as-is when every interpolated
value is:
- **parameterless** (no variables), or
- an **`(int)`-cast scalar** (e.g. `where id = $id` after `$id = (int)$id`), or
- an **`intval()`-sanitized id list** (e.g. `implode(',', array_map('intval', $ids))`).

This matches the existing convention (some files carry comments like
`/* ints ... safe to inline */`). Mark **Done ✅** once the remaining direct queries
all fall into those safe buckets — the flag is a manual judgment, not "Direct == 0".

---

## Bound-statement idioms

`MySQLiBoundQuery($stmt, $file, $line, $sqlstring="", $params=[])` returns a
**buffered** `mysqli_result` (from `mysqli_stmt_get_result`) or `null` on error.
Because it's buffered, `mysqli_num_rows()` works **after** `mysqli_stmt_close()`.
The optional `$sqlstring`/`$params` are only for error-display; pass them on the
complex/dynamic queries so a failure prints the real SQL.

Bind-type letters: `i` int, `d` double, `s` string, `b` blob.

**Scalar / string:**
```php
$stmt = mysqli_prepare($GLOBALS['linki'], "select * from users where username = ?");
mysqli_stmt_bind_param($stmt, 's', $username);
$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
```

**LIKE:** build the wildcard string, then bind it (don't put `%` in the SQL):
```php
$search = '%' . $term . '%';
$stmt = mysqli_prepare($GLOBALS['linki'], "... where name like ?");
mysqli_stmt_bind_param($stmt, 's', $search);
```

**Do NOT pre-escape a value you bind.** If the old code had
`$v = mysqli_real_escape_string($linki, $v)`, drop the escape when binding `$v`
(otherwise backslashes get doubled).

**`IN (...)` with a variable-length list** — placeholders + spread:
```php
$ph     = implode(',', array_fill(0, count($ids), '?'));
$types  = str_repeat('i', count($ids));
$stmt   = mysqli_prepare($GLOBALS['linki'], "... where id in ($ph)");
mysqli_stmt_bind_param($stmt, $types, ...$ids);          // spread works on 7.2/8
$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, $ids);
```

**Nullable column** (blank → SQL NULL): compute `$v = trim($x) === '' ? null : $x;`
and bind it — a bound `null` becomes `NULL`, so you don't build `... = null` strings.

**NULL / non-NULL in one statement (build types + params dynamically):**
```php
$params = array(); $types = '';
$sql = "update t set col = ? where id = ?";
$types .= 's'; $params[] = $val;
$types .= 'i'; $params[] = $id;
$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, $params);
mysqli_stmt_close($stmt);
```

## Identifiers can't be bound

Table and column **names** are never parameters. When they come from input:
- Pick the real column via a **`switch`/whitelist**, or validate against an
  allowlist (`in_array($col, $allowed, true)`).
- Modality → series table: use `GetSeriesTableName($modality)` (validates
  `^[a-z0-9]+$`, returns `''` on reject).
- **`SHOW ...` can't be prepared on all MariaDB versions** — query
  `information_schema.tables` / `.columns` with a *bound* (whitelist-validated)
  name instead.

## Insert id / affected rows
`mysqli_insert_id($GLOBALS['linki'])` and `mysqli_affected_rows($GLOBALS['linki'])`
still work after a bound execute; read them **before** `mysqli_stmt_close()`.

---

## PRG (Post/Redirect/GET) idiom

For a page that handles a **mutating** POST form. Reference impl: `adminusers.php`
+ `functions.php` (`RedirectTo` / `ShowFlashMessage`). Requires `ob_start()` near
the top (before any output).

```php
// process the POST, capture user-facing output as the flash, then redirect to a GET
ob_start();
Handler(...);                       // does the work, echoes its success/error message
$_SESSION['flash'] = ob_get_clean();
RedirectTo("thispage.php");         // 302 to self — refresh/Back can't re-submit
```
At the top of the page's display/list render: `ShowFlashMessage();`.
Mark the file ✅ in `doc/post-redirect-get.md` once every `method="post"` handler is wrapped.

---

## Quick sanity checks before finishing
- `php -l` clean.
- `diff -q` live vs repo → identical.
- No CRLF introduced (`grep -c $'\r' <file>` → 0).
- No stale references to variables you removed (`grep -n '\$oldvar' <file>`).
- Tracker row updated; Done/PRG flag set only if actually justified.
