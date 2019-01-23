#!/bin/sh
# Copies needed solr files to the server specified as a command line argument
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
fi
rm -f /data/vufind-plus/$1/solr_master/lib/*
cp -r solr_master /data/vufind-plus/$1
rm -f /data/vufind-plus/$1/solr_searcher/lib/*
cp -r solr_searcher /data/vufind-plus/$1
