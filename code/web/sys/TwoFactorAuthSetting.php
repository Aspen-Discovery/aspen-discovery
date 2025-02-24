<?php /** @noinspection PhpMissingFieldTypeInspection */

class TwoFactorAuthSetting extends DataObject {
	public $__table = 'two_factor_auth_settings';
	public $id;
	public $accountProfileId;
	public $name;
	public $isEnabled;
	public $deniedMessage;

	private $_libraries;
	private $_ptypes;

	static function getObjectStructure($context = ''): array {
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
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
				'description' => 'The unique id',
			],
			'accountProfileId' => [
				'property' => 'accountProfileId',
				'type' => 'enum',
				'values' => $accountProfileOptions,
				'label' => 'Account Profile Id',
				'description' => 'Account Profile to apply to this interface',
				'permissions' => ['Administer Account Profiles'],
				'readOnly' => $context != 'addNew'
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings',
				'maxLength' => 50,
				'required' => true
			],
			'isEnabled' => [
				'property' => 'isEnabled',
				'type' => 'enum',
				'label' => 'Is Enabled',
				'values' => $requiredList,
			],
			'deniedMessage' => [
				'property' => 'deniedMessage',
				'type' => 'textarea',
				'label' => 'Denied access message',
				'note' => 'Instructions for accessing their account if the user is unable to authenticate',
				'hideInLists' => true,
			]
		];
		if ($context != 'addNew') {
			$structure['libraries'] = [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
			];
			$structure['ptypes'] = [
				'property' => 'ptypes',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Patron Types',
				'values' => $ptypeList,
				'description' => 'Define patron types that use these settings',
			];
		}

		if (!UserAccount::userHasPermission('Administer Two-Factor Authentication')) {
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function updateStructureForEditingObject($structure) : array {
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

	public function update($context = '') : bool|int {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->savePatronTypes();
		}
		return $ret;
	}

	public function insert($context = '') : bool|int {
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