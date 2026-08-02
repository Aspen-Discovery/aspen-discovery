#!/bin/bash

if [ -z "$1" ]
  then
    echo "To use, run with start, stop or restart for the first parameter."
fi

if [[ ( "$1" == "stop" ) || ( "$1" == "restart") ]]
  then
    /opt/solr/bin/solr stop -p 8080 -s "/data/aspen-discovery/ptfse.dev/solr7"
fi

if [[ ( "$1" == "start" ) || ( "$1" == "restart") ]]
  then
    /opt/solr/bin/solr start -Dsolr.modules=analysis-extras -m 3g -p 8080 -s "/data/aspen-discovery/ptfse.dev/solr7"
fi
