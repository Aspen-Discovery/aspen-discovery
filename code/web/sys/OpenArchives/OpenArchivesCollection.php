<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/OpenArchives/LibraryOpenArchivesCollection.php';
require_once ROOT_DIR . '/sys/OpenArchives/LocationOpenArchivesCollection.php';

class OpenArchivesCollection extends DataObject {
	public $__table = 'open_archives_collection';
	public $id;
	public $name;
	public $baseUrl;
	public $setName;
	/** @noinspection PhpUnused */
	public $indexAllSets;
	public $subjects;
	public $defaultCover;
	public /** @noinspection PhpUnused */
		$subjectFilters;
	public $imageRegex;
	/** @noinspection PhpUnused */
	public $coverSourceType;
	/** @noinspection PhpUnused */
	public $metadataFormat;
	/** @noinspection PhpUnused */
	public $dateFormatting;
	public /** @noinspection PhpUnused */
		$fetchFrequency;
	public /** @noinspection PhpUnused */
		$loadOneMonthAtATime;
	public $lastFetched;
	public $deleted;

	public $_libraries;
	public $_locations;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Open Archives'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer Open Archives'));

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
				'description' => 'A name to identify the open archives collection in the system',
				'size' => '100',
			],
			'baseURL' => [
				'property' => 'baseUrl',
				'type' => 'url',
				'label' => 'Base URL',
				'description' => 'The url of the open archives site',
				'size' => '255',
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
			'setName' => [
				'property' => 'setName',
				'type' => 'text',
				'label' => 'Set(s) To Index (separate multiple values with commas)',
				'description' => 'The name of the set(s) to harvest',
				'size' => '100',
			],
			'indexAllSets' => [
				'property' => 'indexAllSets',
				'type' => 'checkbox',
				'label' => 'Index all Sets',
				'description' => 'Index all Sets. This should be set or the list of sets to index should be provided.',
				'default' => false,
			],
			'metadataFormat' => [
				'property' => 'metadataFormat',
				'type' => 'enum',
				'values' => [
					'oai_dc' => 'Dublin Core (works for most archives)',
					'mods' => 'MODS',
				],
				'label' => 'Metadata Format',
				'description' => 'The format of the metadata provided by the OAI collection',
			],
			'dateFormatting' => [
				'property' => 'dateFormatting',
				'type' => 'enum',
				'values' => [
					0 => 'Do no date formatting',
					1 => 'Convert to Date Format',
				],
				'label' => 'Date Formatting',
				'description' => 'Either take date exactly how metadata is formatted in the collection or attempt to change to a date format (this is how it worked before)',
			],
			'subjects' => [
				'property' => 'subjects',
				'type' => 'textarea',
				'label' => 'Available Subjects',
				'description' => 'Subjects that exist within the collection',
				'readOnly' => true,
				'hideInLists' => true,
			],
			'subjectFilters' => [
				'property' => 'subjectFilters',
				'type' => 'textarea',
				'label' => 'Subject Filters (each filter on it\'s own line, regular expressions ok)',
				'description' => 'Subjects to filter by',
				'hideInLists' => true,
			],
			'imageRegex' => [
				'property' => 'imageRegex',
				'type' => 'multilineRegularExpression',
				'label' => 'Image Regular Expression',
				'description' => 'A regular expression to extract the thumbnail.',
				'note' => 'Used to extract thumbnails, can be blank, the first capturing group is used for the value, can put multiple expressions on their own line',
			],
			'coverSourceType' => [
				'property' => 'coverSourceType',
				'type' => 'enum',
				'values' => [
					'default' => 'Default (scrape the record page)',
					'dspace' => 'DSpace 7+',
				],
				'label' => 'Cover Source',
				'description' => 'How covers are found for records in this collection.',
				'note' => 'Default scrapes each record\'s page for a thumbnail on demand. DSpace 7+ resolves the cover on demand via that platform\'s REST API instead, and doesn\'t use the Image Regular Expression. Records DSpace reports as having no cover aren\'t checked again until the collection is saved, so save it again after adding or publishing thumbnails in DSpace.',
				'default' => 'default',
				'serverValidation' => 'validateCoverSourceType',
			],
			'fetchFrequency' => [
				'property' => 'fetchFrequency',
				'type' => 'enum',
				'values' => [
					'daily' => 'Daily',
					'weekly' => 'Weekly',
					'monthly' => 'Monthly',
					'yearly' => 'Yearly',
					'once' => 'Once',
				],
				'label' => 'Frequency to Fetch',
				'description' => 'How often the records should be fetched',
			],
			'loadOneMonthAtATime' => [
				'property' => 'loadOneMonthAtATime',
				'type' => 'checkbox',
				'label' => 'Fetch by Month',
				'description' => 'Whether or not records should be fetched by month which increases performance on most servers',
			],
			'lastFetched' => [
				'property' => 'lastFetched',
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

	/**
	 * DSpace 7+ serves OAI-PMH under ${dspace.server.url}/oai and its REST API under
	 * ${dspace.server.url}/api, so the REST API base is derived from the OAI base URL.
	 */
	public function getDSpaceRestApiUrl(): ?string {
		if (preg_match('~^(https?://.+?)/oai(?:/|$)~i', (string)$this->baseUrl, $matches)) {
			return $matches[1] . '/api';
		}
		return null;
	}

	/** @noinspection PhpUnused */
	function validateCoverSourceType(): array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		if ($this->coverSourceType == 'dspace') {
			$restApiUrl = $this->getDSpaceRestApiUrl();
			if ($restApiUrl == null) {
				$validationResults['errors'][] = 'The Base URL is not a DSpace 7+ OAI-PMH URL, it should end in /oai/request.';
			} else {
				require_once ROOT_DIR . '/sys/CurlWrapper.php';
				$curlWrapper = new CurlWrapper();
				$rootJson = $curlWrapper->curlGetPage($restApiUrl);
				$responseCode = $curlWrapper->getResponseCode();
				$curlWrapper->close_curl();
				$root = $responseCode == 200 ? json_decode($rootJson, true) : null;
				if (!is_array($root) || ($root['type'] ?? '') != 'root') {
					$validationResults['errors'][] = "No DSpace 7+ REST API was found at $restApiUrl.";
				}
			}
		}

		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	public function update(string $context = '') : int|bool {
		//Allow last fetched to be overridden
		if (!empty($this->_changedFields) && !in_array('lastFetched', $this->_changedFields)) {
			$this->lastFetched = 0;
		}
		$this->clearDefaultCovers();
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->clearDSpaceMissingCovers();
		}

		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$this->clearDefaultCovers();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$this->clearLibraries();
		$this->clearLocations();
		$this->clearDefaultCovers();
		$this->deleted = true;
		return $this->update();
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new LibraryOpenArchivesCollection();
			$library->collectionId = $this->id;
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
				$libraryOpenArchivesCollection = new LibraryOpenArchivesCollection();

				$libraryOpenArchivesCollection->collectionId = $this->id;
				$libraryOpenArchivesCollection->libraryId = $libraryId;
				$libraryOpenArchivesCollection->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries() : void {
		//Delete links to the libraries
		$libraryOpenArchivesCollection = new LibraryOpenArchivesCollection();
		$libraryOpenArchivesCollection->collectionId = $this->id;
		$libraryOpenArchivesCollection->delete(true);
	}

	public function getLocations() : ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$location = new LocationOpenArchivesCollection();
			$location->collectionId = $this->id;
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
				$locationOpenArchivesCollection = new LocationOpenArchivesCollection();

				$locationOpenArchivesCollection->collectionId = $this->id;
				$locationOpenArchivesCollection->locationId = $libraryId;
				$locationOpenArchivesCollection->insert();
			}
			unset($this->_locations);
		}
	}

	private function clearLocations() : void {
		//Delete links to the libraries
		$locationOpenArchivesCollection = new LocationOpenArchivesCollection();
		$locationOpenArchivesCollection->collectionId = $this->id;
		$locationOpenArchivesCollection->delete(true);
	}

	private function clearDefaultCovers() : void {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$covers = new BookCoverInfo();
		$covers->reloadAllDefaultCovers();
	}

	/**
	 * Forget stored "no cover" answers so they are resolved again through the REST API on their next request.
	 */
	private function clearDSpaceMissingCovers() : void {
		if ($this->coverSourceType != 'dspace' || empty($this->id)) {
			return;
		}
		require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecord.php';
		$record = new OpenArchivesRecord();
		$record->query("UPDATE " . $record->__table . " SET coverUrl = NULL WHERE sourceCollection = " . (int)$this->id . " AND coverUrl = ''");
	}
}