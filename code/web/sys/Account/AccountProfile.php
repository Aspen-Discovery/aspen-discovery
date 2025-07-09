<?php /** @noinspection PhpMissingFieldTypeInspection */

/**
 * Contains information about how to connect to the ILS or other back end system including
 * local administration. The fields shown will depend on the ILS that is active, and are
 * enabled and disabled with JavaScript.
 */
class AccountProfile extends DataObject {
	public $__table = 'account_profiles';    // table name

	public $id;
	public $name;
	public $ils;
	public $driver;
	public $loginConfiguration;
	public $iiiLoginConfiguration;
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
	public $overrideCode;
	public /** @noinspection PhpUnused */
		$apiVersion;
	public $workstationId;
	public $weight;
	public $ssoSettingId;
    public $carlXViewVersion;
	public $enableFetchingIlsMessages;

	/** @var bool|IndexingProfile|null */
	private $_indexingProfile = false;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$ssoIsEnabled = false;
		$ssoSettingsOptions = [];
		global $enabledModules;
		if (array_key_exists('Single sign-on', $enabledModules)) {
			$ssoIsEnabled = true;
			$authenticationMethodOptions = [
				'ils' => 'ILS',
				'db' => 'Database',
				'sso' => 'Single Sign-on (SSO)'
			];
			require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
			$ssoSettings = new SSOSetting();
			$ssoSettings->orderBy('name');
			$ssoSettingsOptions = [];
			$ssoSettingsOptions[-1] = '';
			$ssoSettings->find();
			while ($ssoSettings->fetch()) {
				$ssoSettingsOptions[$ssoSettings->id] = $ssoSettings->name . ' (' . $ssoSettings->service . ')';
			}
		} else {
			$authenticationMethodOptions = [
				'ils' => 'ILS',
				'db' => 'Database',
			];
		}

		if ($context == 'addNew') {
			$accountProfile = new AccountProfile();
			$accountProfile->selectAdd();
			$accountProfile->selectAdd("MAX(weight) as maxWeight");
			if ($accountProfile->find(true)) {
				/** @noinspection PhpUndefinedFieldInspection */
				$defaultWeight = $accountProfile->maxWeight + 1;
			}else{
				$defaultWeight = 0;
			}
		}else{
			$defaultWeight = 0;
		}

