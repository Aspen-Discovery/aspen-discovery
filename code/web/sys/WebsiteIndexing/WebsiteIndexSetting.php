<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/WebsiteIndexing/LibraryWebsiteIndexing.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/LocationWebsiteIndexing.php';

class WebsiteIndexSetting extends DataObject {
	public $__table = 'website_indexing_settings';    // table name
	public $id;
	public $name;
	/** @noinspection PhpUnused */
	public $searchCategory;
	public $siteUrl;
	public $defaultCover;
	public /** @noinspection PhpUnused */
		$pageTitleExpression;
	public /** @noinspection PhpUnused */
		$descriptionExpression;
	public /** @noinspection PhpUnused */
		$pathsToExclude;
	public /** @noinspection PhpUnused */
		$indexFrequency;
	public /** @noinspection PhpUnused */
		$lastIndexed;
	public /** @noinspection PhpUnused */
		$maxPagesToIndex;
	public $deleted;
	public /** @noinspection PhpUnused */
		$crawlDelay;

	public $_libraries;
	public $_locations;

	public function getNumericColumnNames(): array {
		return [
			'lastIndexed',
			'deleted',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Website Indexing Settings'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Website Indexing Settings'));

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
				'description' => 'The name of the website to index',
				'maxLength' => 75,
				'required' => true,
			],
			'searchCategory' => [
				'property' => 'searchCategory',
				'type' => 'text',
				'label' => 'Search Category',
				'description' => 'The category of the index.  All sites with the same category will be searched together',
				'maxLength' => 75,
				'required' => true,
				'default' => 'Library Website',
			],
			'siteUrl' => [
				'property' => 'siteUrl',
				'type' => 'url',
				'label' => 'Site URL / Sitemap URL',
				'description' => 'The URL to the page in the Website to start indexing from or a sitemap to index based on. Sitemap must have a .xml extension.',
				'maxLength' => 255,
				'required' => true,
			],
			'defaultCover' => [
				'property' => 'defaultCover',
				'type' => 'image',
				'label' => 'Background Image for Default Covers (280x280)',
				'description' => 'A background image for default covers (.jpg or .png only)',
				'required' => false,
				'maxWidth' => 280,
				'maxHeight' => 280,
				'hideInLists' => true,
			],
			'pageTitleExpression' => [
				'property' => 'pageTitleExpression',
				'type' => 'regularExpression',
				'label' => 'Regular Expression to find Page Title (ok to leave blank)',
				'description' => 'A regular expression to use to load the title from.  Will use the value of the first group identified.',
				'maxLength' => 255,
				'required' => false,
				'default' => '',
				'hideInLists' => true,
			],
			'descriptionExpression' => [
				'property' => 'descriptionExpression',
				'type' => 'regularExpression',
				'label' => 'Regular Expression to find Description (ok to leave blank)',
				'description' => 'A regular expression to use to load the description from.  Will use the value of the first group identified.',
				'maxLength' => 255,
				'required' => false,
				'default' => '',
				'hideInLists' => true,
			],
			'pathsToExclude' => [
				'property' => 'pathsToExclude',
				'type' => 'textarea',
				'label' => 'Paths to exclude (regular expression)',
				'description' => 'A list of URLs to exclude from the index with each on it\'s own line.',
				'note' => 'Each URL is matched against the paths to see if it should be excluded, the entire path must match so to exclude a directory end with .*.',
				'hideInLists' => true,
			],
			'maxPagesToIndex' => [
				'property' => 'maxPagesToIndex',
				'type' => 'integer',
				'label' => 'Maximum Pages To Index',
				'description' => 'A maximum number of pages to index.',
				'default' => 2500,
			],
			'crawlDelay' => [
				'property' => 'crawlDelay',
				'type' => 'integer',
				'label' => 'Crawl Delay',
				'description' => 'The number of seconds to delay between requesting pages from the site.',
				'default' => 10,
			],
			'indexFrequency' => [
				'property' => 'indexFrequency',
				'type' => 'enum',
				'values' => [
					'hourly' => 'Hourly',
					'daily' => 'Daily',
					'weekly' => 'Weekly',
					'monthly' => 'Monthly',
					'yearly' => 'Yearly',
					'once' => 'Once',
				],
				'label' => 'Frequency to Fetch',
				'description' => 'How often the records should be fetched',
				'default' => 'weekly'
			],
			'lastIndexed' => [
				'property' => 'lastIndexed',
				'type' => 'timestamp',
				'label' => 'Last Fetched (clear to force a new fetch)',
				'description' => 'When the record was last fetched',
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that can view this website',
				'values' => $libraryList,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that can view this website',
				'values' => $locationList,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		if (str_ends_with($this->siteUrl, '/')) {
			$this->siteUrl = substr($this->siteUrl, 0, -1);
		}
		$this->clearDefaultCovers();
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}

		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		if (str_ends_with($this->siteUrl, '/')) {
			$this->siteUrl = substr($this->siteUrl, 0, -1);
		}
		$this->clearDefaultCovers();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$this->deleted = 1;
		$this->clearLibraries();
		$this->clearLocations();
		$this->clearDefaultCovers();
		return $this->update();
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new LibraryWebsiteIndexing();
			$library->settingId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries() : void {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryWebsiteIndexing = new LibraryWebsiteIndexing();

				$libraryWebsiteIndexing->settingId = $this->id;
				$libraryWebsiteIndexing->libraryId = $libraryId;
				$libraryWebsiteIndexing->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries() : void {
		//Delete links to the libraries
		$libraryWebsiteIndexing = new LibraryWebsiteIndexing();
		$libraryWebsiteIndexing->settingId = $this->id;
		$libraryWebsiteIndexing->delete(true);
	}

	public function getLocations() : ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$location = new LocationWebsiteIndexing();
			$location->settingId = $this->id;
			$location->find();
			while ($location->fetch()) {
				$this->_locations[$location->locationId] = $location->locationId;
			}
		}
		return $this->_locations;
	}

	public function saveLocations() : void {
		if (isset($this->_locations) && is_array($this->_locations)) {
			$this->clearLocations();

			foreach ($this->_locations as $libraryId) {
				$locationWebsiteIndexing = new LocationWebsiteIndexing();

				$locationWebsiteIndexing->settingId = $this->id;
				$locationWebsiteIndexing->locationId = $libraryId;
				$locationWebsiteIndexing->insert();
			}
			unset($this->_locations);
		}
	}

	private function clearLocations() : void {
		//Delete links to the libraries
		$locationWebsiteIndexing = new LocationWebsiteIndexing();
		$locationWebsiteIndexing->settingId = $this->id;
		 $locationWebsiteIndexing->delete(true);
	}

	public function isValidForSearching() : bool {
		global $library;
		$searchLocation = Location::getSearchLocation();
		if ($searchLocation != null) {
			$locationWebsiteIndexing = new LocationWebsiteIndexing();
			$locationWebsiteIndexing->settingId = $this->id;
			$locationWebsiteIndexing->locationId = $searchLocation->locationId;
			if ($locationWebsiteIndexing->count() > 0) {
				return true;
			}
		} else {
			$libraryWebsiteIndexing = new LibraryWebsiteIndexing();
			$libraryWebsiteIndexing->settingId = $this->id;
			$libraryWebsiteIndexing->libraryId = $library->libraryId;
			if ($libraryWebsiteIndexing->count() > 0) {
				return true;
			}
		}
		return false;
	}

	private function clearDefaultCovers() : void {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$covers = new BookCoverInfo();
		$covers->reloadAllDefaultCovers();
	}
}