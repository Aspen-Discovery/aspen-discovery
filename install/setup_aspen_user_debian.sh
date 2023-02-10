#!/bin/sh

#create an aspen_apache group for files that need to be readable (and writable) by apache
grep -q aspen_apache /etc/group || groupadd -r aspen_apache

#Add www-data to the aspen_apache group
usermod -a -G aspen_apache www-data

#create a new user and user group to run Aspen Discovery
grep -q aspen /etc/passwd || useradd -m -s /bin/bash -G aspen_apache aspen
#Add all existing users to the aspen group
for ID in $(cat /etc/passwd | grep /home | cut -d ':' -f1); do (usermod -a -G aspen $ID);done

# Solr service user
grep -q solr /etc/passwd || useradd -r -s /bin/bash -G aspen solr

#Change file permissions so /usr/local/aspen-discovery is owned by the aspen user
chown -R aspen:aspen /usr/local/aspen-discovery
#Now change files back for those that need apache to own them
chown -R www-data:aspen_apache /usr/local/aspen-discovery/tmp
chown -R www-data:aspen_apache /usr/local/aspen-discovery/code/web
chown -R www-data:aspen_apache /usr/local/aspen-discovery/sites
chown -R aspen:aspen_apache /usr/local/aspen-discovery/sites/default
chown -R solr:aspen /usr/local/aspen-discovery/sites/default/solr-7.6.0

#Change file permissions so /data is owned by the aspen user
mkdir -p /data/aspen-discovery
chown -R aspen:aspen_apache /data/aspen-discovery

#Change file permissions so /var/log/aspen-discovery is owned by the aspen user
mkdir -p /var/log/aspen-discovery
chown -R aspen:aspen /var/log/aspen-discovery
