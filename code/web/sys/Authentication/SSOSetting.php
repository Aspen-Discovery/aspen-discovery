<?php

require_once ROOT_DIR . '/sys/Authentication/SSOMapping.php';

class SSOSetting extends DataObject {
	public $__table = 'sso_setting';
	public $id;
	public $name;
	public $service;

	//oAuth
	public $clientId;
	public $clientSecret;
	public $oAuthGateway;
	public $mappingSettingId;
	public $staffOnly;

	//oAuth Custom Gateway
	public $oAuthAuthorizeUrl;
	public $oAuthAccessTokenUrl;
	public $oAuthResourceOwnerUrl;
	public $oAuthLogoutUrl;
	public $oAuthScope;
	public $oAuthGrantType;
	public $oAuthPrivateKeys;
	public $oAuthGatewayLabel;
	public $oAuthGatewayIcon;
	public $oAuthButtonBackgroundColor;
	public $oAuthButtonTextColor;

	//SAML
	public $ssoName;
	public $ssoXmlUrl;
	public $ssoUniqueAttribute;
	public $ssoMetadataFilename;
	public $ssoEntityId;
	public $ssoIdAttr;
	public $ssoUsernameAttr;
	public $ssoFirstnameAttr;
	public $ssoLastnameAttr;
	public $ssoEmailAttr;
	public $ssoDisplayNameAttr;
	public $ssoPhoneAttr;
	public $ssoPatronTypeAttr;
	public $ssoPatronTypeFallback;
	public $ssoAddressAttr;
	public $ssoCityAttr;
	public $ssoLibraryIdAttr;
	public $ssoLibraryIdFallback;
	public $ssoCategoryIdAttr;
	public $ssoCategoryIdFallback;

	public $loginHelpText;
	public $loginOptions;

	private $_libraries;
	private $_dataMapping;

	public static function getObjectStructure(): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$fieldMapping = SSOMapping::getObjectStructure();

		$services = [
			'oauth' => 'OAuth 2.0',
			'saml' => 'SAML 2',
		];

		$oauth_gateways = [
			'google' => 'Google',
			'custom' => 'Custom',
		];

		$login_options = [
			'0' => 'Both SSO and Local Login',
			'1' => 'Only SSO Login',
		];

