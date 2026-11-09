<?php /** @noinspection PhpMissingFieldTypeInspection */

class LibraryBorrowBoxSettings extends DataObject {
	public $__table = 'library_borrowbox_settings';
	public $id;
	public $weight;
	public $settingId;
	public $libraryId;
	public $siteId;
	public $circulationEnabled;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'libraryId',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$borrowBoxSettings = [];
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';
		$borrowBoxSetting = new BorrowBoxSetting();
		$borrowBoxSetting->find();
		while ($borrowBoxSetting->fetch()) {
			$borrowBoxSettings[$borrowBoxSetting->id] = (string)$borrowBoxSetting;
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
				'values' => $borrowBoxSettings,
				'label' => 'BorrowBox Settings',
				'description' => 'The BorrowBox settings to use',
				'default' => -1,
				'forcesReindex' => true,
			],
			'siteId' => [
				'property' => 'siteId',
				'type' => 'text',
				'label' => 'BorrowBox Site ID',
				'description' => 'The BorrowBox Site ID assigned to this library for API access',
				'maxLength' => 50,
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
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getEditLink(string $context): string {
		if ($context == 'libraries') {
			return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId . '#propertyRowborrowBoxSettings';
		} else {
			return '/BorrowBox/Settings?objectAction=edit&id=' . $this->settingId;
		}
	}

	private $_borrowBoxSettings = null;

	public function getBorrowBoxSettings(): ?BorrowBoxSetting {
		if ($this->_borrowBoxSettings == null) {
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';
			$this->_borrowBoxSettings = new BorrowBoxSetting();
			$this->_borrowBoxSettings->id = $this->settingId;
			if (!$this->_borrowBoxSettings->find(true)) {
				$this->_borrowBoxSettings = null;
			}
		}
		return $this->_borrowBoxSettings;
	}
}
