#!/bin/bash

if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

sudo ./install_solr_9.sh
./update_startup_script_for_solr9.sh "$1"
