#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: this script must run inside the kratos Git repository." >&2
  exit 1
fi

BRANCH="$(git rev-parse --abbrev-ref HEAD | tr '/ ' '--')"
COMMIT="$(git rev-parse --short HEAD)"
DIST_DIR="$ROOT_DIR/dist"
PACKAGE_NAME="kratos-${BRANCH}-${COMMIT}.zip"
PACKAGE_PATH="$DIST_DIR/$PACKAGE_NAME"

mkdir -p "$DIST_DIR"

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "Warning: working tree has uncommitted changes. The package will use committed HEAD only." >&2
fi

rm -f "$PACKAGE_PATH"

git archive \
  --format=zip \
  --output="$PACKAGE_PATH" \
  --prefix=kratos/ \
  HEAD \
  ':(exclude).gitignore' \
  ':(exclude).github' \
  ':(exclude).eslintignore' \
  ':(exclude).prettierignore' \
  ':(exclude)AGENTS.md' \
  ':(exclude)CUSTOM_MODULES.md' \
  ':(exclude)DEPLOYMENT.md' \
  ':(exclude)docs' \
  ':(exclude)custom/module-disabled-snippets.php' \
  ':(exclude)scripts'

echo "Created deployment package:"
echo "$PACKAGE_PATH"
echo
echo "Upload the contents of the kratos/ folder to wp-content/themes/kratos/."
