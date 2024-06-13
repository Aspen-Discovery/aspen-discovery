#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

chown -R aspen:aspen_apache /usr/local/aspen-discovery/code/web/sitemaps

