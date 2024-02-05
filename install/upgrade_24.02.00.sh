#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

yum install -y java-11-openjdk
pkill -9 java
yum -y remove java-1.8.0-openjdk
yum -y remove java-1.8.0-openjdk-headless.x86_64
#update-alternatives --set java /usr/lib/jvm/java-11-openjdk-11.0.21.0.9-1.el7_9.x86_64/bin/java

chown -R solr:solr /usr/local/aspen-discovery/sites/default/solr-8.11.2

#Update the startup script to call solr 8 rather than solr 7
echo "updating startup script to use solr 8"
sed -i -e "s/7.6.0/8.11.2/g" /usr/local/aspen-discovery/sites/$1/$1.sh
