# Decisions

This file records dated project decisions. Keep entries short and actionable.

## 2026-06-08

### Keep custom behavior modular

- Keep `custom/custom.php` as a loader only.
- Put custom behavior in focused `custom/module-*.php` files.
- Keep disabled or occasional-use snippets in `custom/module-disabled-snippets.php` instead of silently deleting them.

### User blocklist module

- Rename the old Tag blocklist module to `custom/module-blocklist.php`.
- Keep Tag blocklist behavior and add author blocklist behavior in the same module.
- Store blocked authors as user IDs in `dn_blocked_authors`.
- Show author nicknames in the admin page, not visible IDs.
- If an author account was deleted, show a muted `原作者账号已删除` placeholder and still allow removal.
- Do not allow blocking author ID `48008`.
- Limit blocked authors to 50.
- Keep blocklist hiding on the front end. Do not add database-level filtering for normal page views.

## 2026-06-10

### Keep documentation responsibilities separate

- Keep working rules and server constraints in `AGENTS.md`.
- Keep the custom module inventory in `CUSTOM_MODULES.md`.
- Keep deployment instructions in `DEPLOYMENT.md`.
- Keep only dated design decisions and rationale in this file.
