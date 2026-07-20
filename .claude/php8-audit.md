# PHP8 Compatibility Audit
Generated: 2026-05-15
Source files: /mnt/l/*.php (150 files)

## Status legend
- [ ] = needs fix
- [x] = done

---

## calendar_appointments.php
- [x] Line 472: `eregi_replace()` — removed in PHP7. Replace with `preg_replace()` + `i` flag.

---

## GetRCInst.php
- [x] Line 98: `$IN{0}` and `$IN{1}` — curly brace array access deprecated PHP7.4, removed PHP8. Replace with `$IN[0]`, `$IN[1]`.

---

## redcap2ADO.php
- [x] Line 202: `$FL{0}` and `$FL{1}` — curly brace array access deprecated PHP7.4, removed PHP8. Replace with `$FL[0]`, `$FL[1]`.
  - Lines 198–199 are commented out (no action needed there).

---

## redcapmapping.php
- [x] Line 703: `strpos($colsname, "_id") == false` — loose comparison. `strpos()` returns `0` (falsy) when match is at position 0, so `== false` is a bug. Replace with `=== false`.

---

## search.php
- [x] Line 6226: `strpos($nfsdir," ") != false` — loose comparison. Replace with `!== false`.

---

## setup.php
- [x] Line 1218: `str_starts_with()` — PHP8.0+ only. Replaced with `strpos($orphan, 'deprecated_') === 0` for PHP7 compatibility.

---

## Notes on false positives
- `each()` hits — all jQuery `.each()` in JavaScript blocks, not PHP `each()`. No action needed.
- `${...}` interpolation hits in mriqc.php / visualization.php — JavaScript template literals, not PHP. No action needed.
- `create_function` in adminmodules.php — already commented out. No action needed.
- `redcaptonidb.php` lines 164/172 — bitwise `&` vs logical `&&`. These are not PHP8 issues per se; review separately if logic bugs are suspected.
