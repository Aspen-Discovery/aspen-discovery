<?php /** @noinspection PhpMissingFieldTypeInspection */

class LibraryOverDriveSettings extends DataObject {
	public $__table = 'library_overdrive_settings';
	public $id;
	public $weight;
	public $settingId;
	public $libraryId;
	public $clientSecret;
	public $clientKey;
	public $circulationEnabled;
	public $authenticationILSName;
	public $requirePin;
	/** @noinspection PhpUnused - Used in indexer */
	public $overdriveAdvantageName;
	/** @noinspection PhpUnused - Used in indexer */
	public $overdriveAdvantageProductsKey;
	public $overdriveAdvantageId;
	public $additionalAdvantageId;
	public $additionalAdvantageProductsKey;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'libraryId',
			'overdriveAdvantageId',
			'additionalAdvantageId',
		];
	}

	public function getEncryptedFieldNames(): array {
		return ['clientSecret'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$overdriveSettings = [];
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
		$overdriveSetting = new OverDriveSetting();
		$overdriveSetting->find();
		while ($overdriveSetting->fetch()) {
			$overdriveSettings[$overdriveSetting->id] = (string)$overdriveSetting;
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'The id of a library',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'settingId' => [
				'property' => 'settingId',
				'type' => 'enum',
				'values' => $overdriveSettings,
				'label' => 'OverDrive Settings',
				'description' => 'The OverDrive settings to use',
				'default' => -1,
				'forcesReindex' => true,
			],
			'circulationEnabled' => [
				'property' => 'circulationEnabled',
				'type' => 'checkbox',
				'label' => 'Circulation Enabled',
				'description' => 'Whether or not circulation is enabled within Aspen',
				'hideInLists' => false,
				'default' => true,
				'forcesReindex' => false,
			],
			'authenticationILSName' => [
				'property' => 'authenticationILSName',
				'type' => 'text',
				'label' => 'The ILS Name Overdrive uses for user Authentication',
				'description' => 'The name of the ILS that OverDrive uses to authenticate users logging into the Overdrive website.',
				'size' => '20',
				'hideInLists' => false,
				'required' => true,
			],
			'requirePin' => [
				'property' => 'requirePin',
				'type' => 'checkbox',
				'label' => 'Is a Pin Required to log into Overdrive website?',
				'description' => 'Turn on to allow repeat search in Overdrive functionality.',
				'hideInLists' => false,
				'default' => 1,
			],
			'overdriveAdvantageName' => [
				'property' => 'overdriveAdvantageName',
				'type' => 'text',
				'label' => 'Overdrive Advantage Name',
				'description' => 'The name of the OverDrive Advantage account if any.',
				'size' => '80',
				'hideInLists' => false,
				'forcesReindex' => true,
			],
			'overdriveAdvantageId' => [
				'property' => 'overdriveAdvantageId',
				'type' => 'text',
				'label' => 'Overdrive Advantage Products ID',
				'description' => 'The ID of the OverDrive Advantage account if any.',
				'size' => '20',
				'hideInLists' => false,
				'forcesReindex' => true,
			],
			'overdriveAdvantageProductsKey' => [
				'property' => 'overdriveAdvantageProductsKey',
				'type' => 'text',
				'label' => 'Overdrive Advantage Products Key',
				'description' => 'The products key for use when building urls to the API from the advantageAccounts call.',
				'size' => '80',
				'hideInLists' => false,
				'forcesReindex' => true,
			],
			'additionalAdvantageId' => [
				'property' => 'additionalAdvantageId',
				'type' => 'text',
				'label' => 'Overdrive Additional Advantage Products ID',
				'description' => 'The ID of the OverDrive Advantage account if any.',
				'size' => '20',
				'hideInLists' => false,
				'forcesReindex' => true,
			],
			'additionalAdvantageProductsKey' => [
				'property' => 'additionalAdvantageProductsKey',
				'type' => 'text',
				'label' => 'Overdrive Additional Advantage Products Key',
				'description' => 'The products key for use when building urls to the API from the advantageAccounts call.',
				'size' => '80',
				'hideInLists' => false,
				'forcesReindex' => true,
			],
			'clientKey' => [
				'property' => 'clientKey',
				'type' => 'text',
				'label' => 'Circulation Client Key (if different from settings)',
				'description' => 'The client key provided by OverDrive when registering',
			],
			'clientSecret' => [
				'property' => 'clientSecret',
				'type' => 'storedPassword',
				'label' => 'Circulation Client Secret (if different from settings)',
				'description' => 'The client secret provided by OverDrive when registering',
				'hideInLists' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getEditLink(string $context): string {
		if ($context == 'libraries') {
			return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId . '#propertyRowoverDriveScopes';
		} else {
			return '/OverDrive/Settings?objectAction=edit&id=' . $this->settingId;
		}
	}

	private $_overDriveSettings = null;

	public function getOverDriveSettings(): ?OverDriveSetting{
		if ($this->_overDriveSettings == null) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
			$this->_overDriveSettings = new OverDriveSetting();
			$this->_overDriveSettings->id = $this->settingId;
			if (!$this->_overDriveSettings->find(true)) {
				$this->_overDriveSettings = null;
			}
		}
		return $this->_overDriveSettings;
	}
}
