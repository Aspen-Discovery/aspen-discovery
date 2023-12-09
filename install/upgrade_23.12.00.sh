#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

yum-config-manager --enable remi-php83

cp mariadb.repo /etc/yum.repos.d/mariadb.repo
yum update -y