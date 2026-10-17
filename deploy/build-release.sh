#!/usr/bin/env bash
# Build a production release zip for shared cPanel hosting.
# Usage (Git Bash on Windows, macOS or Linux):  bash deploy/build-release.sh
# Output: release/josveggies-<date>.zip  — upload and extract into ~/josveggies on the server.
set -euo pipefail

cd "$(dirname "$0")/.."
STAMP=$(date +%Y%m%d-%H%M)
OUT="release/josveggies-${STAMP}"

rm -rf release && mkdir -p "$OUT"

echo "→ Building front-end assets"
npm ci
npm run build

echo "→ Copying application files"
git ls-files -z | xargs -0 -I{} cp --parents {} "$OUT"
cp -r public/build "$OUT/public/"

echo "→ Installing production PHP dependencies"
(cd "$OUT" && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist)

echo "→ Removing development-only files"
rm -rf "$OUT/tests" "$OUT/.github" "$OUT/.claude" "$OUT/.mcp.json" "$OUT/boost.json" "$OUT/phpunit.xml" "$OUT/phpstan.neon" \
       "$OUT/node_modules" "$OUT/deploy/build-release.sh"

echo "→ Zipping"
(cd release && zip -qr "josveggies-${STAMP}.zip" "josveggies-${STAMP}")
echo "✓ release/josveggies-${STAMP}.zip"
