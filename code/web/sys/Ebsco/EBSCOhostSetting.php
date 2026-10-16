<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSearchSetting.php';

/**
 * Class EBSCOhostSetting - Store settings for EBSCOhost
 */
class EBSCOhostSetting extends DataObject {
	public $__table = 'ebscohost_settings';
	public $id;
	public $name;
	public $profileId;
	public $profilePwd;

	private $_searchSettings;

	function getEncryptedFieldNames(): array {
		return ['profilePwd'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$ebscoHostSearchSettingStructure = EBSCOhostSearchSetting::getObjectStructure($context);

		$structure = [
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
				'maxLength' => 50,
				'description' => 'A name for these settings',
				'required' => true,
			],
			'profileId' => [
				'property' => 'profileId',
				'type' => 'text',
				'label' => 'Profile Id',
				'description' => 'The profile used for authentication. Required if using profile authentication.',
				'hideInLists' => true,
			],
			'profilePwd' => [
				'property' => 'profilePwd',
				'type' => 'storedPassword',
				'label' => 'Profile Password',
				'description' => 'The password used for profile authentication. Required if using profile authentication.',
				'hideInLists' => true,
			],
			'searchSettings' => [
				'property' => 'searchSettings',
				'type' => 'oneToMany',
				'label' => 'Search Settings',
				'description' => 'Settings for Searching',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'EBSCOhostSearchSetting',
				'structure' => $ebscoHostSearchSettingStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "searchSettings") {
			return $this->getSearchSettings();
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * @return ?EBSCOhostSearchSetting[]
	 */
	public function getSearchSettings(): ?array {
		if (!isset($this->_searchSettings) && $this->id) {
			$this->_searchSettings = [];
			$obj = new EBSCOhostSearchSetting();
			$obj->settingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_searchSettings[$obj->id] = clone($obj);
			}
		}
		return $this->_searchSettings;
	}

	public function __set($name, $value) {
		if ($name == "searchSettings") {
			$this->_searchSettings = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSearchSettings();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_searchSettings)) {
				$searchSettings = new EBSCOhostSearchSetting();
				$searchSettings->settingId = $this->id;
				$searchSettings->name = 'default';


				$this->_searchSettings[] = $searchSettings;
			}
			$this->saveSearchSettings();
		}
		return $ret;
	}

	public function saveSearchSettings(): void {
		if (isset ($this->_searchSettings) && is_array($this->_searchSettings)) {
			$this->saveOneToManyOptions($this->_searchSettings, 'settingId');
			unset($this->_searchSettings);
		}
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret) {
			$this->clearSearchSettings();
		}
		return $ret;
	}

	public function clearSearchSettings() : void {
		$searchSettings = $this->getSearchSettings();
		foreach ($searchSettings as $searchSetting) {
			$searchSetting->delete();
		}
		$this->clearOneToManyOptions('EBSCOhostSearchSetting', 'settingsId');
		$this->_searchSettings = [];
	}
}