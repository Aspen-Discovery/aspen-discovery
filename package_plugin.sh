#!/bin/bash

# Plugin Packaging Script for Aspen Discovery
# Usage: ./package_plugin.sh [plugin_directory] [output_name]

if [ $# -eq 0 ]; then
    echo "Usage: $0 [plugin_directory] [output_name]"
    echo "Example: $0 example_plugin my_plugin"
    echo ""
    echo "If output_name is not provided, it will use the directory name"
    exit 1
fi

PLUGIN_DIR="$1"
OUTPUT_NAME="${2:-$1}"

# Check if plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "Error: Plugin directory '$PLUGIN_DIR' not found"
    exit 1
fi

# Check if manifest.json exists
if [ ! -f "$PLUGIN_DIR/manifest.json" ]; then
    echo "Error: manifest.json not found in '$PLUGIN_DIR'"
    echo "Make sure your plugin has a valid manifest.json file"
    exit 1
fi

# Create the .plugzip file
OUTPUT_FILE="${OUTPUT_NAME}.plugzip"

echo "Packaging plugin from '$PLUGIN_DIR' to '$OUTPUT_FILE'..."

cd "$PLUGIN_DIR"
zip -r "../$OUTPUT_FILE" . -x "*.git*" "*.DS_Store*" "node_modules/*"
cd ..

if [ $? -eq 0 ]; then
    echo "✅ Plugin packaged successfully: $OUTPUT_FILE"
    echo ""
    echo "To install this plugin:"
    echo "1. Go to Admin > System Administration > Plugins"
    echo "2. Click 'Upload Plugin (.plugzip)'"
    echo "3. Select '$OUTPUT_FILE' and upload"
else
    echo "❌ Error packaging plugin"
    exit 1
fi 