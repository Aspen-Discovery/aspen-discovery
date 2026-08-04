<?php /** @noinspection PhpMissingFieldTypeInspection */

class TwoFactorAuthSetting extends DataObject {
	public $__table = 'two_factor_auth_settings';
	public $id;
	public $accountProfileId;
	public $name;
	public $isEnabled;
	public $deniedMessage;
	public $allowEmail;
	public $allowTotp;
	public $issuerTOTP;

	private $_libraries;
	private $_ptypes;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		global $library;
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->whereAdd("name != 'admin_sso'");
		$accountProfile->orderBy('name');
		$accountProfileOptions = $accountProfile->fetchAll('id', 'name');

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$ptypeList = PType::getPatronTypeList();

		$requiredList = [
			'notAvailable' => 'No',
			'optional' => 'Yes, but optional',
			'mandatory' => 'Yes, and mandatory',
		];

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique Id for this setting.',
			],
			'accountProfileId' => [
				'property' => 'accountProfileId',
				'type' => 'enum',
				'values' => $accountProfileOptions,
				'label' => 'Account Profile Name',
				'description' => 'Select the Account Profile for this setting.',
				'note' => 'If the "admin" Account Profile is selected, this setting cannot be scoped to Libraries and Patron Types.',
				'permissions' => ['Administer Account Profiles'],
				'readOnly' => $context != 'addNew'
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the setting.',
				'maxLength' => 50,
				'required' => true
			],
			'isEnabled' => [
				'property' => 'isEnabled',
				'type' => 'enum',
				'label' => 'Is Enabled',
				'values' => $requiredList,
			],
			'allowEmail' => [
				'property' => 'allowEmail',
				'type' => 'checkbox',
				'label' => 'Allow Email',
				'description' => 'Allow users to select email as a method for 2FA. If allowed, users will have the option to receive a code via email when they log in.'
			],
			'allowTotp' => [
				'property' => 'allowTotp',
				'type' => 'checkbox',
				'label' => 'Allow TOTP apps (Google Authenticator, Authy, etc.)',
				'description' => 'Allow users to select TOTP apps as a method for 2FA. If allowed, users will have the option to set up an authenticator app to receive codes when they log in.',
				'onchange' => 'return AspenDiscovery.Admin.toggle2FATOTPOptions();',
			],
			'issuerTOTP' => [
				'property' => 'issuerTOTP',
				'type' => 'text',
				'label' => 'TOTP Issuer Name',
				'description' => 'The name of the service displayed in the authenticator app.',
				'default' => $library->displayName . ' Catalog',
				'required' => true,
			],
			'deniedMessage' => [
				'property' => 'deniedMessage',
				'type' => 'textarea',
				'label' => 'Denied Access Message',
				'description' => 'Instructions on account access when a user cannot authenticate.',
				'hideInLists' => true,
			]
		];
		if ($context != 'addNew') {
			$structure['libraries'] = [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this setting.',
				'values' => $libraryList,
			];
			$structure['ptypes'] = [
				'property' => 'ptypes',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Patron Types',
				'values' => $ptypeList,
				'description' => 'Define patron types that use this setting.',
			];
		}

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function updateStructureForEditingObject($structure) : array {
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$adminProfile = new AccountProfile();
		$adminProfile->name = 'admin';
		if ($adminProfile->find(true) && $this->accountProfileId === $adminProfile->id) {
			unset($structure['libraries']);
			unset($structure['ptypes']);
			return $structure;
		}

		if (isset($structure['libraries'])) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'), $this->accountProfileId);
			$structure['libraries']['values'] = $libraryList;
		}
		if (isset($structure['ptypes'])) {
			$ptypeList = PType::getPatronTypeList(false, false, $this->accountProfileId);
			$structure['ptypes']['values'] = $ptypeList;
		}

		return $structure;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->twoFactorAuthSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == 'ptypes') {
			if (!isset($this->_ptypes) && $this->id) {
				$this->_ptypes = [];
				$obj = new PType();
				$obj->twoFactorAuthSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_ptypes[$obj->id] = $obj->id;
				}
			}
			return $this->_ptypes;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == 'ptypes') {
			$this->_ptypes = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->savePatronTypes();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->savePatronTypes();
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'), $this->accountProfileId);
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->twoFactorAuthSettingId != $this->id) {
						$library->twoFactorAuthSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->twoFactorAuthSettingId == $this->id) {
						$library->twoFactorAuthSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function savePatronTypes() : void {
		if (isset ($this->_ptypes) && is_array($this->_ptypes)) {
			$ptypeList = PType::getPatronTypeList(false, false, $this->accountProfileId);
			foreach ($ptypeList as $id => $ptype) {
				$patronType = new PType();
				$patronType->id = $id;
				$patronType->find(true);
				if (in_array($patronType->id, $this->_ptypes)) {
					//We want to apply the scope to this Patron Type
					if ($patronType->twoFactorAuthSettingId != $this->id) {
						$patronType->twoFactorAuthSettingId = $this->id;
						$patronType->update();
					}
				} else {
					//It should not be applied to this Patron Type. Only change if it was applied to the Patron Type
					if ($patronType->twoFactorAuthSettingId == $this->id) {
						$patronType->twoFactorAuthSettingId = -1;
						$patronType->update();
					}
				}
			}
			unset($this->_ptypes);
		}
	}

	public static function getTwoFactorAuthSettingsList(bool $addEmpty, int $accountProfileId = -1) : array {
		$setting = new TwoFactorAuthSetting();
		$setting->orderBy('name');
		if ($accountProfileId != -1 && !empty($accountProfileId)) {
			$setting->accountProfileId = $accountProfileId;
		}
		$settingsList = [];
		if ($addEmpty) {
			$settingsList[-1] = "";
		}
		$settingsList += $setting->fetchAll('id', 'name');

		return $settingsList;
	}
}