<?php
require_once 'UnknownAuthenticationMethodException.php';

class AuthenticationFactory {

	static function initAuthentication($authNHandler, $additionalInfo = []) {
		switch (strtoupper($authNHandler)) {
			case "DB":
				require_once 'DatabaseAuthentication.php';
				return new DatabaseAuthentication($additionalInfo);
			case "SIP2":
				require_once 'SIPAuthentication.php';
				return new SIPAuthentication($additionalInfo);
			case "ILS":
				require_once 'ILSAuthentication.php';
				return new ILSAuthentication($additionalInfo);
			default:
				throw new UnknownAuthenticationMethodException('Authentication handler ' + $authNHandler + 'does not exist!');
		}
	}
}