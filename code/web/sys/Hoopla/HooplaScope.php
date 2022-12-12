<?php

require_once ROOT_DIR . '/sys/Hoopla/HooplaSetting.php';

class HooplaScope extends DataObject {
	public $__table = 'hoopla_scopes';
	public $id;
	public $name;
	public $settingId;
	public /** @noinspection PhpUnused */
		$excludeTitlesWithCopiesFromOtherVendors;
	public /** @noinspection PhpUnused */
		$includeEBooks;
	public /** @noinspection PhpUnused */
		$maxCostPerCheckoutEBooks;
	public /** @noinspection PhpUnused */
		$includeEComics;
	public /** @noinspection PhpUnused */
		$maxCostPerCheckoutEComics;
	public /** @noinspection PhpUnused */
		$includeEAudiobook;
	public /** @noinspection PhpUnused */
		$maxCostPerCheckoutEAudiobook;
	public /** @noinspection PhpUnused */
		$includeMovies;
	public /** @noinspection PhpUnused */
		$maxCostPerCheckoutMovies;
	public /** @noinspection PhpUnused */
		$includeMusic;
	public /** @noinspection PhpUnused */
		$maxCostPerCheckoutMusic;
	public /** @noinspection PhpUnused */
		$includeTelevision;
	public /** @noinspection PhpUnused */
		$maxCostPerCheckoutTelevision;
	public /** @noinspection PhpUnused */
		$includeBingePass;
	public /** @noinspection PhpUnused */
		$maxCostPerCheckoutBingePass;
	public /** @noinspection PhpUnused */
		$restrictToChildrensMaterial;
	public /** @noinspection PhpUnused */
		$ratingsToExclude;
	public /** @noinspection PhpUnused */
		$excludeAbridged;
	public /** @noinspection PhpUnused */
		$excludeParentalAdvisory;
	public /** @noinspection PhpUnused */
		$excludeProfanity;
	public /** @noinspection PhpUnused */
		$genresToExclude;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure($context = ''): array {
		$hooplaSettings = [];
		$hooplaSetting = new HooplaSetting();
		$hooplaSetting->find();
		while ($hooplaSetting->fetch()) {
			$hooplaSettings[$hooplaSetting->id] = (string)$hooplaSetting;
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'settingId' => [
				'property' => 'settingId',
				'type' => 'enum',
				'values' => $hooplaSettings,
				'label' => 'Setting Id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The Name of the scope',
				'maxLength' => 50,
			],
			'excludeTitlesWithCopiesFromOtherVendors' => [
				'property' => 'excludeTitlesWithCopiesFromOtherVendors',
				'type' => 'enum',
				'values' => [
					0 => 'No show all titles',
					1 => 'Hide Hoopla title if other copies are available',
					2 => 'Hide Hoopla title if other copies are owned',
				],
				'label' => 'Exclude Records With Copies from other eContent Vendors (OverDrive, cloudLibrary, Axis 360, etc.)',
				'description' => 'Whether or not records in other collections should be included',
				'default' => 0,
				'forcesReindex' => true,
			],
			'includeEAudiobook' => [
				'property' => 'includeEAudiobook',
				'type' => 'checkbox',
				'label' => 'Include eAudio books',
				'description' => 'Whether or not EAudiobook are included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'maxCostPerCheckoutEAudiobook' => [
				'property' => 'maxCostPerCheckoutEAudiobook',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Max Cost Per Checkout for eAudio books',
				'description' => 'The maximum per checkout cost to include',
				'default' => 5,
				'forcesReindex' => true,
			],
			'includeEBooks' => [
				'property' => 'includeEBooks',
				'type' => 'checkbox',
				'label' => 'Include eBooks',
				'description' => 'Whether or not EBooks are included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'maxCostPerCheckoutEBooks' => [
				'property' => 'maxCostPerCheckoutEBooks',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Max Cost Per Checkout for eBooks',
				'description' => 'The maximum per checkout cost to include',
				'default' => 5,
				'forcesReindex' => true,
			],
			'includeEComics' => [
				'property' => 'includeEComics',
				'type' => 'checkbox',
				'label' => 'Include eComics',
				'description' => 'Whether or not EComics are included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'maxCostPerCheckoutEComics' => [
				'property' => 'maxCostPerCheckoutEComics',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Max Cost Per Checkout for eComics',
				'description' => 'The maximum per checkout cost to include',
				'default' => 5,
				'forcesReindex' => true,
			],
			'includeMovies' => [
				'property' => 'includeMovies',
				'type' => 'checkbox',
				'label' => 'Include Movies',
				'description' => 'Whether or not Movies are included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'maxCostPerCheckoutMovies' => [
				'property' => 'maxCostPerCheckoutMovies',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Max Cost Per Checkout for Movies',
				'description' => 'The maximum per checkout cost to include',
				'default' => 5,
				'forcesReindex' => true,
			],
			'includeMusic' => [
				'property' => 'includeMusic',
				'type' => 'checkbox',
				'label' => 'Include Music',
				'description' => 'Whether or not Music is included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'maxCostPerCheckoutMusic' => [
				'property' => 'maxCostPerCheckoutMusic',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Max Cost Per Checkout for Music',
				'description' => 'The maximum per checkout cost to include',
				'default' => 5,
				'forcesReindex' => true,
			],
			'includeTelevision' => [
				'property' => 'includeTelevision',
				'type' => 'checkbox',
				'label' => 'Include Television',
				'description' => 'Whether or not Television is included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'maxCostPerCheckoutTelevision' => [
				'property' => 'maxCostPerCheckoutTelevision',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Max Cost Per Checkout for Television',
				'description' => 'The maximum per checkout cost to include',
				'default' => 5,
				'forcesReindex' => true,
			],
			'includeBingePass' => [
				'property' => 'includeBingePass',
				'type' => 'checkbox',
				'label' => 'Include Binge Pass',
				'description' => 'Whether or not Binge Pass is included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'maxCostPerCheckoutBingePass' => [
				'property' => 'maxCostPerCheckoutBingePass',
				'type' => 'currency',
				'displayFormat' => '%0.2f',
				'label' => 'Max Cost Per Checkout for Binge Pass',
				'description' => 'The maximum per checkout cost to include',
				'default' => 5,
				'forcesReindex' => true,
			],
			'restrictToChildrensMaterial' => [
				'property' => 'restrictToChildrensMaterial',
				'type' => 'checkbox',
				'label' => 'Include Children\'s Materials Only',
				'description' => 'If checked only includes titles identified as children by Hoopla',
				'default' => 0,
				'forcesReindex' => true,
			],
			'ratingsToExclude' => [
				'property' => 'ratingsToExclude',
				'type' => 'text',
				'label' => 'Ratings to Exclude (separate with pipes)',
				'description' => 'A pipe separated list of ratings that should not be included in the index',
				'forcesReindex' => true,
			],
			'excludeAbridged' => [
				'property' => 'excludeAbridged',
				'type' => 'checkbox',
				'label' => 'Exclude Abridged Records',
				'description' => 'Whether or not records marked as abridged should be included',
				'default' => 0,
				'forcesReindex' => true,
			],
			'excludeParentalAdvisory' => [
				'property' => 'excludeParentalAdvisory',
				'type' => 'checkbox',
				'label' => 'Exclude Parental Advisory Records',
				'description' => 'Whether or not records marked with a parental advisory indicator should be included',
				'default' => 0,
				'forcesReindex' => true,
			],
			'excludeProfanity' => [
				'property' => 'excludeProfanity',
				'type' => 'checkbox',
				'label' => 'Exclude Records With Profanity',
				'description' => 'Whether or not records marked with a profanity waning should be included',
				'default' => 0,
				'forcesReindex' => true,
			],
			'genresToExclude' => [
				'property' => 'genresToExclude',
				'type' => 'textarea',
				'label' => 'Genres To Exlude (each filter on it\'s own line, regular expressions ok)',
				'description' => 'Genres To Exclude',
				'hideInLists' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'values' => $libraryList,
				'hideInLists' => true,
				'forcesReindex' => true,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'values' => $locationList,
				'hideInLists' => true,
				'forcesReindex' => true,
			],
		];
	}

	/** @noinspection PhpUnused */
	public function getEditLink($context): string {
		return '/Hoopla/Scopes?objectAction=edit&id=' . $this->id;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->hooplaScopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id) {
				$this->_locations = [];
				$obj = new Location();
				$obj->hooplaScopeId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->hooplaScopeId != $this->id) {
						$library->hooplaScopeId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->hooplaScopeId == $this->id) {
						$library->hooplaScopeId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName) {
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)) {
					//We want to apply the scope to this library
					if ($location->hooplaScopeId != $this->id) {
						$location->hooplaScopeId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->hooplaScopeId == $this->id) {
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->hooplaScopeId != -1) {
							$location->hooplaScopeId = -1;
						} else {
							$location->hooplaScopeId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}
}