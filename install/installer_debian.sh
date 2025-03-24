#!/bin/sh

git config --global --add safe.directory /usr/local/aspen-discovery

#Expects to be installed on Debian 11 Bullseye or later
#Run as sudo ./installer_debian.sh
apt-get update
apt-get -y install cron wget rsyslog gpg openjdk-17-jre-headless apache2 certbot python3-certbot-apache mariadb-server apt-transport-https lsb-release ca-certificates zip

# modify version as needed
./debian_install_php.sh 8.4

# MariaDB overrides
cp 60-aspen.cnf /etc/mysql/mariadb.conf.d/

a2enmod rewrite
systemctl restart apache2 mysql

# Create temp smarty directories
mkdir -m 0755 -p /usr/local/aspen-discovery/tmp
chown -R www-data:www-data /usr/local/aspen-discovery/tmp

# logrotate setup
cp logrotate-debian.conf /etc/logrotate.d/aspen_discovery

# Raise process and open file limits for the aspen and solr users
cp solr_limits.conf /etc/security/limits.d/solr.conf
cp aspen_limits.conf /etc/security/limits.d/aspen.conf

# Install ClamAV?
printf "Install ClamAV virus scanner? [Y/n] " >&2
read -r instaclam
if ! echo "$instaclam" | cut -c 1 | grep -i n >/dev/null ; then
  apt-get -y install clamav clamav-daemon
fi

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

