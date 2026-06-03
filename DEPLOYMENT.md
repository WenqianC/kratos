# Deployment Guide

Last updated: 2026-06-03

This project is a WordPress theme. The production server is small, so deploy in small, reversible steps.

Server baseline:

- Web server: 2 GB RAM, 2 vCPU, 60 GB SSD.
- Media FTP server: 2 GB RAM, 2 vCPU, 60 GB SSD.
- Database: MySQL 8.4.7, 40 GB SSD.
- Front edge: free Lei Chi WAF, 2 GB RAM, 2 vCPU, 60 GB SSD.

## Recommended Current Method

Use GitHub for version tracking and FileZilla for production upload.

For small changes, upload only the changed files. This has the lowest risk and the fastest rollback.

For larger changes, build a clean deployment package with:

```bash
./scripts/build-deploy-package.sh
```

The generated zip is placed in `dist/`.

Do not upload these development files or folders to production:

```text
.git/
.github/
.idea/
.gitignore
.DS_Store
dist/
scripts/
DEPLOYMENT.md
CUSTOM_MODULES.md
```

## Before Deployment

Run these checks locally:

```bash
git status --short --branch
git log --oneline -5
```

Confirm:

- You are on the intended branch.
- The working tree is clean.
- The latest commit is the version you want to deploy.
- The changed files are understood.

For PHP changes, run syntax checks on the changed PHP files, for example:

```bash
php -d error_reporting='E_ALL & ~E_DEPRECATED' -l custom/custom.php
```

## FileZilla Deployment

Production theme path is expected to be:

```text
wp-content/themes/kratos/
```

Small change deployment:

1. Back up the files that will be overwritten.
2. Upload only the changed files to the same relative paths.
3. Do not delete the whole `kratos` folder.
4. Do not upload `.git`, `.github`, `.idea`, `dist`, or local metadata.

Clean package deployment:

1. Run `./scripts/build-deploy-package.sh`.
2. Extract the generated zip locally or upload its contents through FileZilla.
3. Upload files into `wp-content/themes/kratos/`.
4. Keep a copy of the previous server files until testing passes.

## Smoke Test After Deployment

Check these pages and actions:

- Home page and archive/list pages.
- Single post page.
- Post author block and author link.
- Login page.
- Registration form.
- Lost-password form.
- Post edit page if editor/admin behavior changed.
- Media upload if upload logic changed.
- Comments admin if comment logic changed.
- "我的收藏" add/remove/list behavior if bookmark logic changed.
- Tag blocklist settings and front-end hiding if blocklist logic changed.

Also watch server behavior briefly:

- No long 500-error period.
- No obvious CPU/RAM spike.
- No repeated AJAX loop.
- No unusually slow admin page.

## Rollback

Small change rollback:

1. Upload the backed-up old files back to the same paths.
2. Clear caches if needed.
3. Re-test the affected pages.

Git-based local rollback for a specific file:

```bash
git show <old-commit>:path/to/file.php > /tmp/file.php
```

Then upload that restored file through FileZilla.

Avoid destructive commands on production. Do not delete the whole theme folder unless you have a complete backup and a tested replacement.

## Production Cleanup

If these folders exist on the production theme server, remove them after a backup:

```text
.git/
.github/
.idea/
```

They are not needed for WordPress runtime and can expose development history or local editor settings.

## Future Automation Path

Recommended sequence:

1. Keep using FileZilla for manual upload.
2. Use `scripts/build-deploy-package.sh` to generate a clean package.
3. When the manual package flow is stable, consider SFTP/rsync automation.
4. Only consider server-side `git pull` if the production theme directory is intentionally managed as a clean Git worktree.

Do not start with fully automatic production deployment. The current manual package flow is safer for this site.
