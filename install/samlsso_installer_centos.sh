#!/bin/sh 

read -p "Configure SAML Single Sign On? (y/N) " SAMLSSO
SAMLSSO="${SAMLSSO:=n}"
SAMLSSO=$(echo $SAMLSSO | tr '[:upper:]' '[:lower:]')
if [ $SAMLSSO = "y" ]; then
	mkdir /tmp/simplesamlphp
	mkdir -p /etc/simplesamlphp/{cert,log,data,metadata}
	curl -sL https://github.com/simplesamlphp/simplesamlphp/releases/download/v1.19.7/simplesamlphp-1.19.7.tar.gz --output /tmp/simplesamlphp/simplesamlphp-1.19.7.tar.gz
	tar -xzf /tmp/simplesamlphp/simplesamlphp-1.19.7.tar.gz -C /usr/share
	mv /usr/share/simplesamlphp-1.19.7 /usr/share/simplesamlphp
	cp /usr/local/aspen-discovery/install/saml20-idp-remote.php /etc/simplesamlphp/metadata/
	rm -rf /tmp/simplesamlphp

	read -p "Enter the SSO technical contact email: " ssoemail
	read -p "Enter a timezone (supported timezones can be found at http://php.net/manual/en/timezones.php): " ssotimezone
	read -p "Enter an SSO admin password (no spaces allowed): " ssoadminpwd
	read -p "Enter server name: " ssoservername
	/bin/bash /usr/local/aspen-discovery/install/samlsso_config.sh $ssoemail $ssotimezone $ssoadminpwd $ssoservername
	echo "Enter SAML certificate details\n"
	openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out /etc/simplesamlphp/cert/saml.crt -keyout /etc/simplesamlphp/cert/saml.pem
	chgrp aspen_apache /etc/simplesamlphp/cert/saml.pem
	chmod 640 /etc/simplesamlphp/cert/saml.pem
fi 