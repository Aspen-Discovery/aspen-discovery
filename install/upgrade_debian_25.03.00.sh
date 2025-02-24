#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

echo "Setting up logrotate"
truncate -s0 /var/mail/aspen
truncate -s0 /var/mail/solr
truncate -s0 /var/mail/root

cp logrotate.conf /etc/logrotate.d/aspen_discovery
