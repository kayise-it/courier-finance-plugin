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
  --exclude "test_*.php" \
  --exclude "simulate_*.php" \
  --exclude "debug_*.php" \
  --exclude "final_*.php" \
  --exclude "verify_*.php" \
  --exclude "unified-table-examples.php" \
  --exclude "cleanup_*.php" \
  --exclude "fix-waybill-issues.php" \
  --exclude "fonts" \
  --exclude "**/*.ttf" \
  --exclude "**/*.otf" \
  --exclude "**/test_*" \
  --exclude "**/simulate_*" \
  --exclude "**/debug_*" \
  --exclude "**/unified-*" \
  --exclude "waybill_excel" \
  --exclude "assets/seed_full.sql" \
  --exclude "export_*.php" \
  --exclude "fix_*.php" \
  --exclude "generate_*.py" \
  --exclude "*.xlsx" \
  --exclude "test-data.json" \
  --exclude "hook-registered.txt" \
  "$ROOT_DIR/" "$STAGE_DIR/"

echo "🧼 Removing stray .DS_Store files..."
find "$STAGE_DIR" -name ".DS_Store" -delete || true

echo "🎨 Compiling Tailwind CSS for production..."
# Compile Tailwind CSS to include all responsive grid classes
if command -v npx >/dev/null 2>&1; then
  npx tailwindcss -i assets/css/tailwind.css -o assets/css/tailwind-full.css --minify
  echo "✅ Tailwind CSS compiled successfully"
else
  echo "⚠️  npx not found; Tailwind compilation skipped"
fi

echo "🚫 Skipping PurgeCSS to preserve all CSS classes and layouts"
# PurgeCSS completely disabled - keeps all CSS intact to prevent any layout destruction
# This ensures grid layouts, delivery cards, and all styling remain functional

echo "🔧 Checking for missing dependencies (production deployment)..."

# Check if critical PHP files can work without Composer dependencies
CRITICAL_FILES=(
  "$STAGE_DIR/08600-services-quotations.php"
  "$STAGE_DIR/includes/class-plugin.php"
  "$STAGE_DIR/class-unified-table.php"
)

MISSING_DEPS=false
for file in "${CRITICAL_FILES[@]}"; do
  if [ -f "$file" ] && grep -q "require.*vendor" "$file" 2>/dev/null; then
    echo "⚠️  $file may need Composer dependencies"
    MISSING_DEPS=true
  fi
done

echo "📦 Installing production dependencies (vendor/)..."
if [ -d "$ROOT_DIR/vendor" ]; then
  echo "✅ vendor/ directory included for production"
  # Ensure dev dependencies are excluded
  if command -v composer >/dev/null 2>&1; then
    echo "⚠️  Note: Run 'composer install --no-dev --optimize-autoloader' before packaging for smaller size"
  fi
else
  echo "⚠️  WARNING: vendor/ directory not found. PDF generation will fail!"
fi

echo "🧾 Creating ZIP..."
rm -f "$ZIP_PATH"
(
  cd "$BUILD_DIR"
  zip -qr "$ZIP_PATH" "$PLUGIN_SLUG" -x "**/.DS_Store"
)

echo "✅ Done: $ZIP_PATH"
du -sh "$ZIP_PATH" || true


