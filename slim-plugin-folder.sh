#!/bin/bash
# Make the plugin folder under 10 MB by removing .git and vendor.
# Use this for a copy you want to keep small. After running:
# - Folder will be ~6 MB (under 10 MB).
# - To use the plugin again, run: composer install
#
# WARNING: This deletes .git (you lose version history here) and vendor
# (dependencies). Keep a full backup or clone elsewhere if you need history.

set -e
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo "Current folder size:"
du -sh . 2>/dev/null || true

if [ -d ".git" ]; then
  echo "Removing .git (saves ~56 MB)..."
  rm -rf .git
fi

if [ -d "vendor" ]; then
  echo "Removing vendor (saves ~18 MB). Run 'composer install' to restore."
  rm -rf vendor
fi

echo "New folder size:"
du -sh .
echo "Done. Run 'composer install' when you need PDF/Sheets/QR features."
