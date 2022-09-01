<?php

require_once '/usr/share/simplesamlphp/lib/_autoload.php';

class SAML2Authentication {

    public $as;
	public $configProperties;
	public $requestMap;

    public function __construct() {
        $this->as = new \SimpleSAML\Auth\Simple('default-sp');
		$this->configProperties = $this->SAMLConfigProperties();
    }

    public function authenticate($idp) {
		if ($idp) {
            $this->as->login(array(
                'saml:idp' => $idp,
                'KeepPost' => FALSE,
                'ReturnTo' => '/saml2auth.php',
            ));
        } else {
            return false;
        }
    }

	// Return an associative array of values supplied by the IdP
	// keyed on our attribute schema
	public function getAttributeValues() {
		global $library;
		$out = [];
		$usersAttributes = $this->as->getAttributes();
		foreach ($this->configProperties as $prop => $content) {
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
		return $out;
	}

	/*
		Define how to derive values from the IdP response
		The default is to use the config value that corresponds with
		the key name. For example, to derive the value for the 'ssoEmailAttr'
		property, look in the config's 'ssoEmailAttr'.
		However, some values need to be derived by other means, 'ssoDisplayNameAttr'
		for example defines a function that will construct the display name from other
		values. ssoPatronTypeAttr has a fallback value, that value also comes from
		the config, but from the 'ssoPatronTypeFallback' property
	*/
    private function SAMLConfigProperties() {
        $configProperties = [
			'ssoUniqueAttribute' => [],
			'ssoIdAttr'          => [],
			'ssoUsernameAttr'    => [
				'aspenUser' => 'username'
			],
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
		];
		return $configProperties;
    }

}

?>
