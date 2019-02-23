#!/bin/sh
# Copies needed solr files to the server specified as a command line argument
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
fi
rm -f /data/aspen-discovery/$1/solr_master/lib/*
cp -r solr_master /data/aspen-discovery/$1
rm -f /data/aspen-discovery/$1/solr_searcher/lib/*
cp -r solr_searcher /data/aspen-discovery/$1
