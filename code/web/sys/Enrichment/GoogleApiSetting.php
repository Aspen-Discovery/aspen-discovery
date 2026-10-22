<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Enrichment/LibraryGoogleAnalytics.php';
class GoogleApiSetting extends DataObject {
	public $__table = 'google_api_settings';    // table name
	public $id;
	public $googleAnalyticsVersion;
	public $googleAnalyticsTrackingId;
	public $googleBooksKey;
	public $googleMapsKey;
	public $googleTranslateKey;

	public $_libraryGoogleAnalytics;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$libraryGoogleAnalyticsStructure = LibraryGoogleAnalytics::getObjectStructure($context);
		unset($libraryGoogleAnalyticsStructure['googleApiSettingId']);
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'googleAnalyticsVersion' => [
				'property' => 'googleAnalyticsVersion',
				'type' => 'enum',
				'values' => [
					'v4' => 'Version 4',
				],
				'label' => 'Google Analytics Version',
				'description' => 'The version of Google Analytics to use',
			],
			'googleAnalyticsTrackingId' => [
				'property' => 'googleAnalyticsTrackingId',
				'type' => 'text',
				'label' => 'Google Analytics Measurement ID',
				'description' => 'The Google Analytics Measurement ID to use globally or as a fallback value',
			],
			'googleBooksKey' => [
				'property' => 'googleBooksKey',
				'type' => 'storedPassword',
				'label' => 'Google Books Key',
				'description' => 'The Google books API key to use',
				'hideInLists' => true,
			],
			'googleMapsKey' => [
				'property' => 'googleMapsKey',
				'type' => 'storedPassword',
				'label' => 'Google Maps Key',
				'description' => 'The Google maps API key to use',
				'hideInLists' => true,
			],
			'googleTranslateKey' => [
				'property' => 'googleTranslateKey',
				'type' => 'storedPassword',
				'label' => 'Google Translate Key',
				'description' => 'The Google Translate API key to use',
				'hideInLists' => true,
			],
			'libraryGoogleAnalytics' => [
				'property' => 'libraryGoogleAnalytics',
				'type' => 'oneToMany',
				'label' => 'Library Google Analytics',
				'description' => 'Per library Google Analytics (overrides defaults above)',
				'keyThis' => 'id',
				'keyOther' => 'googleApiSettingId',
				'subObjectType' => 'LibraryGoogleAnalytics',
				'structure' => $libraryGoogleAnalyticsStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "libraryGoogleAnalytics") {
			return $this->getLibraryGoogleAnalytics();
		} else {
			return parent::__get($name);
		}
	}

	public function getLibraryGoogleAnalytics(): ?array {
		if (!isset($this->_libraryGoogleAnalytics) && $this->id) {
			$this->_libraryGoogleAnalytics = [];
			$obj = new LibraryGoogleAnalytics();
			$obj->googleApiSettingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraryGoogleAnalytics[$obj->id] = clone $obj;
			}
		}
		return $this->_libraryGoogleAnalytics;
	}

	public function __set($name, $value) {
		if ($name == "libraryGoogleAnalytics") {
			$this->_libraryGoogleAnalytics = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraryGoogleAnalytics();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraryGoogleAnalytics();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$loadingMessage = new LibraryGoogleAnalytics();
			$loadingMessage->googleApiSettingId = $this->id;
			$loadingMessage->delete(true);
		}
		return $ret;
	}

	public function saveLibraryGoogleAnalytics() : void {
		if (isset ($this->_libraryGoogleAnalytics) && is_array($this->_libraryGoogleAnalytics)) {
			$this->saveOneToManyOptions($this->_libraryGoogleAnalytics, 'googleApiSettingId');
			unset($this->_libraryGoogleAnalytics);
		}
	}
}