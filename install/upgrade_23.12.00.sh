#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

service mysqld stop

yum remove -y MariaDB-server

cp mariadb.repo /etc/yum.repos.d/mariadb.repo
yum -y install MariaDB-server

service mysqld start

php /usr/local/aspen-discovery/install/runMariaDbUpgrade_23_12.php $1

yum-config-manager --enable remi-php83
yum update -y

apachectl restart