#!/bin/bash
if [[ $# -ne 1 ]]; then
    echo "Please specify the instance"
    echo "eg: $0 aspen.demo"
  else
    ASPENSERVER=$1
    wget --spider --timeout=30 --no-verbose --no-check-certificate -i /usr/local/vufind-plus/sites/${ASPENSERVER}/NYTimesURLsList.txt
fi
