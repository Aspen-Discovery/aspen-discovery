#!/bin/bash
targetpath="/etc/simplesamlphp"
sourcepath="/usr/local/aspen-discovery/install"
if [ -z "$1" ]
  then
    echo "Please provide a Single Sign On email as the first argument."
    exit 1
fi
if [ -z "$2" ]
  then
    echo "Please provide a timezone as the second argument."
    exit 1
fi
if [ -z "$3" ]
  then
    echo "Please provide a Single Sign On admin password as the third argument."
    exit 1
fi
cp $sourcepath/samlsso_config.php $targetpath/config.php

escape_email=$(sed 's/[^[:alnum:]]/\\&/g' <<<"$2")
escape_timezone=$(sed 's/[^[:alnum:]]/\\&/g' <<<"$3")
escape_adminpassword=$(sed 's/[^[:alnum:]]/\\&/g' <<<"$4")

random_string() {
    local l=15
	  [ -n "$1" ] && l=$1
    [ -n "$2" ] && l=$(shuf --random-source=/dev/urandom -i $1-$2 -n 1)
    tr -dc A-Za-z0-9 < /dev/urandom | head -c ${l} | xargs
}
ssosecret=$(random_string 32)

sed -i "s/SSO_NAME/$escape_name/g" $targetpath/config.php
sed -i "s/SSO_EMAIL/$escape_email/g" $targetpath/config.php
sed -i "s/SSO_TIMEZONE/$escape_timezone/g" $targetpath/config.php
sed -i "s/SSO_SECRETSALT/$ssosecret/g" $targetpath/config.php
sed -i "s/SSO_ADMINPASSWORD/$escape_adminpassword/g" $targetpath/config.php

cp $sourcepath/samlsso_authsources.php $targetpath/authsources.php
