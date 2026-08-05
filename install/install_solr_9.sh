#!/usr/bin/env bash
# Installs solr 9, but first verifies that java version 17 is available and if not attempts to install it.
set -e

SOLR_VERSION="9.10.1"
SOLR_TAR="solr-${SOLR_VERSION}.tgz"
DOWNLOAD_URL="https://archive.apache.org/dist/solr/solr/${SOLR_VERSION}/${SOLR_TAR}"

SOLR_DATA_DIR=${2:-/var/solr}

# 1. Ensure Java 17 or higher is present
check_java_version() {
    if ! command -v java >/dev/null 2>&1; then
        return 1
    fi
    local version=$(java -version 2>&1 | awk -F '"' '/version/ {print $2}' | awk -F '.' '{print $1}')
    # For newer Java versions, the first part is the major version (e.g., 17, 21)
    # For older ones like 1.8, it's the second part, but we only care about 17+
    if [[ "$version" -ge 17 ]]; then
        return 0
    else
        return 1
    fi
}

if ! check_java_version; then
    echo "Java 17 or higher not found. Attempting to install Java 17..."
    if [ -f /etc/debian_version ]; then
        # Debian/Ubuntu Install
        apt-get update && sudo apt-get install -y openjdk-17-jre-headless jq wget
    elif [ -f /etc/redhat-release ]; then
        # RedHat 9 Install
        if command -v dnf >/dev/null 2>&1; then
            dnf install -y java-17-openjdk-headless jq wget procps
        else
            yum install -y java-17-openjdk-headless jq wget procps
        fi
    else
        echo "Unsupported OS for automatic Java installation."
        exit 1
    fi
fi

# Re-check Java version
if ! check_java_version; then
    echo "Java 17 or higher installation failed or incorrect version detected."
    exit 1
fi

# 2. Check if Solr 9.10.1 is already installed
if [ -x /opt/solr/bin/solr ]; then
    INSTALLED_VERSION=$(/opt/solr/bin/solr version)
    if [[ "$INSTALLED_VERSION" == *"$SOLR_VERSION"* ]]; then
        echo "Solr $SOLR_VERSION is already installed."
        exit 0
    fi
fi

# 3. Download Solr tarball
wget -q "$DOWNLOAD_URL" -O "/tmp/${SOLR_TAR}"

# 4. Extract the service installer script
tar xzf "/tmp/${SOLR_TAR}" "solr-${SOLR_VERSION}/bin/install_solr_service.sh" --strip-components=2

# 5. Run the installer script
# Places binaries in /opt/solr and create the 'solr' system user
sudo ./install_solr_service.sh "/tmp/${SOLR_TAR}" -i /opt -n -u solr

# 6. Clean up installer artifacts
rm -f "/tmp/${SOLR_TAR}" install_solr_service.sh
