#!/bin/sh
#Installs or updates Composer 2 at /usr/local/bin/composer using the official installer.
#Must be run as root after PHP has been installed.
#Run as sudo ./install_composer.sh

COMPOSER_PATH=/usr/local/bin/composer

if [ -x "$COMPOSER_PATH" ]; then
  echo "Composer already installed, updating to the latest 2.x release"
  "$COMPOSER_PATH" self-update --2
  exit $?
fi

TMP_DIR="$(mktemp -d)"
cd "$TMP_DIR" || exit 1

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
  >&2 echo 'ERROR: Invalid Composer installer checksum'
  cd / && rm -rf "$TMP_DIR"
  exit 1
fi

php composer-setup.php --quiet --2 --install-dir=/usr/local/bin --filename=composer
RESULT=$?
cd / && rm -rf "$TMP_DIR"
exit $RESULT
