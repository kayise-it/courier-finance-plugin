#!/bin/bash

echo "🧹 Cleaning plugin for WordPress.org submission..."

# Remove large directories that shouldn't be in the plugin
echo "Removing node_modules..."
rm -rf node_modules/

echo "Removing vendor directory..."
rm -rf vendor/

echo "Removing dist directory..."
rm -rf dist/

echo "Removing package-lock.json..."
rm -f package-lock.json

echo "Removing composer.lock..."
rm -f composer.lock

# Keep only essential font files
echo "Optimizing fonts directory..."
mkdir -p fonts/Inter/essential
# Keep only the variable font files (much smaller)
cp fonts/Inter/Inter-VariableFont_opsz,wght.ttf fonts/Inter/essential/ 2>/dev/null || true
cp fonts/Inter/Inter-Italic-VariableFont_opsz,wght.ttf fonts/Inter/essential/ 2>/dev/null || true
# Remove the large static directory
rm -rf fonts/Inter/static/
rm -rf fonts/Inter/Inter-VariableFont_opsz,wght.ttf
rm -rf fonts/Inter/Inter-Italic-VariableFont_opsz,wght.ttf
# Move essential fonts back
mv fonts/Inter/essential/* fonts/Inter/ 2>/dev/null || true
rmdir fonts/Inter/essential 2>/dev/null || true

# Remove documentation files (optional)
echo "Removing documentation files..."
rm -f *.md
rm -f SDS_Document.md
rm -f User_Manual.md
rm -f EDIT_WAYBILL_UI_IMPROVEMENTS.md
rm -f UI_STANDARDIZATION_REPORT.md
rm -f SCHEDULED_DELIVERIES_JS_README.md
rm -f BUTTON_THEME_GUIDE.md
rm -f ROLE_SETUP_INSTRUCTIONS.md
rm -f DELIVERY_COMPONENT_README.md

# Remove development files
echo "Removing development files..."
rm -f test-edit-waybill-ui.php
rm -f demo_scheduled_deliveries.html
rm -f production-wp-config.php
rm -f webpage.php
rm -f tailwind.config.js
rm -f postcss.config.js
rm -f package.json
rm -f composer.json
rm -f colorSchema.json

# Remove .DS_Store files
echo "Removing .DS_Store files..."
find . -name ".DS_Store" -delete

echo "✅ Cleanup complete!"
echo "📊 New size:"
du -sh .

echo ""
echo "📝 Next steps:"
echo "1. Test your plugin to ensure it still works"
echo "2. Create a ZIP file for WordPress.org submission"
echo "3. The plugin should now be under 10MB"
