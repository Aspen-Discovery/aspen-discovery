<?php

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Google;

require_once 'bootstrap.php';
require_once ROOT_DIR . '/services/Authentication/vendor/autoload.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';


global $configArray;
global $library;

$clientId = '';
$clientSecret = '';
$redirectUri = $configArray['Site']['url'] . '/Authentication/OAuth';

$ssoSettings = new SSOSetting();
$ssoSettings->id = $library->ssoSettingId;
$ssoSettings->service = "oauth";
if ($ssoSettings->find(true)) {
	$clientId = $ssoSettings->clientId;
	$clientSecret = $ssoSettings->clientSecret;
	if ($ssoSettings->oAuthGateway == "google") {
		$provider = new Google(compact('clientId', 'clientSecret', 'redirectUri'));
	} else {
		$config = [
			'urlAuthorize' => $ssoSettings->oAuthAuthorizeUrl,
			'urlAccessToken' => $ssoSettings->oAuthAccessTokenUrl,
			'clientId' => $ssoSettings->clientId,
			'clientSecret' => $ssoSettings->clientSecret ?? '',
			'redirectUri' => $redirectUri,
			'urlResourceOwnerDetails' => $ssoSettings->oAuthResourceOwnerUrl,
			'scopes' => $ssoSettings->oAuthScope
		];

		$provider = new GenericProvider($config);
	}

}

return $provider;

