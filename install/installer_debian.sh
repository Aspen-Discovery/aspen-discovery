#!/bin/sh

#Expects to be installed on Debian 10 Buster or later
#Run as sudo ./installer_debian.sh
apt-get update
apt-get -y install gpg openjdk-11-jre-headless openjdk-11-jdk-headless apache2 certbot python3-certbot-apache mariadb-server apt-transport-https lsb-release ca-certificates curl zip

# Install "plain" php package here to determine OS default. Override below if desired.
if ! dpkg -l | grep ii | grep -qE ' php[0-9]+\.[0-9]+ ' ; then
  apt-get install -y php
fi

php_vers="$(dpkg -l | grep ii | grep -E ' php[0-9]+\.[0-9]+ ' | grep -Eo '[0-9]+\.[0-9]+' | head -1)"

if test -z "$php_vers" ; then
  echo "Unable to determine default php version!"
  exit 1
fi

# Install Ondrej Sury's php repo for additional php modules
# Specifically, the abandonware php-mcrypt...
keyrings="/etc/apt/keyrings"
test -d "$keyrings" || (mkdir -p "$keyrings" ; chmod 0755 "$keyrings")
if ! test -f "$keyrings/sury.gpg" || ! test -f /etc/apt/sources.list.d/sury.list ; then
  wget -q -O - https://packages.sury.org/php/apt.gpg | gpg -o "$keyrings/sury.gpg" --dearmor
  echo "deb [signed-by=$keyrings/sury.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury.list
  apt-get update
fi

# Have to use versions for these or the highest version available from sury.org is used rather than the system verison.
apt-get install -y "php${php_vers}-mcrypt" "php${php_vers}-gd" "php${php_vers}-curl" "php${php_vers}-mysql" "php${php_vers}-zip" "php${php_vers}-xml" "php${php_vers}-intl" "php${php_vers}-mbstring" "php${php_vers}-soap"

# - Change max_memory to 256M
# - Increase max file size to 75M
# - Increase max post size to 75M
php_ini="/etc/php/${php_vers}/apache2/php.ini"
# Necessary or surprise baggage from dragging around an old php.ini?
#grep -q '^max_input_vars = 2000' "$php_ini" || sed -Ei 's/^;max_input_vars = [0-9]+/max_input_vars = 2000/' "$php_ini"
grep -q '^memory_limit = 256M' "$php_ini" || sed -Ei 's/^memory_limit = [0-9]+M/memory_limit = 256M/' "$php_ini"
grep -q '^post_max_size = 75M' "$php_ini" || sed -Ei 's/^post_max_size = [0-9]+M/post_max_size = 75M/' "$php_ini"
grep -q '^upload_max_filesize = 75M' "$php_ini" || sed -Ei 's/^upload_max_filesize = [0-9]+M/upload_max_filesize = 75M/' "$php_ini"

./samlsso_installer_debian.sh

# MariaDB overrides
cp 60-aspen.cnf /etc/mysql/mariadb.conf.d/

a2enmod rewrite
systemctl restart apache2 mysql

# Create temp smarty directories
mkdir -p /usr/local/aspen-discovery/tmp
chown -R www-data:www-data /usr/local/aspen-discovery/tmp
chmod -R 755 /usr/local/aspen-discovery/tmp

# Raise process and open file limits for the solr user
cp solr_limits.conf /etc/security/limits.d/solr.conf

# Create aspen MySQL superuser
printf "Please enter the username for the Aspen MySQL superuser (can't be root) : " >&2
read -r username
printf "Please enter the password for the Aspen MySQL superuser (%s) : " "$username" >&2
read -r password
query="GRANT ALL PRIVILEGES ON *.* TO '$username'@'localhost' IDENTIFIED BY '$password';"
mysql -e "$query"
query="GRANT ALL PRIVILEGES ON *.* TO '$username'@'127.0.0.1' IDENTIFIED BY '$password';"
mysql -e "$query"
mysql -e "flush privileges"

mysql_secure_installation

./setup_aspen_user_debian.sh
