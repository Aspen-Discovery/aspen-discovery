#!/bin/bash

# Enhanced RTL CSS Generator for LESS → CSS → RTL workflow
# This script can handle both CSS files and monitor for CSS changes after LESS compilation

INPUT_FILE="$1"
WATCH_MODE="$2"

if [ ! -f "$INPUT_FILE" ]; then
    echo "Input file does not exist: $INPUT_FILE"
    exit 1
fi

DIR=$(dirname "$INPUT_FILE")
FILENAME=$(basename "$INPUT_FILE")
BASENAME="${FILENAME%.*}"
EXTENSION="${FILENAME##*.}"

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ "$EXTENSION" = "css" ]; then
    # Direct CSS processing
    RTL_FILE="$DIR/${BASENAME}-rtl.css"
    echo "Generating RTL stylesheet from $INPUT_FILE..."
    node "$SCRIPT_DIR/generate-rtl.js" "$INPUT_FILE" "$RTL_FILE"

elif [ "$EXTENSION" = "less" ]; then
    # LESS file detected - wait for corresponding CSS file to be updated
    CSS_FILE="$DIR/${BASENAME}.css"
    RTL_FILE="$DIR/${BASENAME}-rtl.css"

    echo "LESS file detected: $INPUT_FILE"
    echo "Monitoring for CSS compilation: $CSS_FILE"

    if [ "$WATCH_MODE" = "watch" ]; then
        # Watch mode - monitor the CSS file for changes
        INITIAL_TIME=0
        if [ -f "$CSS_FILE" ]; then
            INITIAL_TIME=$(stat -f %m "$CSS_FILE" 2>/dev/null || stat -c %Y "$CSS_FILE" 2>/dev/null || echo 0)
            echo "Initial CSS file time: $INITIAL_TIME"
        else
            echo "CSS file does not exist yet: $CSS_FILE"
        fi

        # Wait up to 5 seconds for CSS file to be updated
        echo "Waiting for CSS file update..."
        for i in {1..10}; do
            if [ -f "$CSS_FILE" ]; then
                CURRENT_TIME=$(stat -f %m "$CSS_FILE" 2>/dev/null || stat -c %Y "$CSS_FILE" 2>/dev/null || echo 0)
                echo "Check $i: Current time: $CURRENT_TIME, Initial time: $INITIAL_TIME"
                if [ "$CURRENT_TIME" -gt "$INITIAL_TIME" ]; then
                    echo "CSS file updated, generating RTL..."
                    sleep 0.3  # Brief delay to ensure file write is complete
                    node "$SCRIPT_DIR/generate-rtl.js" "$CSS_FILE" "$RTL_FILE"
                    exit $?
                fi
            fi
            sleep 0.5
        done

        # If no update detected, try processing the current CSS file anyway
        echo "No update detected, processing existing CSS file..."
        if [ -f "$CSS_FILE" ]; then
            node "$SCRIPT_DIR/generate-rtl.js" "$CSS_FILE" "$RTL_FILE"
            exit $?
        fi

        echo "Timeout waiting for CSS file update and no existing CSS file found"
        exit 1
    else
        # Immediate mode - process CSS file if it exists
        if [ -f "$CSS_FILE" ]; then
            echo "Processing existing CSS file: $CSS_FILE"
            node "$SCRIPT_DIR/generate-rtl.js" "$CSS_FILE" "$RTL_FILE"
        else
            echo "CSS file not found: $CSS_FILE"
            echo "Make sure LESS compilation is working properly"
            exit 1
        fi
    fi
else
    echo "Unsupported file type: $EXTENSION"
    echo "Supported types: .css, .less"
    exit 1
fi

if [ $? -eq 0 ]; then
    echo "✓ Successfully generated RTL stylesheet"
else
    echo "✗ Failed to generate RTL stylesheet"
    exit 1
fi
