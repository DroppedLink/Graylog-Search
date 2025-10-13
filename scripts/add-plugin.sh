#!/bin/bash

# Add a new plugin to the development environment
# Usage: ./scripts/add-plugin.sh my-new-plugin

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

if [ -z "$1" ]; then
    echo -e "${RED}Error: Please provide a plugin name${NC}"
    echo "Usage: ./scripts/add-plugin.sh my-plugin-name"
    exit 1
fi

PLUGIN_NAME="$1"
PLUGINS_DIR="plugins"
PLUGIN_PATH="${PLUGINS_DIR}/${PLUGIN_NAME}"

# Create plugin directory
if [ -d "$PLUGIN_PATH" ]; then
    echo -e "${YELLOW}Plugin directory already exists: ${PLUGIN_PATH}${NC}"
else
    mkdir -p "$PLUGIN_PATH"
    echo -e "${GREEN}✓ Created plugin directory: ${PLUGIN_PATH}${NC}"
fi

# Create main plugin file if it doesn't exist
MAIN_FILE="${PLUGIN_PATH}/${PLUGIN_NAME}.php"
if [ ! -f "$MAIN_FILE" ]; then
    cat > "$MAIN_FILE" << EOF
<?php
/**
 * Plugin Name: ${PLUGIN_NAME}
 * Description: Description of your plugin
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Your plugin code here
EOF
    echo -e "${GREEN}✓ Created main plugin file: ${MAIN_FILE}${NC}"
else
    echo "Main plugin file already exists: ${MAIN_FILE}"
fi

echo ""
echo -e "${GREEN}Plugin created successfully!${NC}"
echo ""
echo "The plugin is automatically available in WordPress (no restart needed)."
echo ""
echo "Next steps:"
echo "1. Edit your plugin: ${MAIN_FILE}"
echo "2. Go to WordPress Admin → Plugins → Installed Plugins"
echo "3. Activate '${PLUGIN_NAME}'"
echo ""
echo "Your plugin will appear instantly in WordPress!"
