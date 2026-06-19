# Changelog - brilkic

## 0.0.2 : 19 Jun 2026
- `template()` and `run_route()` now require an `array` for their data argument
  (previously `mixed`), so routes and templates can index `$data`/`$vars`
  without an `is_array()` guard. Passing a non-array now raises a `TypeError`.
- Add tests for the CLI command dispatch and init output

## 0.0.1 : 16 Jun 2026
- First release
