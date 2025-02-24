#!/bin/sh

git config --global --add safe.directory /usr/local/aspen-discovery

#Expects to be installed on Debian 10 Buster or later
#Run as sudo ./installer_debian.sh
apt-get update
apt-get -y install gpg openjdk-17-jre-headless openjdk-17-jdk-headless apache2 certbot python3-certbot-apache mariadb-server apt-transport-https lsb-release ca-certificates curl zip

# Install Ondrej Sury's php repo for access to additional PHP versions
keyrings="/etc/apt/keyrings"
test -d "$keyrings" || (
	mkdir -p "$keyrings"
	chmod 0755 "$keyrings"
)
if ! test -f "$keyrings/sury.gpg" || ! test -f /etc/apt/sources.list.d/sury.list; then
	wget -q -O - https://packages.sury.org/php/apt.gpg | gpg -o "$keyrings/sury.gpg" --dearmor
	echo "deb [signed-by=$keyrings/sury.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" >/etc/apt/sources.list.d/sury.list
	apt-get update
fi

# Override default OS PHP version
php_vers="8.0"

# Have to use versions for these or the highest version available is installed.
apt-get install -y "php${php_vers}" "php${php_vers}-mcrypt" "php${php_vers}-gd" "php${php_vers}-imagick" "php${php_vers}-curl" "php${php_vers}-mysql" "php${php_vers}-zip" "php${php_vers}-xml" "php${php_vers}-intl" "php${php_vers}-mbstring" "php${php_vers}-soap" "php${php_vers}-pgsql" "php${php_vers}-ssh2" "php${php_vers}-ldap"

# - Change max_memory to 256M
# - Increase max post size to 75M
php_ini="/etc/php/${php_vers}/apache2/php.ini"
grep -q '^memory_limit = 256M' "$php_ini" || sed -Ei 's/^memory_limit = [0-9]+M/memory_limit = 256M/' "$php_ini"
grep -q '^post_max_size = 75M' "$php_ini" || sed -Ei 's/^post_max_size = [0-9]+M/post_max_size = 75M/' "$php_ini"
grep -q '^upload_max_filesize = 75M' "$php_ini" || sed -Ei 's/^upload_max_filesize = [0-9]+M/upload_max_filesize = 75M/' "$php_ini"
grep -q '^session.gc_probability = 0' "$php_ini" || sed -Ei 's/^session.gc_probability = 0/session.gc_probability = 1/' "$php_ini"

# MariaDB overrides
cp 60-aspen.cnf /etc/mysql/mariadb.conf.d/

a2enmod rewrite
systemctl restart apache2 mysql

# Create temp smarty directories
mkdir -p /usr/local/aspen-discovery/tmp
chown -R www-data:www-data /usr/local/aspen-discovery/tmp
chmod -R 755 /usr/local/aspen-discovery/tmp

# Raise process and open file limits for the aspen and solr users
cp solr_limits.conf /etc/security/limits.d/solr.conf
cp aspen_limits.conf /etc/security/limits.d/aspen.conf

# Setup logrotate
cp logrotate.conf /etc/logrotate.d/aspen_discovery

# Create aspen MySQL superuser
printf "Please enter the username for the Aspen MySQL superuser (cannot be root) : " >&2
read -r username
printf "Please enter the password for the Aspen MySQL superuser (%s) : " "$username" >&2
read -r password
query="GRANT ALL PRIVILEGES ON *.* TO '$username'@'localhost' IDENTIFIED BY '$password';"
mysql -e "$query"
query="GRANT ALL PRIVILEGES ON *.* TO '$username'@'127.0.0.1' IDENTIFIED BY '$password';"
mysql -e "$query"
mysql -e "flush privileges"

mysql_secure_installation

dpkg-reconfigure tzdata

./setup_aspen_user_debian.sh
