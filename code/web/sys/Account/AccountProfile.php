<?php

class AccountProfile extends DataObject {
	public $__table = 'account_profiles';    // table name

	public $id;
	public $name;
	public $ils;
	public $driver;
	public $loginConfiguration;
	public $authenticationMethod;
	public $vendorOpacUrl;
	public $patronApiUrl;
	public $recordSource;
	public $databaseHost;
	public $databasePort;
	public $databaseName;
	public $databaseUser;
	public $databasePassword;
	public /** @noinspection PhpUnused */
		$databaseTimezone;
	public $sipHost;
	public $sipPort;
	public $sipUser;
	public $sipPassword;
	public $oAuthClientId;
	public $oAuthClientSecret;
	public $domain;
	public $staffUsername;
	public $staffPassword;
	public /** @noinspection PhpUnused */
		$apiVersion;
	public $workstationId;
	public $weight;
	public $ssoSettingId;

	/** @var bool|IndexingProfile|null */
	private $_indexingProfile = false;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
		$ssoSettings = new SSOSetting();
		$ssoSettings->orderBy('name');
		$ssoSettingsOptions = [];
		$ssoSettingsOptions[-1] = "";
		$ssoSettings->find();
		while ($ssoSettings->fetch()) {
			$ssoSettingsOptions[$ssoSettings->id] = $ssoSettings->name . ' (' . $ssoSettings->service . ')';
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for this indexing profile',
				'required' => true,
			],
			'ils' => [
				'property' => 'ils',
				'type' => 'enum',
				'label' => 'ILS',
				'values' => [
					'na' => 'None',
					'koha' => 'Koha',
					'carlx' => 'Carl.X',
					'evergreen' => 'Evergreen',
					'evolve' => 'Evolve',
					'folio' => 'Folio',
					'horizon' => 'Horizon',
					'millennium' => 'Millennium',
					'polaris' => 'Polaris',
					'sierra' => 'Sierra',
					'symphony' => 'Symphony',
				],
				'description' => 'The ils of the account profile',
				'required' => true,
				'default' => 'koha',
			],
			'driver' => [
				'property' => 'driver',
				'type' => 'text',
				'label' => 'Driver',
				'maxLength' => 50,
				'description' => 'The name of the driver to use for authentication',
				'required' => false,
			],
			'authConfigurationSection' => [
				'property' => 'authConfigurationSection',
				'type' => 'section',
				'label' => 'Authentication Configuration',
				'renderAsHeading' => true,
				'showBottomBorder' => true,
				'properties' => [
					'loginConfiguration' => [
						'property' => 'loginConfiguration',
						'type' => 'enum',
						'label' => 'Login Configuration',
						'values' => [
							'barcode_pin' => 'Barcode and Pin',
							'name_barcode' => 'Name and Barcode',
						],
						'description' => 'How to configure the prompts for this authentication profile',
						'required' => true,
					],
					'authenticationMethod' => [
						'property' => 'authenticationMethod',
						'type' => 'enum',
						'label' => 'Authentication Method',
						'values' => [
							'ils' => 'ILS',
							'db' => 'Database',
							'sso' => 'Single Sign-on (SSO)'
						],
						'description' => 'The method of authentication to use',
						'required' => true,
						'onchange' => 'return AspenDiscovery.Admin.toggleSSOSettingsInAccountProfile();',
					],
					'ssoSettingId' => [
						'property' => 'ssoSettingId',
						'type' => 'enum',
						'label' => 'Primary Single Sign-on (SSO) Settings',
						'values' => $ssoSettingsOptions,
						'description' => 'The primary single sign-on settings to use for the account profile. Can be overridden at the library level.',
					],
				],
			],
			'ilsConnectionSection' => [
				'property' => 'ilsConnectionSection',
				'type' => 'section',
				'label' => 'ILS Connection Configuration',
				'renderAsHeading' => true,
				'showBottomBorder' => true,
				'properties' => [
					'vendorOpacUrl' => [
						'property' => 'vendorOpacUrl',
						'type' => 'url',
						'label' => 'Vendor OPAC Url',
						'maxLength' => 100,
						'description' => 'A link to the url for the vendor opac',
						'required' => false,
					],
					'patronApiUrl' => [
						'property' => 'patronApiUrl',
						'type' => 'url',
						'label' => 'Webservice/Patron API Url',
						'maxLength' => 100,
						'description' => 'A link to the patron api for the vendor opac if any',
						'required' => false,
					],
					'databaseSection' => [
						'property' => 'databaseSection',
						'type' => 'section',
						'label' => 'Database Information (optional)',
						'hideInLists' => true,
						'properties' => [
							'databaseHost' => [
								'property' => 'databaseHost',
								'type' => 'text',
								'label' => 'Database Host',
								'maxLength' => 100,
								'description' => 'Optional URL where the database is located',
								'required' => false,
							],
							'databasePort' => [
								'property' => 'databasePort',
								'type' => 'text',
								'label' => 'Database Port',
								'maxLength' => 5,
								'description' => 'The port to use when connecting to the database',
								'required' => false,
							],
							'databaseName' => [
								'property' => 'databaseName',
								'type' => 'text',
								'label' => 'Database Schema Name',
								'maxLength' => 75,
								'description' => 'Name of the schema to connect to within the database',
								'required' => false,
							],
							'databaseUser' => [
								'property' => 'databaseUser',
								'type' => 'text',
								'label' => 'Database User',
								'maxLength' => 50,
								'description' => 'Username to use when connecting',
								'required' => false,
							],
							'databasePassword' => [
								'property' => 'databasePassword',
								'type' => 'storedPassword',
								'label' => 'Database Password',
								'maxLength' => 50,
								'description' => 'Password to use when connecting',
								'required' => false,
							],
							'databaseTimezone' => [
								'property' => 'databaseTimezone',
								'type' => 'text',
								'label' => 'Database Timezone',
								'maxLength' => 50,
								'description' => 'Timezone to use when connecting',
								'required' => false,
							],
						],
					],
					'sip2Section' => [
						'property' => 'sip2Section',
						'type' => 'section',
						'label' => 'SIP 2 Information (optional)',
						'hideInLists' => true,
						'properties' => [
							'sipHost' => [
								'property' => 'sipHost',
								'type' => 'text',
								'label' => 'SIP 2 Host',
								'maxLength' => 100,
								'description' => 'The host for SIP 2 connections',
								'required' => false,
							],
							'sipPort' => [
								'property' => 'sipPort',
								'type' => 'text',
								'label' => 'SIP 2 Port',
								'maxLength' => 50,
								'description' => 'Port to use when connecting',
								'required' => false,
							],
							'sipUser' => [
								'property' => 'sipUser',
								'type' => 'text',
								'label' => 'SIP 2 User',
								'maxLength' => 50,
								'description' => 'Username to use when connecting',
								'required' => false,
							],
							'sipPassword' => [
								'property' => 'sipPassword',
								'type' => 'storedPassword',
								'label' => 'SIP 2 Password',
								'maxLength' => 50,
								'description' => 'Password to use when connecting',
								'required' => false,
							],
						],
					],
					'oAuthSection' => [
						'property' => 'oAuthSection',
						'type' => 'section',
						'label' => 'API/OAuth2 Information (optional)',
						'hideInLists' => true,
						'properties' => [
							'oAuthClientId' => [
								'property' => 'oAuthClientId',
								'type' => 'text',
								'label' => 'API/OAuth2 ClientId',
								'maxLength' => 36,
								'description' => 'The Client ID to use when making a connection to APIs',
								'required' => false,
							],
							'oAuthClientSecret' => [
								'property' => 'oAuthClientSecret',
								'type' => 'storedPassword',
								'label' => 'API/OAuth2 Secret',
								'maxLength' => 50,
								'description' => 'The Client Secret to use when making a connection to APIs',
								'required' => false,
							],
							'apiVersion' => [
								'property' => 'apiVersion',
								'type' => 'text',
								'label' => 'API Version',
								'maxLength' => 10,
								'description' => 'Optional description for the version of the API. Required for Sierra.',
							],
							'workstationId' => [
								'property' => 'workstationId',
								'type' => 'text',
								'label' => 'Workstation Id (Polaris)',
								'maxLength' => 10,
								'description' => 'Optional workstation ID for transactions, overrides workstation ID in account profile.',
							],
						],
					],
					'staffUser' => [
						'property' => 'staffUser',
						'type' => 'section',
						'label' => 'Staff Account Information (optional)',
						'hideInLists' => true,
						'properties' => [
							'domain' => [
								'property' => 'domain',
								'type' => 'text',
								'label' => 'Staff Domain',
								'maxLength' => 100,
								'description' => 'The domain to use when performing staff actions',
								'required' => false,
							],
							'staffUsername' => [
								'property' => 'staffUsername',
								'type' => 'text',
								'label' => 'Staff Username',
								'maxLength' => 100,
								'description' => 'The Staff Username to use when performing staff actions',
								'required' => false,
							],
							'staffPassword' => [
								'property' => 'staffPassword',
								'type' => 'storedPassword',
								'label' => 'Staff Password',
								'maxLength' => 50,
								'description' => 'The Staff Password to use when performing staff actions',
								'required' => false,
							],
						],
					],
				],
			],
			'indexingConfigurationSection' => [
				'property' => 'indexingConfigurationSection',
				'type' => 'section',
				'label' => 'Indexing Configuration',
				'renderAsHeading' => true,
				'showBottomBorder' => true,
				'properties' => [
					'recordSource' => [
						'property' => 'recordSource',
						'type' => 'text',
						'label' => 'Record Source',
						'maxLength' => 50,
						'description' => 'The record source of checkouts holds, etc.  Should match the name of an Indexing Profile.',
						'required' => false,
					],
				]
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this profile',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->accountProfileId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	function insert($context = '') {
		global $memCache;
		global $instanceName;
		$memCache->delete('account_profiles_' . $instanceName);
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	/**
	 * @return int|bool
	 */
	function update($context = '') {
		global $memCache;
		global $instanceName;
		$memCache->delete('account_profiles_' . $instanceName);
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	function delete($useWhere = false) {
		/** @var Memcache $memCache */ global $memCache;
		global $instanceName;
		$memCache->delete('account_profiles_' . $instanceName);
		return parent::delete($useWhere);
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the account profile to this library
					if ($library->accountProfileId != $this->id) {
						$library->accountProfileId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this account profile. Only change if it was applied to the scope
					if ($library->accountProfileId == $this->id) {
						$library->accountProfileId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	/**
	 * @return null|IndexingProfile
	 */
	function getIndexingProfile() {
		if ($this->_indexingProfile == false) {
			global $indexingProfiles;
			if (array_key_exists($this->name, $indexingProfiles)) {
				$this->_indexingProfile = $indexingProfiles[$this->name];
			} else {
				$this->_indexingProfile = null;
			}
		}
		return $this->_indexingProfile;
	}
}