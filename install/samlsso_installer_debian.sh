#!/bin/bash 

 read -p "Configure SAML Single Sign On? (y/N) " SAMLSSO
 SAMLSSO="${SAMLSSO:=n}"
 SAMLSSO=$(echo $SAMLSSO | tr '[:upper:]' '[:lower:]')
 if [ $SAMLSSO = "y" ]; then
     apt-get install -y simplesamlphp
     rm /etc/simplesamlphp/config.php
     rm /etc/simplesamlphp/authsources.php
	 cp /usr/local/aspen-discovery/install/saml20-idp-remote.php /etc/simplesamlphp/metadata/
     read -p "Enter the SSO technical contact email: " ssoemail
     read -p "Enter a timezone (supported timezones can be found at http://php.net/manual/en/timezones.php): " ssotimezone
     read -p "Enter an SSO admin password: " ssoadminpwd
     read -p "Enter server name: " ssoservername
     /usr/local/aspen-discovery/install/samlsso_config.sh $ssoemail $ssotimezone $ssoadminpwd $ssoservername
     mkdir -p /etc/simplesamlphp/cert
     mkdir -p /etc/simplesamlphp/log
     mkdir -p /etc/simplesamlphp/data
     echo "Enter SAML certificate details\n"
     openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out /etc/simplesamlphp/cert/saml.crt -keyout /etc/simplesamlphp/cert/saml.pem
     chgrp www-data /etc/simplesamlphp/cert/saml.pem
     chmod 640 /etc/simplesamlphp/cert/saml.pem
 fi 
