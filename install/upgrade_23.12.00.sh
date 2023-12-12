#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

echo "Starting Upgrade 23.12.00.sh for $1"

/usr/sbin/service mysqld stop
sleep 10

yum remove -y MariaDB-server

cp mariadb.repo /etc/yum.repos.d/mariadb.repo
yum -y install MariaDB-server

/usr/sbin/service mysqld start
sleep 10

php /usr/local/aspen-discovery/install/runMariaDbUpgrade_23_12.php $1

yum-config-manager --enable remi-php83
yum update -y

/usr/sbin/apachectl restart
sleep 10

echo "Finished Upgrade 23.12.00.sh for $1"