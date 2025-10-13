#!/bin/bash

# WordPress Plugin Packaging Script
# Usage: ./scripts/zip-plugin.sh plugin-folder-name

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if plugin name is provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Please provide a plugin folder name${NC}"
    echo "Usage: ./scripts/zip-plugin.sh plugin-folder-name"
    echo ""
    echo "Available plugins:"
    ls -1 plugins/
    exit 1
fi

PLUGIN_NAME="$1"
PLUGINS_DIR="plugins"
DIST_DIR="dist"
PLUGIN_PATH="${PLUGINS_DIR}/${PLUGIN_NAME}"

# Check if plugin exists
if [ ! -d "$PLUGIN_PATH" ]; then
    echo -e "${RED}Error: Plugin folder '${PLUGIN_NAME}' not found in ${PLUGINS_DIR}/${NC}"
    echo ""
    echo "Available plugins:"
    ls -1 plugins/
    exit 1
fi

# Create dist directory if it doesn't exist
mkdir -p "$DIST_DIR"

# Temporary directory for building
TEMP_DIR=$(mktemp -d)
TEMP_PLUGIN_DIR="${TEMP_DIR}/${PLUGIN_NAME}"

echo -e "${YELLOW}Packaging plugin: ${PLUGIN_NAME}${NC}"

# Copy plugin files to temp directory
cp -r "$PLUGIN_PATH" "$TEMP_PLUGIN_DIR"

# Remove development files and folders
echo "Cleaning up development files..."
cd "$TEMP_PLUGIN_DIR"

# Remove common development files/folders
rm -rf .git .gitignore .github
rm -rf node_modules bower_components vendor
rm -rf tests test .phpunit.result.cache phpunit.xml phpunit.xml.dist
rm -rf .vscode .idea *.sublime-*
rm -rf .DS_Store Thumbs.db
rm -rf *.log
rm -rf composer.json composer.lock package.json package-lock.json yarn.lock
rm -rf .editorconfig .eslintrc* .prettierrc* .stylelintrc*
rm -rf webpack.config.js gulpfile.js gruntfile.js
rm -rf src/ assets/src/  # Remove source directories if you have build process

# Go back to project root
cd - > /dev/null

# Create the zip file
OUTPUT_FILE="${DIST_DIR}/${PLUGIN_NAME}.zip"
echo "Creating zip file..."

cd "$TEMP_DIR"
zip -r "${PLUGIN_NAME}.zip" "${PLUGIN_NAME}" -q
cd - > /dev/null

# Move zip to dist directory
mv "${TEMP_DIR}/${PLUGIN_NAME}.zip" "$OUTPUT_FILE"

# Clean up temp directory
rm -rf "$TEMP_DIR"

# Get file size
FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)

echo -e "${GREEN}✓ Plugin packaged successfully!${NC}"
echo ""
echo "Output file: ${OUTPUT_FILE}"
echo "File size: ${FILE_SIZE}"
echo ""
echo "You can now upload this .zip file to any WordPress site:"
echo "1. Go to WordPress Admin → Plugins → Add New"
echo "2. Click 'Upload Plugin'"
echo "3. Choose the file: ${OUTPUT_FILE}"
echo "4. Click 'Install Now'"

