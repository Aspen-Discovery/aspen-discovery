#!/usr/bin/env bash
# Updates the Solr path in the startup scripts for a specific site.
# Usage: ./update_solr_path.sh {sitename}

set -e

if [ -z "$1" ]; then
    echo "Usage: $0 {sitename}"
    exit 1
fi

SITENAME=$1
SITE_DIR="/usr/local/aspen-discovery/sites/${SITENAME}"
OLD_PATH="/usr/local/aspen-discovery/sites/default/solr-8.11.2"
NEW_PATH="/opt/solr"

if [ ! -d "$SITE_DIR" ]; then
    echo "Error: Site directory $SITE_DIR does not exist."
    exit 1
fi

echo "Updating Solr path for site: $SITENAME"

# Update .sh files in the site directory
SH_FILE="${SITE_DIR}/${SITENAME}.sh"
if [ -f "$SH_FILE" ]; then
    echo "Updating $SH_FILE"
    sed -i "s|$OLD_PATH|$NEW_PATH|g" "$SH_FILE"
else
    echo "Warning: $SH_FILE not found."
fi

# Update .bat files for Windows compatibility if they exist
BAT_FILE="${SITE_DIR}/${SITENAME}.bat"
if [ -f "$BAT_FILE" ]; then
    echo "Updating $BAT_FILE"
    # Note: .bat files usually just use 'solr' command or relative paths,
    # but we'll check for the full path just in case.
    # In the example seen, it just used 'solr', which might rely on PATH.
    sed -i "s|$OLD_PATH|$NEW_PATH|g" "$BAT_FILE"
fi

echo "Done."
