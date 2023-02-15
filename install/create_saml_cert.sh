#!/bin/sh

read -p "Create new SAML x.509 certificate? (y/N) " SAMLCERT
SAMLCERT="${SAMLCERT:=n}"
SAMLCERT=$(echo $SAMLCERT | tr '[:upper:]' '[:lower:]')
if [ $SAMLCERT = "y" ]; then
	openssl req -newkey rsa:2048 -new -x509 -sha256 -days 3652 -nodes -out /usr/local/aspen-discovery/code/web/services/Authentication/SAML/certs/sp.crt -keyout /usr/local/aspen-discovery/code/web/services/Authentication/SAML/certs/sp.key
fi