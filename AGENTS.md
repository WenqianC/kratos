# AGENTS.md

This file tells Codex how to work on this WordPress theme project.

## Project

- This repository is a customized WordPress theme based on `seatonjiang/kratos`.
- The production theme path is expected to be `wp-content/themes/kratos/`.
- Custom code is mainly under `custom/`.
- Read `CUSTOM_MODULES.md` before changing custom modules.
- Read `DEPLOYMENT.md` before preparing deployment files.
- Read `docs/DECISIONS.md` when prior design decisions may matter.

## Server Constraints

Production is small. Treat performance as a hard requirement.

- Web server: 2 GB RAM, 2 vCPU, 60 GB SSD.
- Media FTP server: 2 GB RAM, 2 vCPU, 60 GB SSD.
- Database: MySQL 8.4.7, 40 GB SSD.
- Front edge: free Lei Chi WAF, 2 GB RAM, 2 vCPU, 60 GB SSD.

Do not add:

- Unbounded `WP_Query`, `get_posts`, `get_users`, `get_comments`, or raw SQL.
- Full-table scans during normal page views.
- High-frequency AJAX polling.
- Large file scans during requests.
- Heavy cron jobs or batch tasks without explicit approval.

Always use explicit limits such as `posts_per_page`, `number`, or SQL `LIMIT`.

## Coding Rules

- Prefer small, reversible changes.
- Preserve existing behavior unless the user explicitly asks to change it.
- Keep `custom/custom.php` as a module loader only.
- If code looks strange, ask the user before changing its behavior.
- Put new custom behavior in a focused `custom/module-*.php` file.
- Ask the user before commenting-out code or disabled snippets.
- Keep old disabled snippets in `custom/module-disabled-snippets.php` when they may be useful later.
- Do not broaden upload restrictions beyond the user's stated intent without asking.
- Use WordPress escaping and sanitization APIs for output/input.
- Avoid large refactors unrelated to the current task.
- Always ask the user to test the code in server first before pushing commits.
- Do not create or modify files outside this repository unless the user explicitly approves.
- Put temporary local test scripts under `tests/tmp/` and make sure they are ignored by Git.
- Record significant design decisions in `docs/DECISIONS.md` when useful.

## Ask First

Ask the user before:

- Changing user-facing behavior.
- Changing permissions, privacy, registration, password reset, or upload policy.
- Adding new dependencies, plugins, cron tasks, or background jobs.
- Running destructive Git commands.
- Merging upstream changes from `seatonjiang/kratos`.
- Deploying or giving instructions that may replace many server files.

## Verification

For PHP changes, run syntax checks on changed PHP files:

```bash
php -d error_reporting='E_ALL & ~E_DEPRECATED' -l path/to/file.php
```

For broad PHP changes, run:

```bash
git ls-files '*.php' | xargs -n1 php -d error_reporting='E_ALL & ~E_DEPRECATED' -l
```

Always run:

```bash
git diff --check
git status --short --branch
```

If deployment packaging is touched, run:

```bash
./scripts/build-deploy-package.sh
```

Then verify the package does not include development files:

```bash
zipinfo -1 dist/*.zip | rg '(^|/)\.git|(^|/)\.github|(^|/)\.idea|AGENTS|CUSTOM_MODULES|DEPLOYMENT|module-disabled-snippets|(^|/)scripts/|(^|/)dist/|DS_Store'
```

The `rg` command should return no package contents.

## Git Workflow

- Work from a feature branch, not directly on `master`, unless the user asks.
- Commit focused changes with clear messages.
- Write every commit title and description in Chinese.
- Every commit description must include these three numbered sections:
  - `1. 简要描述`：概括本次提交的目的和涉及范围。
  - `2. 修改前的表现`：说明修改前的行为、问题或限制。
  - `3. 修改后的表现`：说明修改后的行为，以及明确保留不变的重要行为。
- Push only after checks pass or after telling the user what could not be checked.
- Do not merge into `master` without user approval.

## Deployment

- Prefer uploading only changed files for small updates.
- For larger updates, build a clean package and upload its extracted `kratos/` contents.
- Do not upload `.git`, `.github`, `.idea`, `dist`, scripts, or local metadata to production.
- Never delete the production theme folder unless the user has a complete backup and explicitly approves.
