#!/bin/bash

if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

php ./updateConfig_26.10.00.php "$1"
