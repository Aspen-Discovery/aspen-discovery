#!/bin/sh

if [ -z "$1" ]
  then
    echo "To use, run with start, stop or restart for the first parameter."
fi

if [[ $(hostname -s) == "HOBVMPLAP23" ]]
  then
    SOLRMEM="26g"
elif [[ $(hostname -s) == "HOBVMPLAPT23" ]]
    then
      SOLRMEM="26g"
else
    SOLRMEM="20g"
fi

if [[ ( "$1" == "stop" ) || ( "$1" == "restart") ]]
	then
		../default/solr/bin/solr stop -p 8180 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
		../default/solr/bin/solr stop -p 8080 -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi

if [[ ( "$1" == "start" ) || ( "$1" == "restart") ]]
	then
		../default/solr/bin/solr start -m $SOLRMEM -p 8180 -s "/data/pika/nashville.test/solr_master" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
		../default/solr/bin/solr start -m $SOLRMEM -p 8080 -a "-Dsolr.masterport=8180" -s "/data/pika/nashville.test/solr_searcher" -d "/usr/local/vufind-plus/sites/default/solr/jetty"
fi
