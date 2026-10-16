<?php /** @noinspection PhpMissingFieldTypeInspection */

class ILSMessageType extends DataObject {
	public $__table = 'ils_message_type';
	public $id;
	public $name;
	public $module;
	public $code;
	/** @noinspection PhpUnused */
	public $isDigest;
	public $locationCode;
	public $isEnabled;
	public $ilsNotificationSettingId;
	/** @noinspection PhpUnused */
	public $_messageTitle;
	/** @noinspection PhpUnused */
	public $_messageBody; //Translatable text block to store the message text if configurable

	public function getNumericColumnNames(): array {
		return [
			'id',
			'isDigest',
			'isEnabled',
			'ilsNotificationSettingId'
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
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
				'description' => 'The name of the Type of Message from the ILS',
				'readOnly' => true,
			],
			'code' => [
				'property' => 'code',
				'type' => 'text',
				'label' => 'Type',
				'description' => 'The Type of Message from the ILS',
				'readOnly' => true,
			],
			'locationCode' => [
				'property' => 'locationCode',
				'type' => 'text',
				'label' => 'Location',
				'description' => 'The location code of the Type of Message from the ILS',
				'readOnly' => true,
			],
			'isDigest' => [
				'property' => 'isDigest',
				'type' => 'checkbox',
				'label' => 'Is Digest',
				'description' => 'If the message type is sent as a digest',
			],
			'isEnabled' => [
				'property' => 'isEnabled',
				'type' => 'checkbox',
				'label' => 'Is Enabled in Aspen',
				'description' => 'Whether or not Aspen will send notifications for this message type',
			],
			'messageTitle' => [
				'property' => 'messageTitle',
				'type' => 'translatablePlainTextBlock',
				'label' => 'Title',
				'description' => 'The title of the message',
				'note' => 'The length of the message is limited by the application operating system. For best compatibility, keep the title under 50 characters.',
				'readOnly' => false,
				'maxLength' => 100,
				'hideInLists' => true,
			],
			'messageBody' => [
				'property' => 'messageBody',
				'type' => 'translatablePlainTextBlock',
				'label' => 'Body',
				'description' => 'The body of the message. The message is limited to 4096 total characters including the body and title. It should not include HTML.',
				'readOnly' => false,
				'maxLength' => 3996,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/AspenLiDA/ILSMessageTypes?objectAction=edit&id=' . $this->id;
	}

	/**
	 * Modify the structure of the object based on the object currently being edited.
	 * This can be used to change enums or other values based on the object being edited, so we know relationships
	 *
	 * @param $structure
	 * @return array
	 */
	public function updateStructureForEditingObject($structure) : array {
		$ilsNotificationSetting = $this->getIlsNotificationSetting();
		if ($ilsNotificationSetting) {
			$accountProfile = $ilsNotificationSetting->getAccountProfile();
			if ($accountProfile) {
				$defaultTitleFile = "ILSMessageType_{$accountProfile->ils}_{$this->code}_title.MD";
				if (file_exists(ROOT_DIR . '/default_translatable_text_fields/' . $defaultTitleFile)){
					$structure['messageTitle']['defaultTextFile'] = $defaultTitleFile;
				}
				$defaultBodyFile = "ILSMessageType_{$accountProfile->driver}_{$this->code}_body.MD";
				if (file_exists(ROOT_DIR . '/default_translatable_text_fields/' . $defaultBodyFile)) {
					$structure['messageBody']['defaultTextFile'] = $defaultBodyFile;
				}
				if ($accountProfile->ils == 'sierra') {
					unset($structure['module']);
					unset($structure['branch']);
				}else{
					unset($structure['messageTitle']);
					unset($structure['messageBody']);
				}
			}
		}

		return $structure;
	}

	private ILSNotificationSetting|null|false $_ilsNotificationSetting = false;
	public function getIlsNotificationSetting() : ?ILSNotificationSetting {
		if ($this->_ilsNotificationSetting === false) {
			$this->_ilsNotificationSetting = new ILSNotificationSetting();
			$this->_ilsNotificationSetting->id = $this->ilsNotificationSettingId;
			if (!$this->_ilsNotificationSetting->find(true)){
				$this->_ilsNotificationSetting = null;
			}
		}
		return $this->_ilsNotificationSetting;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveTextBlockTranslations('messageTitle');
			$this->saveTextBlockTranslations('messageBody');
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveTextBlockTranslations('messageTitle');
			$this->saveTextBlockTranslations('messageBody');
		}
		return $ret;
	}
}