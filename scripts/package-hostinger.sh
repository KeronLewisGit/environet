#!/usr/bin/env bash
# Builds a production zip that extracts straight into Hostinger's public_html/.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/dist"
STAGE="$OUT/stage"
APP="$STAGE/environet-app"
ZIP="$OUT/environet-hostinger.zip"

cd "$ROOT"
echo "→ Building front-end assets"
npm run build >/dev/null

echo "→ Staging application"
rm -rf "$STAGE" "$ZIP"; mkdir -p "$APP"
rsync -a --delete \
  --exclude '.git' --exclude 'node_modules' --exclude 'tests' --exclude 'dist' --exclude 'deploy' --exclude 'scripts' \
  --exclude '/public' --exclude '/vendor' --exclude '.env' --exclude 'database/database.sqlite' \
  --exclude 'storage/logs/*' --exclude 'storage/framework/cache/data/*' --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' --exclude 'storage/framework/testing/*' --exclude 'storage/app/private/*' --exclude 'storage/app/public/*' --exclude 'bootstrap/cache/*' \
  --exclude '.phpunit*' --exclude 'public/_frame.html' --exclude 'phpunit.xml' --exclude 'boost.json' --exclude 'AGENTS.md' --exclude 'CLAUDE.md' --exclude 'README.md' \
  "$ROOT/" "$APP/"

echo "→ Installing production PHP dependencies (PHP 8.3 compatible)"
cp "$ROOT/composer.json" "$ROOT/composer.lock" "$APP/"
( cd "$APP" && composer install --no-dev --optimize-autoloader --no-interaction --quiet --ignore-platform-req=php )

mkdir -p "$APP/storage/framework/"{cache/data,sessions,views,testing} "$APP/storage/logs" "$APP/storage/app/public" "$APP/storage/app/private/"{solicitations,vendors,cvs,rooms} "$APP/bootstrap/cache"
touch "$APP/storage/logs/.gitkeep"
cp "$ROOT/deploy/env.production.example" "$APP/.env.example"
cp "$ROOT/deploy/app.htaccess" "$APP/.htaccess"

echo "→ Staging public files"
cp -R "$ROOT/public/build" "$STAGE/build"
cp -R "$ROOT/public/img" "$STAGE/img"
# Pre-generated capability statement PDF (npm run capability), if built.
[ -d "$ROOT/public/downloads" ] && cp -R "$ROOT/public/downloads" "$STAGE/downloads"

cp "$ROOT/public/favicon.svg" "$STAGE/favicon.svg"
[ -f "$ROOT/public/favicon.ico" ] && cp "$ROOT/public/favicon.ico" "$STAGE/favicon.ico"
cp "$ROOT/deploy/index.php" "$STAGE/index.php"
cp "$ROOT/deploy/.htaccess" "$STAGE/.htaccess"
cp "$ROOT/deploy/robots.txt" "$STAGE/robots.txt"
cp "$ROOT/deploy/setup.php" "$STAGE/setup.php"

echo "→ Zipping"
( cd "$STAGE" && zip -qr "$ZIP" . -x '*.DS_Store' )
rm -rf "$STAGE"
echo "✓ Package ready: $ZIP ($(du -h "$ZIP" | cut -f1))"
