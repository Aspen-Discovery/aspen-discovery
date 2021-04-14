#!/bin/sh
#create a new user and user group to run Aspen Discovery
adduser aspen
#Add all existing users to the group
for ID in $(cat /etc/passwd | grep /home | cut -d ':' -f1); do (usermod -a -G aspen $ID);done

#create an aspen_apache group as well for files that need to be readable (and writable) by apache
groupadd aspen_apache
#Add apache to the aspen_apache group
usermod -a -G aspen_apache apache
usermod -a -G aspen_apache aspen
adduser solr
usermod -a -G aspen solr

#Change file permissions so /usr/local/aspen-discovery is owned by the aspen user
chown -R aspen:aspen /usr/local/aspen-discovery
#Now change files back for those that need apache to own them
chown -R apache:aspen_apache /usr/local/aspen-discovery/tmp
chown -R apache:aspen_apache /usr/local/aspen-discovery/code/web
chown -R apache:aspen_apache /usr/local/aspen-discovery/sites
chown -R aspen:aspen_apache /usr/local/aspen-discovery/sites/default
chown -R aspen:aspen_apache /usr/local/aspen-discovery/code/web/sitemaps
chown -R solr:aspen /usr/local/aspen-discovery/sites/default/solr-7.6.0

#Change file permissions so /data is owned by the aspen user
if [ ! -d /data/aspen-discovery ] ; then
  mkdir -p /data/aspen-discovery
fi
chown -R aspen:aspen_apache /data/aspen-discovery

#Change file permissions so /var/log/aspen-discovery is owned by the aspen user
if [ ! -d /var/log/aspen-discovery ] ; then
 mkdir -p /var/log/aspen-discovery
fi
chown -R aspen:aspen /var/log/aspen-discovery