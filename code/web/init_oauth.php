<?php
require_once 'bootstrap.php';
$provider = require_once ROOT_DIR . '/sys/Authentication/OAuthProvider.php';

global $logger;

if (!empty($_GET['error'])) {
	// Got an error, probably user denied access
	$logger->log('Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'), Logger::LOG_ERROR);
	exit('Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));
} elseif (empty($_GET['code'])) {
	// If we don't have an authorization code then get one
	$authUrl = $provider->getAuthorizationUrl();
	$_SESSION['oauth2state'] = $provider->getState();
	$logger->log($provider->getAuthorizationUrl(), Logger::LOG_ERROR);
	$logger->log($provider->getState(), Logger::LOG_ERROR);
	header('Location: ' . $authUrl);
	exit;
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
	// State is invalid, possible CSRF attack in progress
	unset($_SESSION['oauth2state']);
	$logger->log("Invalid state", Logger::LOG_ERROR);
	exit('Invalid state');
} else {
	// Try to get an access token (using the authorization code grant)
	$token = $provider->getAccessToken('authorization_code', [
		'code' => $_GET['code']
	]);
	$logger->log($token, Logger::LOG_ERROR);
	$_SESSION['token'] = serialize($token);
}