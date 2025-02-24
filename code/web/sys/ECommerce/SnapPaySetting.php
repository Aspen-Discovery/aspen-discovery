<?php

class SnapPaySetting extends DataObject {
	public $__table = 'snappay_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $accountId;
	public $merchantId;
	public $apiAuthenticationCode;
	public $emailNotifications;
	public $emailNotificationsAddresses;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

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
				'description' => 'A name for the settings',
				'maxLength' => 50,
			],
			'sandboxMode' => [
				'property' => 'sandboxMode',
				'type' => 'checkbox',
				'label' => 'Use SnapPay Sandbox (for testing payments only, does not collect money)',
				'description' => 'Whether or not to use SnapPay in Sandbox mode',
				'hideInLists' => false,
				'note' => 'This is for testing only! No funds will be received by the library.',
			],
			'accountId' => [
				'property' => 'accountId',
				'type' => 'text',
				'label' => 'Account ID',
				'description' => 'The Account ID to use when paying fines with SnapPay.',
				'hideInLists' => false,
				'default' => '',
				'size' => 10,
			],
			'merchantId' => [
				'property' => 'merchantId',
				'type' => 'text',
				'label' => 'Merchant ID',
				'description' => 'The Merchant ID to use when paying fines with SnapPay.',
				'hideInLists' => false,
				'default' => '',
				'size' => 20,
			],
			'apiAuthenticationCode' => [
				'property' => 'apiAuthenticationCode',
				'type' => 'storedPassword',
				'label' => 'API Authentication Code',
				'description' => 'The API Authentication Code to use when paying fines with SnapPay.',
				'hideInLists' => true,
				'default' => '',
				'size' => 255,
			],
			'emailNotifications' => [
				'property' => 'emailNotifications',
				'type' => 'enum',
				'values' => [
					0 => 'Do not send email notifications',
					1 => 'Email errors',
					2 => 'Email all transactions',
				],
				'label' => 'Email Notifications',
				'description' => 'Send Email notifications to Library staff for payment transactions',
				'hideInLists' => false,
				'default' => 0,
			],
			'emailNotificationsAddresses' => [
				'property' => 'emailNotificationsAddresses',
				'type' => 'text',
				'label' => 'Email Notification Addresses',
				'description' => 'Semicolon-separated list of email addresses to send notifications to',
				'hideInLists' => false,
				'default' => '',
				'size' => 255,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];

		if (!UserAccount::userHasPermission('Library eCommerce Options')) {
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->snapPaySettingId = $this->id;
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

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries(): void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->snapPaySettingId != $this->id) {
						$library->finePaymentType = 15;
						$library->snapPaySettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->snapPaySettingId == $this->id) {
						if ($library->finePaymentType == 15) {
							$library->finePaymentType = 0;
						}
						$library->snapPaySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}