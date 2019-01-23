#!/bin/sh

if [ -z "$1" ]
  then
    echo "To use, run with start, stop or restart for the first parameter."
fi

if [[ ( "$1" == "stop" ) || ( "$1" == "restart") ]]
	then
		../default/solr/bin/solr stop -p 8180 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
		../default/solr/bin/solr stop -p 8080 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi

if [[ ( "$1" == "start" ) || ( "$1" == "restart") ]]
	then
		../default/solr/bin/solr start -m 24g -p 8180 -s "/data/vufind-plus/marmot.test/solr_master" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
		../default/solr/bin/solr start -m 24g -p 8080 -a "-Dsolr.masterport=8180" -s "/data/vufind-plus/marmot.test/solr_searcher" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi