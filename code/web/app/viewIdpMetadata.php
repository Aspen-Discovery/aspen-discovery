<?php

	require_once '../bootstrap.php';
	require_once '../sys/Authentication/SAML2Authentication.php';

	global $library;

	$auth = new SAML2Authentication('/app/viewIdpMetadata.php');

	// If we need to forward the user to an IdP
	$url = $library->ssoXmlUrl;
	if (
		array_key_exists('samlLogin', $_REQUEST) &&
		array_key_exists('idp', $_REQUEST) &&
		strlen($_REQUEST['samlLogin']) > 0 &&
		strlen($_REQUEST['idp']) > 0 &&
		$url &&
		strlen($url) > 0
	) {
		$auth->authenticate($_REQUEST['idp']);
		header('Location: /');
	}

	// We are processing the result of authenticating
	// with the IdP

	// Get the attributes from the IdP response
	$usersAttributes = $auth->as->getAttributes();

	// Someone has most likely hit this script directly, rather than having
	// come back from an IdP
	if (count($usersAttributes) == 0) {
		$logger->log("No SSO attributes found",  Logger::LOG_ERROR);
		header('Location: /');
	}

	print "<pre>";
	print_r($usersAttributes);
