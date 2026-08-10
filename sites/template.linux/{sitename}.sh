#!/bin/bash

if [ -z "$1" ]
  then
    echo "To use, run with start, stop or restart for the first parameter."
fi

if [[ ( "$1" == "stop" ) || ( "$1" == "restart") ]]
  then
    /opt/solr/bin/solr stop -p {solrPort} -s "/data/aspen-discovery/{sitename}/solr7"
fi

if [[ ( "$1" == "start" ) || ( "$1" == "restart") ]]
  then
    /opt/solr/bin/solr start -Dsolr.modules=analysis-extras -m 3g -p {solrPort} -s "/data/aspen-discovery/{sitename}/solr7"
fi
