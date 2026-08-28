#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SLUG="seoulcommerce-payment-gateway-for-tosspayments"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}"
WORK_DIR="${TMPDIR:-/tmp}/${SLUG}-svn"

VERSION="$(grep -m1 'Stable tag:' "${ROOT_DIR}/readme.txt" | awk '{print $3}')"
TAG="tags/${VERSION}"

if [[ -z "${VERSION}" ]]; then
  echo "Could not read version from readme.txt Stable tag."
  exit 1
fi

echo "Preparing WordPress.org release ${VERSION}..."

if [[ -f "${ROOT_DIR}/compile-translations.py" ]]; then
  python3 "${ROOT_DIR}/compile-translations.py"
fi

rm -rf "${WORK_DIR}"
mkdir -p "${WORK_DIR}"

if [[ ! -d "${WORK_DIR}/svn" ]]; then
  svn checkout "${SVN_URL}" "${WORK_DIR}/svn"
else
  svn update "${WORK_DIR}/svn"
fi

RSYNC_EXCLUDES=(
  --exclude '.git'
  --exclude '.gitignore'
  --exclude 'node_modules'
  --exclude 'src'
  --exclude 'package.json'
  --exclude 'package-lock.json'
  --exclude 'webpack.config.js'
  --exclude 'composer.json'
  --exclude 'compile-translations.py'
  --exclude 'deploy-wordpress-org.sh'
  --exclude 'build-wordpress-org.sh'
  --exclude 'build.sh'
  --exclude 'build-*.sh'
  --exclude 'quick-build.sh'
  --exclude '*.zip'
  --exclude '*.pdf'
  --exclude 'CHANGELOG.md'
  --exclude 'README.md'
  --exclude '.DS_Store'
)

rsync -a --delete "${RSYNC_EXCLUDES[@]}" \
  "${ROOT_DIR}/" "${WORK_DIR}/svn/trunk/"

if svn info "${WORK_DIR}/svn/${TAG}" >/dev/null 2>&1; then
  echo "SVN tag ${TAG} already exists. Remove it first if you need to re-release."
  exit 1
fi

svn copy "${WORK_DIR}/svn/trunk" "${WORK_DIR}/svn/${TAG}"

echo
echo "Ready to commit ${VERSION} to WordPress.org."
echo "Review changes:"
echo "  cd ${WORK_DIR}/svn && svn status"
echo
read -r -p "Commit to WordPress.org SVN now? [y/N] " CONFIRM
if [[ "${CONFIRM}" =~ ^[Yy]$ ]]; then
  svn commit "${WORK_DIR}/svn" -m "Tagging version ${VERSION}."
  echo "Submitted ${VERSION}. WordPress.org usually updates within a few minutes."
else
  echo "Skipped commit. Run this when ready:"
  echo "  svn commit ${WORK_DIR}/svn -m \"Tagging version ${VERSION}.\""
fi