		$authentication_grant_type = [
			'0' => 'By Authorization Code (Standard)',
			'1' => 'By Resource Owner Credentials',
			'2' => 'By Client Credentials',
		];

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the setting',
				'maxLength' => 50,
			],
			'service' => [
				'property' => 'service',
				'type' => 'enum',
				'label' => 'Service',
				'values' => $services,
				'description' => 'The service used for authenticating users',
				'default' => 'oauth',
				'onchange' => 'return AspenDiscovery.Admin.getSSOFields();',
			],
			'loginOptions' => [
				'property' => 'loginOptions',
				'type' => 'enum',
				'label' => 'Available Options at Login',
				'values' => $login_options,
				'description' => 'The login options available to users when logging in',
				'default' => '0',
				'hideInLists' => true,
			],
			'loginHelpText' => [
				'property' => 'loginHelpText',
				'type' => 'textarea',
				'label' => 'Login Help Text',
				'description' => 'Additional information provided to users when logging in',
				'hideInLists' => true,
			],

			'oAuthGateway' => [
				'property' => 'oAuthGateway',
				'type' => 'enum',
				'label' => 'Gateway',
				'values' => $oauth_gateways,
				'description' => 'The gateway provider used for authenticating users',
				'default' => 'google',
				'hideInLists' => true,
				'onchange' => 'return AspenDiscovery.Admin.toggleOAuthGatewayFields();',
			],
			'clientId' => [
				'property' => 'clientId',
				'type' => 'text',
				'label' => 'Client ID',
				'description' => 'Client ID used for accessing the gateway provider',
				'hideInLists' => true,
			],
			'clientSecret' => [
				'property' => 'clientSecret',
				'type' => 'storedPassword',
				'label' => 'Client Secret',
				'description' => 'Client secret used for accessing the gateway provider',
				'hideInLists' => true,
			],

			'oAuthGatewayLabel' => [
				'property' => 'oAuthGatewayLabel',
				'type' => 'text',
				'label' => 'Custom Gateway Label',
				'description' => 'The public-facing name for the custom gateway',
				'hideInLists' => true,
			],
			'oAuthAuthorizeUrl' => [
				'property' => 'oAuthAuthorizeUrl',
				'type' => 'url',
				'label' => 'Custom Gateway Authorization Url',
				'description' => 'The API url used as the main entry point for requesting authorization',
				'hideInLists' => true,
			],
			'oAuthAccessTokenUrl' => [
				'property' => 'oAuthAccessTokenUrl',
				'type' => 'url',
				'label' => 'Custom Gateway Access Token Url',
				'description' => 'The API url used to connect and exchange the authorization code for an access token.',
				'hideInLists' => true,
			],
			'oAuthResourceOwnerUrl' => [
				'property' => 'oAuthResourceOwnerUrl',
				'type' => 'url',
				'label' => 'Custom Gateway Resource Owner Url',
				'description' => 'The API url used to access the user details',
				'hideInLists' => true,
			],
			'oAuthLogoutUrl' => [
				'property' => 'oAuthLogoutUrl',
				'type' => 'url',
				'label' => 'Custom Gateway Logout Url',
				'description' => 'The API url used to invalidate a session and force a user to logout',
				'hideInLists' => true,
			],
			'oAuthScope' => [
				'property' => 'oAuthScope',
				'type' => 'text',
				'label' => 'Custom Gateway Scopes',
				'description' => 'Granular permissions the API client needs to access data',
				'hideInLists' => true,
			],
			'oAuthGrantType' => [
				'property' => 'oAuthGrantType',
				'type' => 'enum',
				'label' => 'Authentication Grant Type',
				'values' => $authentication_grant_type,
				'description' => 'The grant type used when obtaining an access token.',
				'default' => 0,
				'hideInLists' => true,
				'onchange' => 'return AspenDiscovery.Admin.toggleOAuthPrivateKeysField();',
			],
			'oAuthPrivateKeys' => [
				'property' => 'oAuthPrivateKeys',
				'type' => 'file',
				'label' => 'Private Keys PEM File',
				'description' => 'A .PEM file that contains private keys to access a client secret.',
				'hideInLists' => true,
			],
			'oAuthGatewayIcon' => [
				'property' => 'oAuthGatewayIcon',
				'type' => 'image',
				'label' => 'Custom Gateway Icon',
				'description' => 'An icon representing the custom gateway',
				'hideInLists' => true,
				'thumbWidth' => 32,
			],
			'oAuthButtonBackgroundColor' => [
				'property' => 'oAuthButtonBackgroundColor',
				'type' => 'text',
				'label' => 'Custom Gateway Background Color',
				'description' => 'Custom Gateway Button Background Color',
				'hideInLists' => true,
			],
			'oAuthButtonTextColor' => [
				'property' => 'oAuthButtonTextColor',
				'type' => 'text',
				'label' => 'Custom Gateway Text Color',
				'description' => 'Custom Gateway Button Foreground Color',
				'hideInLists' => true,
			],

			'ssoName' => [
				'property' => 'ssoName',
				'type' => 'text',
				'label' => 'Name of service',
				'description' => 'The name to be displayed when referring to the authentication service',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoXmlUrl' => [
				'property' => 'ssoXmlUrl',
				'type' => 'text',
				'label' => 'URL of service metadata XML',
				'description' => 'The URL at which the metadata XML document for this identity provider can be obtained',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoMetadataFilename' => [
				'path' => '/data/aspen-discovery/sso_metadata',
				'property' => 'ssoMetadataFilename',
				'type' => 'file',
				'label' => 'XML metadata file',
				'description' => 'The XML metadata file if no URL is available',
				'hideInLists' => true,
				'readOnly' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoEntityId' => [
				'property' => 'ssoEntityId',
				'type' => 'text',
				'label' => 'Entity ID of SSO provider',
				'description' => 'The entity ID of the SSO IdP. This can be found in the IdP\'s metadata',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoUniqueAttribute' => [
				'property' => 'ssoUniqueAttribute',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that uniquely identifies a user',
				'description' => 'This should be unique to each user',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoIdAttr' => [
				'property' => 'ssoIdAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user ID',
				'description' => 'This should be unique to each user',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoUsernameAttr' => [
				'property' => 'ssoUsernameAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s username',
				'description' => 'The user\'s username',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoFirstnameAttr' => [
				'property' => 'ssoFirstnameAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s first name',
				'description' => 'The user\'s first name',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoLastnameAttr' => [
				'property' => 'ssoLastnameAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s last name',
				'description' => 'The user\'s last name',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoEmailAttr' => [
				'property' => 'ssoEmailAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s email address',
				'description' => 'The user\'s email address',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoDisplayNameAttr' => [
				'property' => 'ssoDisplayNameAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s display name',
				'description' => 'The user\'s display name, if one is not supplied, a name for display will be assembled from first and last names',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoPhoneAttr' => [
				'property' => 'ssoPhoneAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s phone number',
				'description' => 'The user\'s phone number',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoAddressAttr' => [
				'property' => 'ssoAddressAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s address',
				'description' => 'The user\'s address',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoCityAttr' => [
				'property' => 'ssoCityAttr',
				'type' => 'text',
				'label' => 'Name of the identity provider attribute that contains the user\'s city',
				'description' => 'The user\'s city',
				'size' => '512',
				'hideInLists' => true,
				'permissions' => ['Library ILS Connection'],
			],
			'ssoPatronTypeSection' => [
				'property' => 'ssoPatronTypeSection',
				'type' => 'section',
				'label' => 'Patron type',
				'hideInLists' => true,
				'permissions' => ['Library ILS Options'],
				'properties' => [
					'ssoPatronTypeAttr' => [
						'property' => 'ssoPatronTypeAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s patron type',
						'description' => 'The user\'s patron type, this should be a value that is recognised by Aspen. If this is not supplied, please provide a fallback value below',
						'size' => '512',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoPatronTypeFallback' => [
						'property' => 'ssoPatronTypeFallback',
						'type' => 'text',
						'label' => 'A fallback value for patron type',
						'description' => 'A value to be used in the event the identity provider does not supply a patron type attribute, this should be a value that is recognised by Aspen.',
						'size' => '512',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
				],
			],
			'ssoLibraryIdSection' => [
				'property' => 'ssoLibraryIdSection',
				'type' => 'section',
				'label' => 'Library ID',
				'hideInLists' => true,
				'permissions' => ['Library ILS Options'],
				'properties' => [
					'ssoLibraryIdAttr' => [
						'property' => 'ssoLibraryIdAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s library ID',
						'description' => 'The user\'s library ID, this should be an ID that is recognised by your LMS. If this is not supplied, please provide a fallback value below',
						'size' => '512',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoLibraryIdFallback' => [
						'property' => 'ssoLibraryIdFallback',
						'type' => 'text',
						'label' => 'A fallback value for library ID',
						'description' => 'A value to be used in the event the identity provider does not supply a library ID attribute, this should be an ID that is recognised by your LMS',
						'size' => '512',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
				],
			],
			'ssoCategoryIdSection' => [
				'property' => 'ssoCategoryIdSection',
				'type' => 'section',
				'label' => 'Patron category ID',
				'hideInLists' => true,
				'permissions' => ['Library ILS Options'],
				'properties' => [
					'ssoCategoryIdAttr' => [
						'property' => 'ssoCategoryIdAttr',
						'type' => 'text',
						'label' => 'Name of the identity provider attribute that contains the user\'s patron category ID',
						'description' => 'The user\'s patron category ID, this should be an ID that is recognised by your LMS. If this is not supplied, please provide a fallback value below',
						'size' => '512',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
					'ssoCategoryIdFallback' => [
						'property' => 'ssoCategoryIdFallback',
						'type' => 'text',
						'label' => 'A fallback value for category ID',
						'description' => 'A value to be used in the event the identity provider does not supply a category ID attribute, this should be an ID that is recognised by your LMS',
						'size' => '512',
						'hideInLists' => true,
						'permissions' => ['Library ILS Connection'],
					],
				],
			],

			'dataMapping' => [
				'property' => 'dataMapping',
				'type' => 'oneToMany',
				'label' => 'User Data Mapping',
				'description' => 'Define how user data matches up with data in Aspen',
				'keyThis' => 'id',
				'keyOther' => 'id',
				'subObjectType' => 'SSOMapping',
				'structure' => $fieldMapping,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'hideInLists' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this setting',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->ssoSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "dataMapping") {
			return $this->getFieldMappings();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "dataMapping") {
			$this->_dataMapping = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function getFieldMappings() {
		if (!isset($this->_dataMapping) && $this->id) {
			$this->_dataMapping = [];
			$dataMapping = new SSOMapping();
			$dataMapping->ssoSettingId = $this->id;
			if ($dataMapping->find()) {
				while ($dataMapping->fetch()) {
					$this->_dataMapping[$dataMapping->id] = clone $dataMapping;
				}
			}
		}
		return $this->_dataMapping;
	}

	public function update() {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveFieldMappings();
		}
		return true;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->ssoSettingId != $this->id) {
						$library->ssoSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->ssoSettingId == $this->id) {
						$library->ssoSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveFieldMappings() {
		if (isset($this->_dataMapping) && is_array($this->_dataMapping)) {
			$this->saveOneToManyOptions($this->_dataMapping, 'ssoSettingId');
			unset($this->_dataMapping);
		}
	}

	public function insert() {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveFieldMappings();
		}
		return $ret;
	}

	public function getNumericColumnNames(): array {
		return ['staffOnly'];
	}

	public function genericOAuthProvider() {
		global $configArray;
		$redirectUri = $configArray['Site']['url'] . '/Authentication/OAuth';
		return [
			'urlAuthorize' => $this->oAuthAuthorizeUrl,
			'urlAccessToken' => $this->oAuthAccessTokenUrl,
			'clientId' => $this->clientId,
			'clientSecret' => $this->clientSecret ?? '',
			'redirectUri' => $redirectUri,
			'urlResourceOwnerDetails' => $this->oAuthResourceOwnerUrl,
			'scopes' => $this->oAuthScope,
		];
	}

	public function getAuthorizationUrl() {
		if ($this->oAuthGateway == "google") {
			return "https://accounts.google.com/o/oauth2/v2/auth";
		}

		return $this->oAuthAuthorizeUrl;
	}

	public function getAccessTokenUrl() {
		if ($this->oAuthGateway == "google") {
			return "https://oauth2.googleapis.com/token";
		}

		return $this->oAuthAccessTokenUrl;
	}

	public function getResourceOwnerDetailsUrl() {
		if ($this->oAuthGateway == "google") {
			return "https://openidconnect.googleapis.com/v1/userinfo";
		}

		return $this->oAuthResourceOwnerUrl;
	}

	public function getLogoutUrl() {
		if ($this->oAuthGateway == 'google') {
			return 'https://oauth2.googleapis.com/revoke?token=';
		}

		return $this->oAuthLogoutUrl;
	}

	public function getScope() {
		if ($this->oAuthGateway == "google") {
			return "openid email profile";
		}

		return $this->oAuthScope;
	}

	public function getRedirectUrl() {
		global $configArray;
		$baseUrl = $configArray['Site']['url'];
		if ($this->service == "oauth") {
			return $baseUrl . '/Authentication/OAuth';
		}

		return false;
	}

	public function getMatchpoints() {
		$matchpoints = [
			'email' => 'email',
			'userId' => 'sub',
			'firstName' => 'given_name',
			'lastName' => 'family_name',
		];

		$mappings = new SSOMapping();
		$mappings->ssoSettingId = $this->id;
		$mappings->find();
		while ($mappings->fetch()) {
			if ($mappings->aspenField == "email") {
				$matchpoints['email'] = $mappings->responseField;
			} elseif ($mappings->aspenField == "user_id") {
				$matchpoints['userId'] = $mappings->responseField;
			} elseif ($mappings->aspenField == "first_name") {
				$matchpoints['firstName'] = $mappings->responseField;
			} elseif ($mappings->aspenField == "last_name") {
				$matchpoints['lastName'] = $mappings->responseField;
			}
		}

		return $matchpoints;
	}

	public function getBasicAuthToken() {
		return base64_encode($this->clientId . ":" . $this->clientSecret);
	}

	public function getAuthenticationGrantType() {
		if ($this->oAuthGateway == 'google') {
			return 0;
		}
		return $this->oAuthGrantType;
	}

}