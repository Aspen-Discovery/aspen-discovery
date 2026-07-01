#!/bin/bash

dnf install -y composer
mkdir -p /usr/share/httpd/.composer/cache
chown -R apache:apache /usr/share/httpd/.composer
runuser -u apache -- composer --version
cd /usr/local/aspen-discovery/code/web || exit
runuser -u apache -- composer install --no-interaction --prefer-dist
runuser -u apache -- composer check-platform-reqs