		$structure = [
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
				'default' => $defaultWeight,
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
					'carlx' => 'Carl.X',
					'evergreen' => 'Evergreen',
					'evolve' => 'Evolve',
					//'folio' => 'Folio',
					//'horizon' => 'Horizon',
					'koha' => 'Koha',
					//'millennium' => 'Millennium',
					'polaris' => 'Polaris',
					'sierra' => 'Sierra',
					'symphony' => 'Symphony',
				],
				'description' => 'The ils of the account profile',
				'required' => true,
				'default' => 'na',
				'onchange' => 'AspenDiscovery.Admin.setAccountProfileDefaultsByIls();AspenDiscovery.Admin.toggleAccountProfileIlsFields();return false;',
			],
			'driver' => [
				'property' => 'driver',
				'type' => 'text',
				'label' => 'Driver',
				'maxLength' => 50,
				'description' => 'The name of the driver to use for authentication',
				'required' => false,
				'relatedIls' => ['carlx','evergreen','evolve','koha','polaris','sierra','symphony']
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
							'name_barcode' => 'Name and Barcode (Sierra/Millennium Only)',
							'barcode_lastname' => 'Barcode and Last Name (CARL.X Only)',
						],
						'description' => 'How to configure the prompts for this authentication profile',
						'required' => true,
					],
					'iiiLoginConfiguration' => [
						'property' => 'iiiLoginConfiguration',
						'type' => 'enum',
						'label' => 'Sierra/Millennium Login Configuration',
						'values' => [
							'' => 'N/A',
							'barcode_pin' => 'Barcode and Pin',
							'name_barcode' => 'Name and Barcode',
							'name_barcode_pin' => 'Name and Barcode and Pin',
						],
						'description' => 'How to login to Sierra/Millennium WebPAC (for screen scraping)',
						'required' => false,
						'relatedIls' => ['sierra']
					],
					'authenticationMethod' => [
						'property' => 'authenticationMethod',
						'type' => 'enum',
						'label' => 'Authentication Method',
						'values' => $authenticationMethodOptions,
						'description' => 'The method of authentication to use',
						'required' => true,
						'onchange' => 'return AspenDiscovery.Admin.toggleSSOSettingsInAccountProfile();',
						'default' => 'db'
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
				'relatedIls' => ['carlx','evergreen','evolve','koha','polaris','sierra','symphony'],
				'properties' => [
					'vendorOpacUrl' => [
						'property' => 'vendorOpacUrl',
						'type' => 'text',
						'label' => 'Vendor OPAC Url',
						'maxLength' => 100,
						'description' => 'A link to the url for the vendor opac',
						'required' => false,
						'validationPattern' => "^https?:\/\/[-a-zA-Z0-9_.]*(:[0-9]{1,4})?([-\/a-zA-Z0-9_?&=.]*)$",
						'validationMessage' => 'Please enter a valid URL. The URL may include port number.',
						'relatedIls' => ['koha','sierra']
					],
					'patronApiUrl' => [
						'property' => 'patronApiUrl',
						'type' => 'text',
						'label' => 'Webservice/Patron API Url',
						'maxLength' => 100,
						'description' => 'A link to the patron api for the vendor opac if any',
						'required' => false,
						'validationPattern' => "^https?:\/\/[-a-zA-Z0-9_.]*(:[0-9]{1,4})?([-\/a-zA-Z0-9_?&=.]*)$",
						'validationMessage' => 'Please enter a valid URL. The URL may include port number.',
						'relatedIls' => ['carlx','evergreen','evolve','koha','polaris','sierra','symphony'],
					],
					'accountMessagesSection' => [
						'property' => 'accountMessagesSection',
						'type' => 'section',
						'label' => 'ILS Messages Information',
						'hideInLists' => true,
						'relatedIls' => ['koha', 'sierra'],
						'properties' => [
							'enableFetchingIlsMessages' => [
								'property' => 'enableFetchingIlsMessages',
								'type' => 'checkbox',
								'label' => 'Enable Fetching Messages from the ILS',
								'description' => 'Whether or not messages from the ILS will be fetched for use in notifications Discovery and LiDA',
								'default' => false,
								'relatedIls' => ['koha', 'sierra']
							],
						],
					],
					'databaseSection' => [
						'property' => 'databaseSection',
						'type' => 'section',
						'label' => 'Database Information',
						'hideInLists' => true,
						'relatedIls' => ['carlx','koha','sierra'],
						'properties' => [
							'databaseHost' => [
								'property' => 'databaseHost',
								'type' => 'text',
								'label' => 'Database Host',
								'maxLength' => 100,
								'description' => 'Optional URL where the database is located',
								'required' => false,
								'relatedIls' => ['carlx','koha','sierra'],
							],
							'databasePort' => [
								'property' => 'databasePort',
								'type' => 'text',
								'label' => 'Database Port',
								'maxLength' => 5,
								'description' => 'The port to use when connecting to the database',
								'required' => false,
								'relatedIls' => ['carlx','koha','sierra'],
							],
							'databaseName' => [
								'property' => 'databaseName',
								'type' => 'text',
								'label' => 'Database Schema Name',
								'maxLength' => 75,
								'description' => 'Name of the schema to connect to within the database',
								'required' => false,
								'relatedIls' => ['carlx','koha','sierra'],
							],
							'databaseUser' => [
								'property' => 'databaseUser',
								'type' => 'text',
								'label' => 'Database User',
								'maxLength' => 50,
								'description' => 'Username to use when connecting',
								'required' => false,
								'relatedIls' => ['carlx','koha','sierra'],
							],
							'databasePassword' => [
								'property' => 'databasePassword',
								'type' => 'storedPassword',
								'label' => 'Database Password',
								'maxLength' => 50,
								'description' => 'Password to use when connecting',
								'required' => false,
								'relatedIls' => ['carlx','koha','sierra'],
							],
							'databaseTimezone' => [
								'property' => 'databaseTimezone',
								'type' => 'text',
								'label' => 'Database Timezone',
								'maxLength' => 50,
								'description' => 'Timezone to use when connecting',
								'required' => false,
								'relatedIls' => ['koha'],
							],
							'carlXViewVersion' => [
								'property' => 'carlXViewVersion',
								'type' => 'enum',
								'values' => [
									'' => 'N/A',
									'v' => 'v',
									'v2' => 'v2',
								],
								'default' => '',
								'label' => 'Carl.X Database View Version',
								'note' => 'Only used for Carl.X',
								'description' => 'Database View Version of Carl.X to use when connecting',
								'required' => false,
								'relatedIls' => ['carlx'],
							],
						],
					],
					'sip2Section' => [
						'property' => 'sip2Section',
						'type' => 'section',
						'label' => 'SIP 2 Information (optional)',
						'hideInLists' => true,
						'relatedIls' => ['carlx','evergreen','evolve','polaris'],
						'properties' => [
							'sipHost' => [
								'property' => 'sipHost',
								'type' => 'text',
								'label' => 'SIP 2 Host',
								'maxLength' => 100,
								'description' => 'The host for SIP 2 connections',
								'required' => false,
								'relatedIls' => ['carlx','evergreen','evolve','polaris'],
							],
							'sipPort' => [
								'property' => 'sipPort',
								'type' => 'text',
								'label' => 'SIP 2 Port',
								'maxLength' => 50,
								'description' => 'Port to use when connecting',
								'required' => false,
								'relatedIls' => ['carlx','evergreen','evolve','polaris'],
							],
							'sipUser' => [
								'property' => 'sipUser',
								'type' => 'text',
								'label' => 'SIP 2 User',
								'maxLength' => 50,
								'description' => 'Username to use when connecting',
								'required' => false,
								'relatedIls' => ['carlx','evergreen','evolve','polaris'],
							],
							'sipPassword' => [
								'property' => 'sipPassword',
								'type' => 'storedPassword',
								'label' => 'SIP 2 Password',
								'maxLength' => 50,
								'description' => 'Password to use when connecting',
								'required' => false,
								'relatedIls' => ['carlx','evergreen','evolve','polaris'],
							],
						],
					],
					'oAuthSection' => [
						'property' => 'oAuthSection',
						'type' => 'section',
						'label' => 'API/OAuth2 Information',
						'hideInLists' => true,
						'relatedIls' => ['carlx','evolve','koha','polaris','sierra','symphony'],
						'properties' => [
							'oAuthClientId' => [
								'property' => 'oAuthClientId',
								'type' => 'text',
								'label' => 'API/OAuth2 ClientId',
								'maxLength' => 36,
								'description' => 'The Client ID to use when making a connection to APIs',
								'required' => false,
								'relatedIls' => ['carlx','koha','polaris','sierra','symphony'],
							],
							'oAuthClientSecret' => [
								'property' => 'oAuthClientSecret',
								'type' => 'storedPassword',
								'label' => 'API/OAuth2 Secret',
								'maxLength' => 50,
								'description' => 'The Client Secret to use when making a connection to APIs',
								'required' => false,
								'relatedIls' => ['carlx','evolve','koha','polaris','sierra'],
							],
							'apiVersion' => [
								'property' => 'apiVersion',
								'type' => 'text',
								'label' => 'API Version',
								'maxLength' => 10,
								'description' => 'Optional description for the version of the API. Required for Sierra.',
								'relatedIls' => ['polaris','sierra'],
							],
							'workstationId' => [
								'property' => 'workstationId',
								'type' => 'text',
								'label' => 'Workstation Id (Polaris)',
								'maxLength' => 10,
								'description' => 'Optional workstation ID for transactions, overrides workstation ID in account profile.',
								'relatedIls' => ['polaris'],
							],
						],
					],
					'staffUser' => [
						'property' => 'staffUser',
						'type' => 'section',
						'label' => 'Staff Account Information',
						'hideInLists' => true,
						'relatedIls' => ['carlx','evergreen','polaris','symphony'],
						'properties' => [
							'domain' => [
								'property' => 'domain',
								'type' => 'text',
								'label' => 'Staff Domain',
								'maxLength' => 100,
								'description' => 'The domain to use when performing staff actions',
								'required' => false,
								'relatedIls' => ['polaris'],
							],
							'staffUsername' => [
								'property' => 'staffUsername',
								'type' => 'text',
								'label' => 'Staff Username',
								'maxLength' => 100,
								'description' => 'The Staff Username to use when performing staff actions',
								'required' => false,
								'relatedIls' => ['carlx','evergreen','polaris','symphony'],
							],
							'staffPassword' => [
								'property' => 'staffPassword',
								'type' => 'storedPassword',
								'label' => 'Staff Password',
								'maxLength' => 50,
								'description' => 'The Staff Password to use when performing staff actions',
								'required' => false,
								'relatedIls' => ['evergreen','polaris','symphony'],
							],
						],
					],
					'overrideCode' => [
						'property' => 'overrideCode',
						'type' => 'storedPassword',
						'label' => 'Override Code',
						'maxLength' => 50,
						'description' => 'An Override Code to apply for some actions (i.e. PIN Resets)',
						'required' => false,
						'relatedIls' => ['symphony'],
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
				],
				'relatedIls' => ['carlx','evergreen','evolve','koha','polaris','sierra','symphony'],
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

		if (!array_key_exists('Single sign-on', $enabledModules)) {
			unset($structure['authConfigurationSection']['properties']['ssoSettingId']);
		}
		if (!array_key_exists('Aspen LiDA', $enabledModules)) {
			unset($structure['ilsConnectionSection']['properties']['accountMessagesSection']);
		}

		return $structure;
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
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
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

	function update($context = '') : bool|int {
		global $memCache;
		global $instanceName;
		$memCache->delete('account_profiles_' . $instanceName);
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	function delete($useWhere = false) : int {
		/** @var Memcache $memCache */ global $memCache;
		global $instanceName;
		$memCache->delete('account_profiles_' . $instanceName);
		return parent::delete($useWhere);
	}

	public function saveLibraries() : void {
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
	function getIndexingProfile() : ?IndexingProfile {
		if ($this->_indexingProfile === false) {
			global $indexingProfiles;
			if (array_key_exists($this->name, $indexingProfiles)) {
				$this->_indexingProfile = $indexingProfiles[$this->name];
			} else {
				$this->_indexingProfile = null;
			}
		}
		return $this->_indexingProfile;
	}

	/**
	 * Checks if there are any Account Profiles other than 'admin' or 'admin_sso'.
	 *
	 * @return bool True if at least one ILS profile exists, false otherwise.
	 */
	public static function hasValidILSProfiles(): bool {
		$accountProfile = new AccountProfile();
		$accountProfile->find();
		while ($accountProfile->fetch()) {
			if ($accountProfile->name != "admin" && $accountProfile->name != "admin_sso") {
				return true; // Found one, no need to check further.
			}
		}
		return false;
	}


	/**
	 * Modify the structure of the object based on the object currently being edited.
	 * This can be used to change enums or other values based on the object being edited, so we know relationships
	 *
	 * @param $structure
	 * @return array
	 */
	public function updateStructureForEditingObject($structure) : array {
		if ($this->name == 'admin') {
			$structure['name']['readOnly'] = true;
			unset($structure['ils']);
			unset($structure['driver']);
			unset($structure['authConfigurationSection']);
			unset($structure['ilsConnectionSection']);
			unset($structure['indexingConfigurationSection']);
			unset($structure['libraries']);
		}

		return $structure;
	}

	public function canActiveUserDelete() : bool {
		//Do not allow the admin account profile to be deleted
		return $this->name != 'admin';
	}
}
