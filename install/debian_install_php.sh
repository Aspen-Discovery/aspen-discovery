#!/bin/sh

# Install the version of PHP passed in $1. This script can be used for initial
# installs from installer_debian.sh or in upgrade_debian_*.sh scripts to upgrade

php_vers="${1:-8.4}"

echo "Installing PHP $php_vers"

# Install Ondrej Sury's php repo for access to additional versions
keyrings="/etc/apt/keyrings"
if ! [ -d "$keyrings" ]; then
  mkdir -m 0755 -p "$keyrings"
fi

if ! [ -s "$keyrings/sury.gpg" ] || ! [ -s /etc/apt/sources.list.d/sury.list ]; then
  wget -q -O - https://packages.sury.org/php/apt.gpg | gpg --yes -o "$keyrings/sury.gpg" --dearmor
  echo "deb [signed-by=$keyrings/sury.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" >/etc/apt/sources.list.d/sury.list
fi

# disable older version of php apache module if present
# will be empty on new installs
curr_php=$(/usr/sbin/a2query -m | grep -Eo php...)
curr_php=${curr_php#php}

if [ -n "$curr_php" ] && [ "$curr_php" != "$php_vers" ]; then
	/usr/sbin/a2dismod "php${curr_php}"
fi

# install new package versions
apt-get update -q
DEBIAN_FRONTEND=noninteractive apt-get install -y -q "libapache2-mod-php${php_vers}" "php${php_vers}" "php${php_vers}-mcrypt" "php${php_vers}-gd" "php${php_vers}-imagick" "php${php_vers}-curl" "php${php_vers}-mysql" "php${php_vers}-zip" "php${php_vers}-xml" "php${php_vers}-intl" "php${php_vers}-mbstring" "php${php_vers}-soap" "php${php_vers}-pgsql" "php${php_vers}-ssh2" "php${php_vers}-ldap"

# Make sure the active apache module has the correct settings
cp php.ini "/etc/php/$php_vers/apache2/"

# and turn it on
/usr/sbin/a2enmod "php${php_vers}"
systemctl restart apache2

