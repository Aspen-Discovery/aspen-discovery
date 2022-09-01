#!/bin/sh 

 read -p "Configure SAML Single Sign On? (y/N) " SAMLSSO
 SAMLSSO="${SAMLSSO:=n}"
 SAMLSSO=$(echo $SAMLSSO | tr '[:upper:]' '[:lower:]')
 if [ $SAMLSSO = "y" ]; then
     apt-get install -y simplesamlphp
     rm /etc/simplesamlphp/config.php
     rm /etc/simplesamlphp/authsources.php
     read -p "Enter the SSO technical contact email: " ssoemail
     read -p "Enter a timezone (supported timezones can be found at http://php.net/manual/en/timezones.php): " ssotimezone
     read -p "Enter an SSO admin password: " ssoadminpwd
     /bin/bash /usr/local/aspen-discovery/install/samlsso_config.sh $ssoemail $ssotimezone $ssoadminpwd
     mkdir /etc/simplesamlphp/cert
     mkdir /etc/simplesamlphp/log
     mkdir /etc/simplesamlphp/data
     echo "Enter SAML certificate details\n"
     openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out /etc/simplesamlphp/cert/saml.crt -keyout /etc/simplesamlphp/cert/saml.pem
     chgrp www-data /etc/simplesamlsso/cert/saml.pem
     chmod 640 /etc/simplesamlsso/cert/saml.pem
 fi 
