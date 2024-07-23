<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class ILSNotificationSetting extends DataObject {

	public $__table = 'ils_notification_setting';
	public $id;
	public $name;

	private $_messageTypes;
	private $_notificationSettings;

	public function getNumericColumnNames(): array {
		return [
			'id',
		];
	}

	static function getObjectStructure($context = ''): array {
		$notificationSettings = [];
		$notificationSetting = new NotificationSetting();
		$notificationSetting->find();
		while($notificationSetting->fetch()) {
			$notificationSettings[$notificationSetting->id] = $notificationSetting->name;
		}

		require_once ROOT_DIR . '/sys/AspenLiDA/ILSMessageType.php';
		$messageTypeStructure = ILSMessageType::getObjectStructure($context);

		$structure = [
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

		return $structure;
	}

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

	/**
	 * @return int|bool
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveMessageTypes();
			$this->saveNotificationSettings();
		}
		return $ret;
	}

	public function insert($context = '') {
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

	public function saveMessageTypes() {
		if (isset ($this->_messageTypes) && is_array($this->_messageTypes)) {
			$this->saveOneToManyOptions($this->_messageTypes, 'ilsNotificationSettingId');
			unset($this->_messageTypes);
		}
	}

	public function updateMessageTypes() {
		$messageTypesList = [];
		if (UserAccount::getActiveUserObj()->getCatalogDriver()) {
			$messageTypesList = UserAccount::getActiveUserObj()->getCatalogDriver()->getMessageTypes();
		}

		foreach ($messageTypesList as $messageTypes) {
			foreach($messageTypes as $messageType) {
				$newILSMessageType = new ILSMessageType();
				$newILSMessageType->ilsNotificationSettingId = $this->id;
				$newILSMessageType->attributeId = $messageType['attribute_id'];
				$newILSMessageType->module = $messageType['module'];
				$newILSMessageType->code = $messageType['code'];
				$newILSMessageType->isDigest = (bool)$messageType['is_digest'];
				$newILSMessageType->locationCode = $messageType['branch'];
				$newILSMessageType->insert();
			}
		}
	}

	public function saveNotificationSettings() {
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
}