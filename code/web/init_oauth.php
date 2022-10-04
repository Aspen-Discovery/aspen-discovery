<?php
require_once 'bootstrap.php';
require_once ROOT_DIR . '/sys/BotChecker.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
require_once ROOT_DIR . '/sys/Authentication/OAuthAuthentication.php';

global $logger;
global $library;

$auth = new OAuthAuthentication();

if (!empty($_GET['error'])) {
	// Got an error, probably user denied access
	$logger->log('Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'), Logger::LOG_ERROR);
	header('Location: ' . '/Search/Home');
	exit;
} elseif (empty($_GET['code'])) {
	// If we don't have an authorization code then get one
	$SSOSetting = new SSOSetting();
	$SSOSetting->id = $library->ssoSettingId;
	if ($SSOSetting->find(true)) {
		$authUrl = $auth->getAuthorizationRequestUrl($SSOSetting);
		$logger->log($authUrl, Logger::LOG_ERROR);
		header('Location: ' . $authUrl);
		exit;
	}
	header('Location: ' . '/Search/Home');
	exit;
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
	// State is invalid, possible CSRF attack in progress
	unset($_SESSION['oauth2state']);
	$logger->log("Invalid state", Logger::LOG_ERROR);
	exit('Invalid state');
} else {
	// Try to get an access token (using the authorization code grant)
	$SSOSetting = new SSOSetting();
	$SSOSetting->id = $library->ssoSettingId;
	if ($SSOSetting->find(true)) {
		$requestOptions = [
			'client_id' => $SSOSetting->clientId,
			'client_secret' => $SSOSetting->clientSecret,
			'grant_type' => 'authorization_code',
			'code' => $_GET['code'],
			'redirect_uri' => $SSOSetting->getRedirectUrl(),
		];
		$token = $auth->getAccessToken($SSOSetting->getAccessTokenUrl(), $requestOptions, true);
		$logger->log($token, Logger::LOG_ERROR);
		$_SESSION['token'] = serialize($token);
	}
}