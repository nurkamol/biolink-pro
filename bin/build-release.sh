#!/usr/bin/env bash
# Build a production-ready release zip for BioLink Pro.
#
# Output: dist/biolink-pro-vX.Y.Z.zip (top-level dir inside the zip is `biolink-pro/`).
#
# Usage:
#   bin/build-release.sh                    # version read from plugin.php
#   bin/build-release.sh --skip-deps        # reuse existing vendor/ and assets/admin/
#   PHP=/path/to/php COMPOSER=/path/to/composer.phar bin/build-release.sh
#
# Requirements: php >=8.2, composer, node + npm, rsync, zip.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PHP_BIN="${PHP:-php}"
COMPOSER_BIN="${COMPOSER:-composer}"
SLUG="biolink-pro"

skip_deps=0
for arg in "$@"; do
    case "$arg" in
        --skip-deps) skip_deps=1 ;;
        *) echo "Unknown argument: $arg" >&2; exit 1 ;;
    esac
done

VERSION="$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' plugin.php | head -1 | awk '{print $3}')"
if [[ -z "${VERSION}" ]]; then
    echo "Could not parse Version from plugin.php header." >&2
    exit 1
fi

echo "==> Building ${SLUG} v${VERSION}"

BUILD_DIR="${ROOT_DIR}/build"
STAGE_DIR="${BUILD_DIR}/${SLUG}"
DIST_DIR="${ROOT_DIR}/dist"
ZIP_NAME="${SLUG}-v${VERSION}.zip"

rm -rf "${BUILD_DIR}"
mkdir -p "${STAGE_DIR}" "${DIST_DIR}"

if [[ "${skip_deps}" -eq 0 ]]; then
    echo "==> Installing production Composer dependencies"
    "${PHP_BIN}" "${COMPOSER_BIN}" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

    echo "==> Installing npm dependencies + building admin bundle"
    if [[ -f package-lock.json ]]; then
        npm ci --no-audit --no-fund
    else
        npm install --no-audit --no-fund
    fi
    npm run build
fi

if [[ ! -f assets/admin/main.asset.php ]]; then
    echo "Admin assets are missing — re-run without --skip-deps to build them." >&2
    exit 1
fi

echo "==> Staging files into ${STAGE_DIR}"
rsync -a --delete \
    --exclude-from="${ROOT_DIR}/.distignore" \
    "${ROOT_DIR}/" "${STAGE_DIR}/"

echo "==> Restoring dev Composer dependencies"
if [[ "${skip_deps}" -eq 0 ]]; then
    "${PHP_BIN}" "${COMPOSER_BIN}" install --prefer-dist --no-interaction >/dev/null
fi

echo "==> Creating ${ZIP_NAME}"
( cd "${BUILD_DIR}" && zip -rq "${DIST_DIR}/${ZIP_NAME}" "${SLUG}" )

ZIP_SIZE="$(du -h "${DIST_DIR}/${ZIP_NAME}" | awk '{print $1}')"
echo "==> Done: dist/${ZIP_NAME} (${ZIP_SIZE})"
