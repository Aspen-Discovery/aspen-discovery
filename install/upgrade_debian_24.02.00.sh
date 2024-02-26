#!/bin/sh
if [ -z "$1" ]; then
	echo "Please provide the server name to update as the first argument."
	exit 1
fi

apt-get update
yes | apt-get install -y openjdk-11-jdk
pkill -9 java
apt-get -y remove openjdk-8-jdk
apt-get -y remove openjdk-8-jre-headless

chown -R solr:solr /usr/local/aspen-discovery/sites/default/solr-8.11.2

#Update the startup script to call solr 8 rather than solr 7
echo "updating startup script to use solr 8"
sed -i -e "s/7.6.0/8.11.2/g" /usr/local/aspen-discovery/sites/$1/$1.sh

truncate -s0 /var/mail/aspen
truncate -s0 /var/mail/solr
truncate -s0 /var/mail/root

cp install/logrotate.conf /etc/logrotate.d/aspen_discovery
