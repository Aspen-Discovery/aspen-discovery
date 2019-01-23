#!/bin/bash
if [[ $# -ne 1 ]]; then
    echo "Please specify the Pika instance"
    echo "eg: $0 marmot.production"
  else
    PIKASERVER=$1
    wget --spider --timeout=30 --no-verbose --no-check-certificate -i /usr/local/vufind-plus/sites/${PIKASERVER}/NYTimesURLsList.txt
fi
