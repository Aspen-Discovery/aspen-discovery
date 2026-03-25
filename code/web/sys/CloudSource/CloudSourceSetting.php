<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/CloudSource/LibraryCloudSourceSetting.php';
require_once ROOT_DIR . '/sys/CloudSource/LocationCloudSourceSetting.php';

class CloudSourceSetting extends DataObject
{
	public $__table = 'cloudsource_setting';
	public $id;
	public $name;
	public $baseUrl;
	public $accessToken;
	public $profileKey;
	public $showInExploreMore;
	public $bypassAspenCloudSourcePage;


	public $_libraries;
	public $_locations;

	function getEncryptedFieldNames(): array
	{
		return ['profileKey'];
	}

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array
	{
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer CloudSource OA'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer CloudSource OA'));

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
				'description' => 'The base url for CloudSource OA',
				'size' => '255',
			],
			'accessToken' => [
				'property' => 'accessToken',
				'type' => 'text',
				'label' => 'Access Token',
				'description' => 'The access token provided by CloudSource OA.',
				'hideInLists' => true,
			],
			'profileKey' => [
				'property' => 'profileKey',
				'type' => 'storedPassword',
				'label' => 'Profile Key',
				'description' => 'The profile key provided by CloudSource OA.',
				'hideInLists' => true,
			],
			'showInExploreMore' => [
				'property' => 'showInExploreMore',
				'type' => 'checkbox',
				'label' => 'Show in Explore More',
			],
			'bypassAspenCloudSourcePage' => [
				'property' => 'bypassAspenCloudSourcePage',
				'type' => 'checkbox',
				'label' => 'Bypass Aspen CloudSource Record Page',
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

	public function __get($name)
	{
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value)
	{
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = ''): int|bool
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}

		return $ret;
	}

	public function insert(string $context = ''): int|bool
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): bool|int
	{
		$this->clearLibraries();
		$this->clearLocations();
		return parent::delete($useWhere, $hardDelete);
	}

	public function getLibraries(): ?array
	{
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$library = new LibraryCloudSourceSetting();
			$library->cloudsourceSettingId = $this->id;
			$library->find();
			while ($library->fetch()) {
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries(): void
	{
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryCloudSourceSetting = new LibraryCloudSourceSetting();
				$libraryCloudSourceSetting->cloudsourceSettingId = $this->id;
				$libraryCloudSourceSetting->libraryId = $libraryId;
				$libraryCloudSourceSetting->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries(): void
	{
		//Delete links to the libraries
		$libraryCloudSourceSetting = new LibraryCloudSourceSetting();
		$libraryCloudSourceSetting->cloudsourceSettingId = $this->id;
		$libraryCloudSourceSetting->delete(true);
	}

	public function getLocations(): ?array
	{
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$location = new LocationCloudSourceSetting();
			$location->cloudsourceSettingId = $this->id;
			$location->find();
			while ($location->fetch()) {
				$this->_locations[$location->locationId] = $location->locationId;
			}
		}
		return $this->_locations;
	}

	public function saveLocations(): void
	{
		if (isset($this->_locations) && is_array($this->_locations)) {
			$this->clearLocations();

			foreach ($this->_locations as $libraryId) {
				$locationCloudSourceSetting = new LocationCloudSourceSetting();
				$locationCloudSourceSetting->cloudsourceSettingId = $this->id;
				$locationCloudSourceSetting->locationId = $libraryId;
				$locationCloudSourceSetting->insert();
			}
			unset($this->_locations);
		}
	}

	private function clearLocations(): void
	{
		//Delete links to the locations
		$locationCloudSourceSetting = new LocationCloudSourceSetting();
		$locationCloudSourceSetting->cloudsourceSettingId = $this->id;
		$locationCloudSourceSetting->delete(true);
	}
}