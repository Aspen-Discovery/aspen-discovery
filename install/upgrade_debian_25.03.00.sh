#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

echo "Setting up logrotate"
truncate -s0 /var/mail/aspen
truncate -s0 /var/mail/solr
truncate -s0 /var/mail/root

cp logrotate.conf /etc/logrotate.d/aspen_discovery

php_vers="8.0"
php_ini="/etc/php/${php_vers}/apache2/php.ini"
grep -q '^;max_input_vars = 1000' "$php_ini" || sed -Ei 's/^;max_input_vars = 1000/max_input_vars = 5000/' "$php_ini"
