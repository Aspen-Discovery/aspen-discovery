#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

printf "Updating session garbage collection probability\n"
php_version="8.0"
php_ini="/etc/php/${php_version}/apache2/php.ini"
grep -q '^session.gc_probability = 0' "$php_ini" || sed -Ei 's/^session.gc_probability = 0/session.gc_probability = 1/' "$php_ini"
