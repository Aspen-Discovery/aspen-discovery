#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

USER_HOME=$(getent passwd "${SUDO_USER:-$USER}" | cut -d: -f6)

# ---- Default Configuration ------------------------------------------
PHP_VER="8.4"				# Default PHP version
GD_VER="gd-2.3.3"			# Default GD version branch
NJOBS="$(nproc)"			# Parallel make jobs
# ---------------------------------------------------------------------

log(){ printf '\e[36m%s\e[0m\n' "$*"; }

while [[ $# -gt 0 ]]; do
  case $1 in
    --clean)
      log "Deep-clean: reverting the system to its pre-experiment state!"

      sudo rm -rf \
          /usr/local/src/libgd /usr/local/src/libgd-build \
          "$HOME/php${PHP_VER}-src" "$HOME/.php-src-probe"

      # Remove custom gd.so that was installed.
      sudo rm -f "$(php -r 'echo ini_get("extension_dir");')/gd.so"

      # Purge packages installed for the custom build.
      sudo apt-get -y --purge remove libgd3 libgd-dev php"${PHP_VER}"-gd || true
      sudo apt-get -y autoremove

      # Clean APT caches so the next run re-downloads.
      sudo apt-get clean

      log "System state rolled back!"
      exit 0
      ;;
    --php-version)
      PHP_VER="$2"
      shift 2
      ;;
    --gd-version)
      GD_VER="$2"
      shift 2
      ;;
    *)
      echo "Error: Unknown argument '$1'"
      echo "Usage: $0 [--php-version VERSION] [--gd-version VERSION] [--clean]"
      exit 1
      ;;
  esac
done

log "Building GD with RAQM support for PHP ${PHP_VER} using ${GD_VER}"

log "Installing build prerequisites"
sudo apt-get -y update -qq || true
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
  build-essential cmake git pkg-config autoconf libtool \
  libfreetype6-dev libpng-dev libjpeg-dev libwebp-dev libavif-dev \
  libharfbuzz-dev libfribidi-dev libraqm-dev libxpm-dev \
  php"${PHP_VER}"-dev php"${PHP_VER}"-common

log "Removing distro libgd and php-gd so they cannot override this install"
sudo DEBIAN_FRONTEND=noninteractive apt-get remove -y --purge \
  libgd3 libgd-dev php"${PHP_VER}"-gd || true

# 1. Build and install libgd with RAQM.
if [[ ! -d /usr/local/src/libgd ]]; then
  log "Cloning libgd ${GD_VER}"
  sudo git clone --depth 1 --branch "${GD_VER}" https://github.com/libgd/libgd.git \
       /usr/local/src/libgd
fi
log "Configuring libgd..."
sudo cmake -S /usr/local/src/libgd -B /usr/local/src/libgd-build \
  -DCMAKE_INSTALL_PREFIX=/usr \
  -DENABLE_FREETYPE=ON -DENABLE_PNG=ON -DENABLE_JPEG=ON \
  -DENABLE_WEBP=ON    -DENABLE_AVIF=ON \
  -DENABLE_XPM=ON     -DENABLE_RAQM=ON \
  >/dev/null
log "Compiling libgd, may take a minute..."
sudo cmake --build /usr/local/src/libgd-build -j"${NJOBS}" >/dev/null
log "Installing libgd"
sudo cmake --install /usr/local/src/libgd-build
sudo ldconfig

# Quick sanity-check.
ldd /usr/lib/x86_64-linux-gnu/libgd.so.3 | grep -q libraqm || { echo "‼  libgd was built without RAQM! aborting" >&2; exit 1; }

# 2. Rebuild PHP's gd extension against the new libgd.
# Debian 11's official repositories stop at PHP 7.4,
# so get PHP packages from Sury's repository.
if ! sudo -u "${SUDO_USER:-$USER}" bash -c \
     "mkdir -p \$HOME/.php-src-probe &&
      cd \$HOME/.php-src-probe &&
      apt-get -qq --download-only source php${PHP_VER}" 2>/dev/null; then
  log "Sury deb-src for PHP${PHP_VER} missing, adding it now"

  # Keyring should already exist from the binary repo; create if not.
  [[ -f /etc/apt/keyrings/sury.gpg ]] ||
    curl -fsSL https://packages.sury.org/php/apt.gpg |
    sudo tee /etc/apt/keyrings/sury.gpg >/dev/null

  cat <<EOF | sudo tee /etc/apt/sources.list.d/sury-src.list >/dev/null
deb-src [signed-by=/etc/apt/keyrings/sury.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main
EOF
  sudo apt-get -y update -qq || true
fi

log "Re-building PHP ${PHP_VER} GD extension"
PHP_SRC_DIR=$USER_HOME/php${PHP_VER}-src
if [[ ! -d $PHP_SRC_DIR ]]; then
  sudo -u "${SUDO_USER:-$USER}" bash -c "
      cd \"$USER_HOME\" &&
      apt-get -q source php${PHP_VER} &&
      PHP_EXTRACTED_DIR=\$(find . -maxdepth 1 -type d -name 'php${PHP_VER}-*' | head -n1) &&
      if [[ -n \"\$PHP_EXTRACTED_DIR\" && -d \"\$PHP_EXTRACTED_DIR\" ]]; then
        mv \"\$PHP_EXTRACTED_DIR\" \"${PHP_SRC_DIR}\"
      else
        echo 'Error: Could not find extracted PHP source directory' >&2
        exit 1
      fi
  "
fi

pushd "$PHP_SRC_DIR/ext/gd" >/dev/null
  phpize >/dev/null
  ./configure --with-external-gd >/dev/null
  make -j"${NJOBS}" >/dev/null
  sudo make install >/dev/null

  # Write and enable ini.
  if [[ ! -f /etc/php/${PHP_VER}/mods-available/gd.ini ]]; then
    echo 'extension=gd.so' | sudo tee /etc/php/"${PHP_VER}"/mods-available/gd.ini
  fi
  sudo phpenmod -v "${PHP_VER}" gd
popd >/dev/null

# 3. Restart web-stack and verify.
if systemctl is-active --quiet apache2; then
  log "Restarting apache2"
  sudo systemctl restart apache2
else
  log "apache2 is not running, skipping restart"
fi

EXT_DIR="$(php -r 'echo ini_get("extension_dir");')"
log "gd_info() after rebuild:"
php -r 'print_r(gd_info());' |
  grep -E 'GD Version|FreeType|RAQM|AVIF' |
  sed 's/^/    /'

# PHP source code does not seem to have RAQM output when
# gd_info() is called, so confirm using dynamic link.
log "Dynamic-link proof:"
ldd "${EXT_DIR}/gd.so" | grep raqm || echo "libraqm NOT linked!"

log "Cleaning build artefacts"
  rm -rf "$USER_HOME/php${PHP_VER}-src" \
         "$USER_HOME/.php-src-probe" \
         "$USER_HOME"/php"${PHP_VER}"_*.{xz,dsc,asc} 2>/dev/null || true

log "All done: libgd with RAQM and recompiled php-gd are now live!"