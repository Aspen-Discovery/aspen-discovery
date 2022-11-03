#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

if ! grep -qF 'ServerSignature Off' '/etc/httpd/conf/httpd.conf'; then
	echo "ServerSignature Off" >> '/etc/httpd/conf/httpd.conf'
fi
if ! grep -qF 'ServerTokens Min' '/etc/httpd/conf/httpd.conf'; then
	echo "ServerTokens Min" >> '/etc/httpd/conf/httpd.conf'
fi
sed -i 's/expose_php = On/expose_php = Off/' '/etc/php.ini'

apt install -y php-imagick