#!/bin/bash

# Solr install
# 1. Download the installer and extract
wget https://dlcdn.apache.org/solr/solr/9.10.1/solr-9.10.1.tgz
tar xzf solr-9.10.1.tgz solr-9.10.1/bin/install_solr_service.sh --strip-components=2

# 2. Run the installer (works on Debian, Ubuntu, RHEL, Rocky, CentOS). Will install into /var/solr and /opt/solr
sudo ./install_solr_service.sh solr-9.10.1.tgz
