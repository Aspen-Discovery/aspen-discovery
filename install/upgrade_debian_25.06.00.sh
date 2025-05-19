#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

printf "Updating session garbage collection probability\n"

# Update the file for the current PHP version
php_version=$(ls /etc/php/ | sort -V | tail -n 1)
php_ini="/etc/php/${php_version}/apache2/php.ini"

# Update session.gc_probability if it exists, or add it if it doesn't
grep -q '^session.gc_probability' "$php_ini"
if [ $? -eq 0 ]; then
  echo "Found existing PHP config option session.gc_probability, updating value to 1"
  sed -i 's/^session\.gc_probability\s*=\s*[0-9]\+/session.gc_probability = 1/' "$php_ini"
else
  echo "PHP config option session.gc_probability not found, adding it with value of 1"
  echo "session.gc_probability = 1" >> "$php_ini"
fi
