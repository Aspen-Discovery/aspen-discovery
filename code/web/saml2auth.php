<?php
	require_once 'bootstrap.php';
    require_once ROOT_DIR . '/sys/Authentication/SAML2Authentication.php';

	global $library;

	// An associative array containing all the keys for config values that
	// describe IdP attributes where data can be found
	// We can also derive a fallback value from values supplied by the admin
	// or from an anonymous function
	/*$configProperties = [
		'ssoUniqueAttribute' => [],
		'ssoIdAttr'          => [],
		'ssoUsernameAttr'    => [],
		'ssoFirstnameAttr'   => [],
		'ssoLastnameAttr'    => [],
		'ssoEmailAttr'       => [],
		'ssoDisplayNameAttr' => [
			'fallback' => [
				'propertyName' => 'ssoDisplayNameFallback',
				// Assemble a display name from first and last names
				'func' => function($usersAttributes, $library) {
					$comp = [
						$usersAttributes[$library->ssoFirstnameAttr][0],
						$usersAttributes[$library->ssoLastnameAttr][0]
					];
					return implode(' ', $comp);
				}
			]
		],
		'ssoPhoneAttr'       => [],
		'ssoPatronTypeAttr'  => [
			'fallback' => [
				'propertyName' => 'ssoPatronTypeFallback'
			]
		],
		'ssoAddressAttr'     => [],
		'ssoCityAttr'        => [],
		'ssoLibraryIdAttr'   => [
			'fallback' => [
				'propertyName' => 'ssoLibraryIdFallback'
			]
		],
		'ssoCategoryIdAttr'  => [
			'fallback' => [
				'propertyName' => 'ssoCategoryIdFallback'
			]
		]
	];*/

	$configProperties = SAML2Authentication::SAMLConfigProperties();

	// Get the attributes from the IdP response
	$auth = new SAML2Authentication();
	$usersAttributes = $auth->as->getAttributes();

	// Create an associative array containing populated values
	// from the supplied IdP attributes
	$out = [];
	foreach ($configProperties as $prop => $content) {
		// The attribute name in the config supplied by the admin
		$attrName = $library->$prop;
		// The value of this attribute from the IdP
		$attrValArray = strlen($attrName) > 0 ?
			$usersAttributes[$attrName] :
			[];
		// If we have a value for this attribute from the IdP,
		// use it
		if (isset($attrValArray) && count($attrValArray) == 1) {
			if (strlen($attrValArray[0]) > 0) {
				$out[$prop] = $attrValArray[0];
			}
		// If a fallback has been supplied, use that
		} else if (array_key_exists('fallback', $content)) {
			$fallback = $content['fallback'];
			$propertyName = $fallback['propertyName'];
			// It might be an anonymous function, or a reference
			// to a fallback value
			$out[$propertyName] = (array_key_exists('func', $fallback)) ?
				$fallback['func']($usersAttributes, $library) :
				$library->$propertyName;
		}
	}

	// Set a cookie for each value we want to pass forward, set them to
	// expire in 20 seconds so the browser will remove them
	foreach ($out as $key => $value) {
		setcookie($key, $value, time() + 20);
	}

	/*print_r("<pre>");
	print_r($out);
	die;*/

	header("Location: /?samlLogin=v");
?>