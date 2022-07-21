<?php

	require_once 'bootstrap.php';
    require_once ROOT_DIR . '/sys/Authentication/SAML2Authentication.php';

	$auth = new SAML2Authentication();
	$attr = $auth->attributes();
	//print_r($attr);

	global $library;
	//$ssoUniqueAttribute = $library->ssoUniqueAttribute;

	setcookie("ssoUniqueAttribute", $attr[$library->ssoUniqueAttribute][0]);
	header("Location: /?samlLogin=v");
	//print_r($attr[$ssoUniqueAttribute][0]);
	


	/*$session = SimpleSAML_Session::getSessionFromRequest();
	print_r($session);
	$session->cleanup();
	print_r($session);
	print_r($attr);*/

	//print_r($attr["urn:oid:0.9.2342.19200300.100.1.3"][0]);

	/*$session = SimpleSAML_Session::getSessionFromRequest();
	$session->cleanup();*/
	//session_start();
	//$_SESSION["sso_email"] = $attr["urn:oid:0.9.2342.19200300.100.1.3"][0];
	//echo "sso_email: ".$_SESSION["sso_email"];
	//die;

	//setcookie("barbara", "For the love of god where have the attributes gone?");
	//setcookie("jeremy", $attr["urn:oid:0.9.2342.19200300.100.1.3"][0]);
	
	//header("Location: /?samlLogin=v");

