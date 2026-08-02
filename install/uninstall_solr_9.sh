#!/usr/bin/env bash
sudo systemctl stop solr
sudo systemctl disable solr

sudo rm /etc/systemd/system/solr.service
sudo rm /etc/init.d/solr
sudo systemctl daemon-reload
sudo systemctl reset-failed

sudo rm -rf /opt/solr
sudo rm -rf /opt/solr-9.10.1
sudo rm -f /etc/default/solr.in.sh

sudo rm -rf /var/solr
