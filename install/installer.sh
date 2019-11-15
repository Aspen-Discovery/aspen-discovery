#!/bin/sh

#This will need to be copied to the server manually to do the setup.
#Expects to be installed on CentOS8
#Run as sudo ./installer.sh
yum check-update
yum -y install wget
yum -y install httpd
yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum -y install yum-utils
yum-config-manager --enable remi-php73
yum -y install php php-mcrypt php-gd php-curl php-mysql php-zip php-fileinfo
yum -y install php-xml
service httpd start
chkconfig httpd on
# New PHP ini file
# - Change max_memory to 256M (from 128M)
# - Increase max file size to 50M
# - Increase max post size to 50M
mv /etc/php.ini /etc/php.ini.old
cp php.ini /etc/php.ini
cp mariadb.repo /etc/yum.repos.d/mariadb.repo
yum -y install MariaDB-server
mv /etc/my.cnf /etc/my.cnf.old
mv my.cnf /etc/my.cnf
systemctl start mariadb
systemctl enable mariadb
yum -y install java-1.8.0-openjdk
yum -y install unzip

#Create temp smarty directories
cd /usr/local/aspen-discovery
mkdir tmp
chown -R apache:apache tmp
chmod -R 755 tmp

#Disable SELinux
setenforce 0
cp install/selinux.config /etc/selinux/config

echo "Generate new root password for mariadb at: https://passwordsgenerator.net/ and store in passbolt"
mysql_secure_installation
#echo "Setting timezone to Mountain Time, update as necessary with timedatectl set-timezone timezone"
echo "Enter the timezone of the server"
read timezone
timedatectl set-timezone $timezone


