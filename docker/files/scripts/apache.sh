#!/bin/bash

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

    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] [APACHE] [${color}${level}${NC}] ${message}"
}

log_info()  { log "INFO" "$1"; }
log_warn()  { log "WARN" "$1"; }
log_error() { log "ERROR" "$1"; }

log_info "Starting Apache initialization"

export CONFIG_DIRECTORY="/usr/local/aspen-discovery/sites/${SITE_NAME}"

# Check if site configuration exists
apacheConfFile="$CONFIG_DIRECTORY/httpd-${SITE_NAME}.conf"
log_info "Waiting for site configuration: $apacheConfFile"

tries=0
MAX_TRIES=10

while [ ! -f "$apacheConfFile" ]; do
    sleep 5
    ((tries++))
    log_info "Waiting for configuration file... (attempt $tries/$MAX_TRIES)"

    if [ $tries -eq $MAX_TRIES ]; then
        log_error "Site configuration not found after $MAX_TRIES attempts. Exiting."
        exit 1
    fi
done

log_info "Configuration file found: $apacheConfFile"

# Prepare Apache environment
log_info "Preparing Apache environment"
mkdir -p /var/run/apache2
chown -R www-data:www-data /var/run/apache2

# Source environment variables
if [ -f /etc/apache2/envvars ]; then
    source /etc/apache2/envvars
    log_info "Apache environment variables loaded"
else
    log_warn "/etc/apache2/envvars not found"
fi

# Move to docker directory
cd "/usr/local/aspen-discovery/docker/files/apache2/" || {
    log_error "Cannot change to docker directory"
    exit 1
}

# Set Apache configurations
log_info "Setting Apache configurations"
if ! php setApacheConf.php "$apacheConfFile"; then
    log_error "Apache configuration failed"
    exit 1
fi

log_info "Apache configuration completed successfully"

# Validate Apache configuration
log_info "Validating Apache configuration"
if ! apache2 -t; then
    log_error "Apache configuration test failed"
    exit 1
fi

log_info "Apache configuration is valid"


# Apache runs as PID 1 via exec — Docker signals it directly for graceful shutdown
log_info "Starting Apache in foreground mode..."
exec apache2 -D FOREGROUND


