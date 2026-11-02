# ComercioSocial Enterprise 6.1 — Stabilization pass (proposed)

This branch is an incremental, non-destructive stability update built on v6.0.

## Changes
- `app_version()` now reports `6.1.0` for the proposed release.
- Inventory now preserves `NULL` stock when the seller leaves the stock field empty. An explicit `0` remains zero.
- Price input is rejected if missing, rather than silently interpreting an empty price as zero.
- Reservation state updates use the affected-row count to detect simultaneous changes instead of always reporting success.
- A GitHub Actions workflow checks PHP syntax on PHP 8.2 and 8.3.
- `.gitignore` prevents accidental commits of local configuration, session/install markers, and uploaded content.

## Manual tests required before merge
1. Run the app on XAMPP/PHP 8.2 with MySQL/MariaDB.
2. Verify seller inventory for stock `NULL`, `0` and positive numbers; empty price must be rejected.
3. Test reservation acceptance/rejection/completion, and simultaneous updates from two browsers.
4. Test installers, template activation, uploads, public catalog and AdminCP.
5. Verify server access rules for private directories and backup folders.

## Known limitations
A reservation acceptance is **not** stock allocation and does not collect payment. Full stock locking, a transactional order model, and deployment security audit are separate milestones. The CI workflow performs syntax checks only, not end-to-end tests.
