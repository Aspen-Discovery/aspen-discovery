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
yum -y install php php-mcrypt php-gd php-curl php-mysql php-zip php-fileinfo php-soap
yum -y install php-xml
yum -y install bind-utils
yum -y install php-intl
yum -y install php-mbstring
yum -y install php-pecl-ssh2
yum -y install php-pgsql
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
cp my.cnf /etc/my.cnf
systemctl start mariadb
systemctl enable mariadb
yum -y install java-1.8.0-openjdk
yum -y install unzip
yum -y install strace
yum -y install mytop
yum -y install mysqltuner

#Create temp smarty directories
cd /usr/local/aspen-discovery
mkdir tmp
chown -R apache:apache tmp
chmod -R 755 tmp

#Disable SELinux
setenforce 0
cp install/selinux.config /etc/selinux/config

#Increase entropy
yum -y -q install rng-tools
cp install/limits.conf /etc/security/limits.conf
cp install/rngd.service /etc/systemd/system/multi-user.target.wants/rngd.service

systemctl daemon-reload
systemctl start rngd

yum -y install epel-release
yum -y install certbot python2-certbot-apache

echo "Generate new root password for mariadb at: https://passwordsgenerator.net/ and store in passbolt"
mysql_secure_installation
#echo "Setting timezone to Mountain Time, update as necessary with timedatectl set-timezone timezone"
echo "Enter the timezone of the server"
read timezone
timedatectl set-timezone $timezone

#Setup LogRotate
cp install/logrotate.conf /etc/logrotate.d/aspen_discovery


cd /usr/local/aspen-discovery/install
bash ./setup_aspen_user.sh

#upgrade OpenSSH to 9.0
yum groupinstall "Development Tools" -y
yum install zlib-devel openssl-devel -y

cp /etc/ssh/sshd_config  /etc/ssh/sshd_config
wget -c  https://cdn.openbsd.org/pub/OpenBSD/OpenSSH/portable/openssh-9.0p1.tar.gz
tar -xzf  openssh-9.0p1.tar.gz

cd openssh-9.0p1/
yum install pam-devel libselinux-devel -y
./configure  --with-pam --with-selinux --with-privsep-path=/var/lib/sshd/ --sysconfdir=/etc/ssh

make
make install

# Disable apache server signature
cp install/httpd.conf /etc/httpd/conf/httpd.conf
sudo systemctl restart httpd.service
