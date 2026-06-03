#!/bin/bash

yum install -y composer
composer --version
cd /usr/local/aspen-discovery/code/web || exit
runuser -uapache -- composer install --no-interaction --prefer-dist
runuser -uapache -- composer check-platform-reqs
