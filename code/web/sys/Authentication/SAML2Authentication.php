<?php

require_once '/usr/share/simplesamlphp/lib/_autoload.php';

class SAML2Authentication {

    public $as;

    public function __construct() {
        $this->as = new \SimpleSAML\Auth\Simple('default-sp');
    }

    public function authenticate($idp) {
		if (!$this->as->isAuthenticated() && $idp) {
            $this->as->login(array(
                'saml:idp' => $idp,
                'KeepPost' => FALSE,
                'ReturnTo' => '/saml2auth.php',
            ));
        } else {
            return false;
        }
    }

    public function SAMLConfigProperties() {
        $configProperties = [
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
	];
    return $configProperties;
    }

}

?>
