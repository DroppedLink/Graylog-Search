#!/bin/bash
# Build script for Graylog Search WordPress Plugin
# Creates a properly structured zip file for WordPress installation

# Exit on error
set -e

# Get the version from the main plugin file
VERSION=$(grep "Version:" graylog-search.php | awk '{print $3}')
echo "Building Graylog Search v${VERSION}..."

# Create temporary build directory
BUILD_DIR="build"
PLUGIN_DIR="${BUILD_DIR}/graylog-search"
ZIP_NAME="graylog-search-${VERSION}.zip"

# Clean up any previous build
rm -rf "${BUILD_DIR}"
rm -f "${ZIP_NAME}"

# Create build directory structure
mkdir -p "${PLUGIN_DIR}"

# Copy plugin files (exclude development files)
echo "Copying plugin files..."
rsync -av --exclude="${BUILD_DIR}" \
    --exclude=".git" \
    --exclude=".gitignore" \
    --exclude=".gitattributes" \
    --exclude="build-release.sh" \
    --exclude="test-github-actions.sh" \
    --exclude="*.md" \
    --exclude=".DS_Store" \
    ./ "${PLUGIN_DIR}/"

# Include only essential markdown files
cp README.md "${PLUGIN_DIR}/" 2>/dev/null || true
cp CHANGELOG.md "${PLUGIN_DIR}/" 2>/dev/null || true

# Create zip file (using system zip to preserve structure)
echo "Creating zip file..."
cd "${BUILD_DIR}"
zip -r "../${ZIP_NAME}" graylog-search/ -q
cd ..

# Clean up build directory
rm -rf "${BUILD_DIR}"

echo "✓ Successfully created: ${ZIP_NAME}"
echo "✓ This file will extract to: graylog-search/"
echo ""
echo "Next steps:"
echo "1. Go to: https://github.com/DroppedLink/Graylog-Search/releases/tag/v${VERSION}"
echo "2. Click 'Edit release'"
echo "3. Upload ${ZIP_NAME} as an asset"
echo "4. Update release notes to mention using the uploaded zip instead of source code"

