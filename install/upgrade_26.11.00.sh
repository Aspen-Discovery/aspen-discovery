#!/bin/bash

# Install Composer dependencies (async-aws/s3 for S3 storage) from the repo root, where composer.json now lives
dnf install -y composer
mkdir -p /usr/share/httpd/.composer/cache
chown -R apache:apache /usr/share/httpd/.composer
cd /usr/local/aspen-discovery || exit
runuser -u apache -- composer install --no-interaction --prefer-dist --no-dev
runuser -u apache -- composer check-platform-reqs --no-dev
