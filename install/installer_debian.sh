#!/bin/sh

#This will need to be copied to the server manually to do the setup.
#Expects to be installed on Debian 10 Buster
#Run as sudo ./installer.sh
apt update
apt install -y wget
apt install -y apache2
apt -y install apt-transport-https lsb-release ca-certificates curl
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
apt update
apt install -y php7.3 php7.3-mcrypt php7.3-gd php7.3-curl php7.3-mysql php7.3-zip
apt install -y php7.3-xml
apt install -y bind9 bind9utils
apt install -y php7.3-intl
apt install -y php7.3-mbstring
apt install -y php7.3-pgsql
apt install -y php-ssh2
service apache2 start
systemctl enable apache2
# New PHP ini file
# - Change max_memory to 256M (from 128M)
# - Increase max file size to 50M
# - Increase max post size to 50M
mv /etc/php/7.3/apache2/php.ini /etc/php/7.3/apache2/php.ini.old
cp php.ini /etc/php/7.3/apache2/php.ini
a2enmod rewrite
apt install -y mariadb-server
mv /etc/mysql/mariadb.cnf /etc/mysql/mariadb.cnf.old
cp mariadb.cnf /etc/mysql/mariadb.cnf
systemctl start mariadb
systemctl enable mariadb
apt install -y software-properties-common
apt install -y default-jdk
apt install -y openjdk-11-jdk
apt install -y unzip

#Create temp smarty directories
cd /usr/local/aspen-discovery
mkdir tmp
chown -R www-data:www-data tmp
chmod -R 755 tmp

#Increase entropy
apt install -y -q rng-tools
cp install/limits.conf /etc/security/limits.conf
cp install/rngd.service /usr/lib/systemd/system/rngd.service

systemctl daemon-reload
systemctl start rngd

apt install -y python-certbot-apache

read -p "Configure SAML Single Sign On? (y/N) " SAMLSSO
SAMLSSO="${SAMLSSO:=n}"
SAMLSSO=$(echo $SAMLSSO | tr '[:upper:]' '[:lower:]')
if [ $SAMLSSO = "y" ]; then
    apt-get install -y simplesamlphp
    rm /etc/simplesamlphp/config.php
    rm /etc/simplesamlphp/authsources.php
    read -p "Enter the SSO technical contact email: " ssoemail
    read -p "Enter a timezone (supported timezones can be found at http://php.net/manual/en/timezones.php): " ssotimezone
    read -p "Enter an SSO admin password: " ssoadminpwd
    /bin/bash /usr/local/aspen-discovery/install/samlsso_config.sh $ssoemail $ssotimezone $ssoadminpwd
    mkdir /etc/simplesamlphp/cert
    mkdir /etc/simplesamlphp/log
    mkdir /etc/simplesamlphp/data
    echo "Enter SAML certificate details\n"
    openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out /etc/simplesamlphp/cert/saml.crt -keyout /etc/simplesamlphp/cert/saml.pem
    chgrp www-data /etc/simplesamlsso/cert/saml.pem
    chmod 640 /etc/simplesamlsso/cert/saml.pem
fi

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
