# NiDB project instructions

Project-specific conventions for working in this repo. These are loaded automatically at the start of each session.

## Git / commits
- **The user does all git commits.** Do not commit or push unless explicitly asked.
- Make and sync file changes; leave them staged in the working tree for the user to review and commit.

## Web (PHP) workflow
- The **live site is the source of truth for editing**: edit files in `/var/www/html/<file>` first.
- After editing, always: (1) lint with `php -l /var/www/html/<file>`, then (2) copy to the repo with `cp -f /var/www/html/<file> /home/nidb/nidb/src/web/<file>`, then (3) verify with `diff -q`.
- `/var/www/html` is outside the git repo; `src/web` is the committed copy. Keep them identical.

## Line endings
- **Always use LF (Unix) line endings.** Never introduce CRLF. Some legacy files are CRLF/mixed — when touching one, convert it to LF (strip `\r`) as part of the change.

## PHP version support
- Code must run on **both PHP 7.2 (dev) and PHP 8 (production)**. Watch for PHP 8 fatals that were only warnings in 7.x:
  - `count()`, `min()`, `max()`, `sort()`, `array_diff()` on `null`/non-array → fatal `TypeError`. Initialize arrays before loops that populate them.
  - `min([])` / `max([])` on an empty array → fatal `ValueError`. Guard with a count check.
  - Division by zero (`/`, `%`) → fatal `DivisionByZeroError`. Guard divisors.
  - `number_format("")` (non-numeric string) → fatal `TypeError`. (`null` is only a deprecation.)
  - Undefined array keys / `htmlspecialchars(null)` → warnings/deprecations; add `?? ''` / `?? 0` guards.

## SQL
- **Use prepared/bound statements when refactoring or writing queries** that include user input: `mysqli_prepare($GLOBALS['linki'], $sql)` + `mysqli_stmt_bind_param()` + `MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, $params)`. Use `MySQLiQuery($sql, __FILE__, __LINE__)` for parameter-less queries.
- `SHOW` statements can't be prepared on all MariaDB versions — use `information_schema` tables instead.
- **Do not access the live database without permission.** Ask before running any `mysql`/query against it, even read-only inspection.
- Local DB access for inspection (only once permitted): `mysql -unidb -ppassword nidb`.
- Config lives in `/nidb/nidb.cfg` (custom `[key] = value` format, not standard INI); PHP reads it into `$GLOBALS['cfg']`.

## C++ (nidb binary) workflow
- Edit C++ sources under `src/nidb/*.cpp` directly. **The user compiles** — do not attempt to build unless asked.

## Tooling note
- The `rtk` shell proxy can fabricate output for `git`/`ls`/`find`. When you need real filesystem/git/mysql results, run Bash with `dangerouslyDisableSandbox: true`.
