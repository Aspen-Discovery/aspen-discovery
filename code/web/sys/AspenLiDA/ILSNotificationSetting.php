<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class ILSNotificationSetting extends DataObject {

	public $__table = 'ils_notification_setting';
	public $id;
	public $name;
	public $accountProfileId;

	private $_messageTypes;
	private $_notificationSettings;

	public function getNumericColumnNames(): array {
		return [
			'id',
		];
	}

	static ?array $objectStructure = null;
	static function getObjectStructure($context = ''): array {
		if (self::$objectStructure == null) {
			$notificationSettings = [];
			$notificationSetting = new NotificationSetting();
			$notificationSetting->find();
			while ($notificationSetting->fetch()) {
				$notificationSettings[$notificationSetting->id] = $notificationSetting->name;
			}

			$accountProfiles = [];
			require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
			$accountProfile = new AccountProfile();
			$accountProfile->whereAdd("name <> 'admin' AND name <> 'admin_sso'");
			$accountProfile->whereAdd("recordSource <> ''");
			$accountProfile->orderBy('name');
			$accountProfile->find();
			$accountProfiles = $accountProfile->fetchAll('id', 'name');
			unset($accountProfile);

			require_once ROOT_DIR . '/sys/AspenLiDA/ILSMessageType.php';
			$messageTypeStructure = ILSMessageType::getObjectStructure($context);

			self::$objectStructure = [
				'id' => [
					'property' => 'id',
					'type' => 'label',
					'label' => 'Id',
					'description' => 'The unique id within the database',
				],
				'name' => [
					'property' => 'name',
					'type' => 'text',
					'label' => 'Name',
					'maxLength' => 50,
					'description' => 'A name for these settings',
					'required' => true,
				],
				'accountProfileId' => [
					'property' => 'accountProfileId',
					'type' => 'enum',
					'label' => 'Account Profile',
					'values' => $accountProfiles,
					'description' => 'Select the Account Profile linked to these notification settings.',
					'required' => true,
				],
				'messageTypes' => [
					'property' => 'messageTypes',
					'type' => 'oneToMany',
					'label' => 'Message Types',
					'description' => 'Message types available for the ILS',
					'keyThis' => 'id',
					'keyOther' => 'ilsNotificationSettingId',
					'subObjectType' => 'ILSMessageType',
					'structure' => $messageTypeStructure,
					'sortable' => false,
					'storeDb' => true,
					'allowEdit' => true,
					'canEdit' => true,
					'canAddNew' => false,
					'canDelete' => false,
				],
				'notificationSettings' => [
					'property' => 'notificationSettings',
					'type' => 'multiSelect',
					'listStyle' => 'checkboxSimple',
					'label' => 'Applies to Aspen LiDA Notification Settings',
					'description' => 'Define Aspen LiDA Notification Settings that use this setting',
					'values' => $notificationSettings,
				]
			];
		}

		if ($context == 'addNew') {
			$structureCopy = self::$objectStructure;
			unset($structureCopy['messageTypes']);
			return $structureCopy;
		}else{
			return self::$objectStructure;
		}
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink($context): string {
		return '/AspenLiDA/ILSNotificationSettings?objectAction=edit&id=' . $this->id;
	}

	public function __get($name) {
		if ($name == 'messageTypes') {
			return $this->getMessageTypes();
		} elseif($name == 'notificationSettings') {
			if(!isset($this->_notificationSettings) && $this->id) {
				$this->_notificationSettings = [];
				$obj = new NotificationSetting();
				$obj->ilsNotificationSettingId = $this->id;
				$obj->find();
				while($obj->fetch()) {
					$this->_notificationSettings[$obj->id] = $obj->name;
				}
			}
			return $this->_notificationSettings;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'messageTypes') {
			$this->_messageTypes = $value;
		} elseif($name == 'notificationSettings') {
			$this->_notificationSettings = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = '') : bool|int {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveMessageTypes();
			$this->saveNotificationSettings();
		}
		return $ret;
	}

	public function insert($context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->updateMessageTypes();
			$this->saveMessageTypes();
			$this->saveNotificationSettings();
		}
		return $ret;
	}


	/**
	 * @return ILSMessageType[]
	 */
	public function getMessageTypes(): array {
		if (!isset($this->_messageTypes)) {
			$this->_messageTypes = [];
			if ($this->id) {
				$obj = new ILSMessageType();
				$obj->ilsNotificationSettingId = $this->id;
				$obj->orderBy('module');
				$obj->find();
				while ($obj->fetch()) {
					$this->_messageTypes[$obj->id] = clone $obj;
				}
			}
		}
		return $this->_messageTypes;
	}

	public function getMessageTypeByCode($code) : ?ILSMessageType {
		$messageTypes = $this->getMessageTypes();
		foreach ($messageTypes as $messageType) {
			if ($messageType->code == $code) {
				return $messageType;
			}
		}
		return null;
	}

	public function saveMessageTypes() : void {
		if (isset ($this->_messageTypes) && is_array($this->_messageTypes)) {
			$this->saveOneToManyOptions($this->_messageTypes, 'ilsNotificationSettingId');
			unset($this->_messageTypes);
		}
	}

	public function updateMessageTypes() : void {
		$messageTypesList = [];

		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = $this->getAccountProfile();
		if($accountProfile) {
			$catalogDriver = trim($accountProfile->driver);
			if (!empty($catalogDriver)) {
				$_catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
				$messageTypesList = $_catalogDriver->getMessageTypes();
			}
		}

		foreach($messageTypesList as $messageType) {
			$newILSMessageType = new ILSMessageType();
			$newILSMessageType->ilsNotificationSettingId = $this->id;
			$newILSMessageType->name = $messageType['name'];
			$newILSMessageType->module = $messageType['module'];
			$newILSMessageType->code = $messageType['code'];
			$newILSMessageType->locationCode = $messageType['branch'];
			$newILSMessageType->insert();
		}
	}

	public function saveNotificationSettings() : void {
		if (isset($this->_notificationSettings) && is_array($this->_notificationSettings)) {
			$notificationSettingsList = [];
			$notificationSettings = new NotificationSetting();
			$notificationSettings->find();
			while($notificationSettings->fetch()) {
				$notificationSettingsList[$notificationSettings->id] = $notificationSettings->id;
			}
			foreach($notificationSettingsList as $notificationSetting) {
				$setting = new NotificationSetting();
				$setting->id = $notificationSetting;
				if($setting->find(true)) {
					if(in_array($notificationSetting, $this->_notificationSettings)) {
						if($setting->ilsNotificationSettingId != $this->id) {
							$setting->ilsNotificationSettingId = $this->id;
							$setting->update();
						}
					} else {
						if($setting->ilsNotificationSettingId == $this->id) {
							$setting->ilsNotificationSettingId = -1;
							$setting->update();
						}
					}
				}
			}
			unset($this->_notificationSettings);
		}
	}

	private AccountProfile|null|false $_accountProfile = false;
	public function getAccountProfile() : ?AccountProfile {
		if ($this->_accountProfile === false) {
			$this->_accountProfile = new AccountProfile();
			$this->_accountProfile->id = $this->accountProfileId;
			if (!$this->_accountProfile->find(true)){
				$this->_accountProfile = null;
			}
		}
		return $this->_accountProfile;
	}

	/**
	 * Modify the structure of the object based on the object currently being edited.
	 * This can be used to change enums or other values based on the object being edited, so we know relationships
	 *
	 * @param $structure
	 * @return array
	 */
	public function updateStructureForEditingObject($structure) : array {
		$accountProfile = $this->getAccountProfile();
		if ($accountProfile) {
			if ($accountProfile->ils == 'sierra') {
				unset($structure['messageTypes']['structure']['module']);
				unset($structure['messageTypes']['structure']['locationCode']);
				unset($structure['messageTypes']['structure']['isDigest']);
			}
		}

		return $structure;
	}
}