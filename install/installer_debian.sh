#!/bin/sh

#This will need to be copied to the server manually to do the setup.
#Expects to be installed on Debian 10 Buster
#Run as sudo ./installer.sh
apt-get update
apt-get install -y wget
apt-get install -y apache2
apt-get -y install apt-transport-https lsb-release ca-certificates curl
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
apt-get update
apt-get install -y php7.3 php7.3-mcrypt php7.3-gd php7.3-curl php7.3-mysql php7.3-zip
apt-get install -y php7.3-xml
apt-get install -y bind9 bind9utils
apt-get install -y php7.3-intl
apt-get install -y php7.3-mbstring
service apache2 start
systemctl enable apache2
# New PHP ini file
# - Change max_memory to 256M (from 128M)
# - Increase max file size to 50M
# - Increase max post size to 50M
mv /etc/php/7.3/apache2/php.ini /etc/php/7.3/apache2/php.ini.old
cp php.ini /etc/php/7.3/apache2/php.ini
a2enmod rewrite
apt-get install -y mariadb-server
mv /etc/mysql/mariadb.cnf /etc/mysql/mariadb.cnf.old
cp mariadb.cnf /etc/mysql/mariadb.cnf
systemctl start mariadb
systemctl enable mariadb
apt-get install -y software-properties-common
apt-get install -y default-jdk
apt-get install -y openjdk-11-jdk
apt-get install -y unzip

#Create temp smarty directories
cd /usr/local/aspen-discovery
mkdir tmp
chown -R www-data:www-data tmp
chmod -R 755 tmp

#Increase entropy
apt-get install -y -q rng-tools
cp install/limits.conf /etc/security/limits.conf
cp install/rngd.service /usr/lib/systemd/system/rngd.service

systemctl daemon-reload
systemctl start rngd

apt-get install -y python-certbot-apache

echo "Generate new root password for mariadb at: https://passwordsgenerator.net/ and store in passbolt"
mysql_secure_installation
echo "Enter the timezone of the server"
read timezone
timedatectl set-timezone $timezone

#Create aspen MySQL superuser
read -p "Please enter the username for the Aspen MySQL superuser (can't be root) : " username
read -p "Please enter the password for the Aspen MySQL superuser ($username) : " password
query="GRANT ALL PRIVILEGES ON *.* TO $username@'localhost' IDENTIFIED BY '$password'";
mysql -e "$query"
query="GRANT ALL PRIVILEGES ON *.* TO $username@'127.0.0.1' IDENTIFIED BY '$password'";
mysql -e "$query"
mysql -e "flush privileges"

cd /usr/local/aspen-discovery/install
bash ./setup_aspen_user_debian.sh