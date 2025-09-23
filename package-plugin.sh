#!/bin/bash

set -euo pipefail

PLUGIN_SLUG="courier-finance-plugin"
ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="$ROOT_DIR/build"
STAGE_DIR="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_PATH="$BUILD_DIR/$PLUGIN_SLUG.zip"

echo "📦 Packaging $PLUGIN_SLUG (non-destructive)..."

rm -rf "$BUILD_DIR"
mkdir -p "$STAGE_DIR"

echo "➕ Copying plugin files to staging..."
rsync -a --delete \
  --exclude ".git" \
  --exclude ".github" \
  --exclude "build" \
  --exclude "node_modules" \
  --exclude "*.map" \
  --exclude "*.log" \
  --exclude "*.md" \
  --exclude "*.MD" \
  --exclude "assets/previous_waybills" \
  --exclude "assets/customers.*" \
  --exclude "*.sql" \
  --exclude "vendor/**/tests" \
  --exclude "vendor/**/test" \
  --exclude "vendor/**/docs" \
  --exclude "vendor/**/doc" \
  --exclude "vendor/**/examples" \
  --exclude "vendor/dompdf/dompdf-backup" \
  "$ROOT_DIR/" "$STAGE_DIR/"

echo "🧼 Removing stray .DS_Store files..."
find "$STAGE_DIR" -name ".DS_Store" -delete || true

echo "🗜️  Optimizing CSS with PurgeCSS (if available)..."
if command -v npx >/dev/null 2>&1; then
  CSS_INPUT="$STAGE_DIR/assets/css/frontend.css"
  CSS_OUTPUT="$STAGE_DIR/assets/css/frontend.min.css"
  if [ -f "$CSS_INPUT" ]; then
    npx --yes purgecss \
      --css "$CSS_INPUT" \
      --content "$STAGE_DIR/**/*.php" "$STAGE_DIR/**/*.js" \
      --safelist ":root,html,body,show,hide,hidden,inline,block,flex,grid,table,thead,tbody,tr,td,th" \
      --output "$STAGE_DIR/assets/css" || echo "PurgeCSS failed or found nothing; keeping original CSS."
    if [ -f "$CSS_OUTPUT" ]; then
      mv "$CSS_OUTPUT" "$CSS_INPUT"
      echo "✅ Purged CSS written to assets/css/frontend.css"
    fi
  fi
else
  echo "⚠️  npx not found; skipping PurgeCSS."
fi

echo "🧪 Pruning composer dev files inside vendor (docs/tests/examples already excluded)..."
find "$STAGE_DIR/vendor" -type d \( -name tests -o -name test -o -name docs -o -name doc -o -name examples \) -prune -exec rm -rf {} + 2>/dev/null || true

echo "🧾 Creating ZIP..."
rm -f "$ZIP_PATH"
(
  cd "$BUILD_DIR"
  zip -qr "$ZIP_PATH" "$PLUGIN_SLUG" -x "**/.DS_Store"
)

echo "✅ Done: $ZIP_PATH"
du -sh "$ZIP_PATH" || true


