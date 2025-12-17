#!/bin/bash
set -e

# Colors for log output
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

log() {
    local level="${1:-INFO}"
    local message="$2"
    local color=""

    case "$level" in
        ERROR) color="$RED" ;;
        WARN)  color="$YELLOW" ;;
    esac

    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] [BACKEND] [${color}${level}${NC}] ${message}"
}

log_info()  { log "INFO" "$1"; }
log_warn()  { log "WARN" "$1"; }
log_error() { log "ERROR" "$1"; }

echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%"
echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%"
echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%"
echo "%%%%%%%%%%%%                                                                           %%%%%%%%%%%%%"
echo "%%%%%%%%%%%%                      WELCOME TO ASPEN DISCOVERY                           %%%%%%%%%%%%%"
echo "%%%%%%%%%%%%                                                                           %%%%%%%%%%%%%"
echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%"
echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%"
echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%"
echo "                                        "
echo "                                        "
echo "                                        "
echo "                                        "
echo "                                        "


log_info "Aspen Discovery starting for: ${SITE_NAME}"

export CONFIG_DIRECTORY="/usr/local/aspen-discovery/sites/$SITE_NAME"

# Move to docker directory
cd "/usr/local/aspen-discovery/docker/files/scripts" || exit

# Check if site configuration exists
confSiteFile="$CONFIG_DIRECTORY/conf/config.ini"
if [ ! -f "$confSiteFile" ] ; then
	log_info "$confSiteFile not found. Generating..."
	mkdir -p "$CONFIG_DIRECTORY"
	if ! php createConfig.php "$CONFIG_DIRECTORY" ; then
		log_error "Failed to create instance config"
		exit 1
	fi
fi

# Sync environment variables to config files (runs every start)
log_info "Syncing environment variables to config..."
if ! php syncEnvToConfig.php ; then
	log_warn "Environment sync failed, using existing config"
fi

# Initialize Aspen database
log_info "Initializing database"
if ! php initDatabase.php ; then
	log_error "Database initialization failed"
	exit 1
fi

# Create missing dirs and fix ownership and permissions if needed
log_info "Setting up data and log directories"
if ! php createDirs.php ; then
	log_error "Directories creation and permission fixes failed"
	exit 1
fi

# FIXME: This seems to be creating dirs like images/images, etc
#        It should be put outside the codebase, and mounted accordingly

# Move and create temporarily sym-links to data directory
dataDir="/data/aspen-discovery/$SITE_NAME"
localDir="/usr/local/aspen-discovery/code/web"

directories=(files images fonts)
for dir in "${directories[@]}"; do

	source="$localDir/$dir"
    dest="$dataDir/$dir"

    # Ensure persistent target directory exists
    mkdir -p "$dest"

    # Move original data only if source is a real directory and target is empty
    if [ -d "$source" ] && [ ! -L "$source" ] && [ "$(ls -A "$dest")" == "" ]; then
        mv "$source"/* "$dest"/ 2>/dev/null || true
    fi

    # Remove source only if it's a real directory (not a symlink)
    # This is required because ln -sfn can't replace a directory
    if [ -d "$source" ] && [ ! -L "$source" ]; then
        rm -rf "$source"
    fi

    # Create symlink atomically (ln -sfn replaces existing symlink in one operation)
    ln -sfn "$dest" "$source"

    log_info "Created symlink: $source → $dest"
done

# Run pending database updates
log_info "Running pending database updates..."
php updateDatabase.php "$SITE_NAME"

sudo -u www-data php /usr/local/aspen-discovery/docker/files/cron/checkBackgroundProcessesDocker.php $SITE_NAME >/proc/1/fd/1 2>/proc/1/fd/2

log_info "Starting PHP-FPM in foreground mode..."
php-fpm8.4 --test && exec php-fpm8.4 -F