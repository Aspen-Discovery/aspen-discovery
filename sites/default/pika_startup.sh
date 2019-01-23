#!/bin/sh
### BEGIN INIT INFO
# Provides: pika
# Required-Start: mysqld httpd memcached
# Default-Start: 2 3 4 5
# Default-Stop: 0 1 6
# Description: Pika init script. (formerly known as VuFind)
#   Change {servername} to your server name.
#   Ensure the required-start daemons above match the daemon names on your server. use chkconfig --list
#   Move the file to /etc/init.d/
#   Rename as pika.sh, make executable.
#   Add to startup sequence with "chkconfig pika on"
### END INIT INFO

# Solr Engine for {servername} instance
cd /usr/local/vufind-plus/sites/{servername}
./{servername}.sh $*
# this script is passed a "start" or "stop" argument which is passed on to the pika script
