#!/bin/bash

set -euo pipefail

echo "🧹 Cleaning up development files from plugin directory..."
echo "⚠️  This will remove development-only files but keep your source code safe"
echo ""

# Remove node_modules (development dependency)
if [ -d "node_modules" ]; then
    echo "🗑️  Removing node_modules/ (74MB)..."
    rm -rf node_modules/
    echo "✅ Removed node_modules/"
fi

# Remove build directory (can be regenerated)
if [ -d "build" ]; then
    echo "🗑️  Removing build/ directory (11MB)..."
    rm -rf build/
    echo "✅ Removed build/"
fi

# Remove SQL backup files
echo "🗑️  Removing SQL backup files..."
rm -f latestSQL.sql
rm -f latestSQL.sql.backup
rm -f latestSQL.sql.backup2
rm -f latestSQL.sql.bak2
rm -f latestSQL.sql.bak
echo "✅ Removed SQL backup files"

# Remove development text files
echo "🗑️  Removing development text files..."
rm -f waybill_dimension_updates.txt
rm -f agent-sessions-recovery.txt
rm -f hook-registered.txt
rm -f COMPARISON.txt
echo "✅ Removed development text files"

# Remove package-lock.json (can be regenerated)
if [ -f "package-lock.json" ]; then
    echo "🗑️  Removing package-lock.json..."
    rm -f package-lock.json
    echo "✅ Removed package-lock.json"
fi

# Remove waybill_excel directory (development data)
if [ -d "waybill_excel" ]; then
    echo "🗑️  Removing waybill_excel/ directory..."
    rm -rf waybill_excel/
    echo "✅ Removed waybill_excel/"
fi

# Remove bin directory (development scripts)
if [ -d "bin" ]; then
    echo "🗑️  Removing bin/ directory..."
    rm -rf bin/
    echo "✅ Removed bin/"
fi

# Remove Python scripts (development tools)
echo "🗑️  Removing Python development scripts..."
rm -f *.py
rm -f generate-sql.ps1
echo "✅ Removed Python/PowerShell scripts"

# Remove development PHP files
echo "🗑️  Removing development PHP files..."
rm -f customer-summary.php
rm -f fix_waybill_dimensions.php
rm -f export_city_mapping.php
rm -f import_excel_waybills.php
rm -f unified-table-examples.php
echo "✅ Removed development PHP files"

# Remove Python virtual environment
if [ -d ".venv" ]; then
    echo "🗑️  Removing .venv/ directory (Python virtual environment)..."
    rm -rf .venv/
    echo "✅ Removed .venv/"
fi

# Remove .DS_Store files
echo "🗑️  Removing .DS_Store files..."
find . -name ".DS_Store" -delete 2>/dev/null || true
echo "✅ Removed .DS_Store files"

# Show new size
echo ""
echo "📊 New directory size:"
du -sh .

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "📝 Note: .git directory (67MB) was NOT removed to preserve your version control."
echo "   If you're packaging for WordPress.org, use package-plugin.sh which excludes .git automatically."
echo ""
echo "💡 To restore development files, run:"
echo "   - npm install (for node_modules)"
echo "   - composer install (if vendor/ was removed)"

