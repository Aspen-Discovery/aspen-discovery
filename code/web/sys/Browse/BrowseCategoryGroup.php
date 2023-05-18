<?php

require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupEntry.php';
require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupUser.php';
require_once ROOT_DIR . '/sys/DB/LibraryLocationLinkedObject.php';

class BrowseCategoryGroup extends DB_LibraryLocationLinkedObject {
	public $__table = 'browse_category_group';
	public $__displayNameColumn = 'name';
	public $id;
	public $name;

	public $defaultBrowseMode;
	public $browseCategoryRatingsMode;

	protected $_browseCategories;

	protected $_libraries;
	protected $_locations;
	protected $_additionalEditors;

	public static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Browse Categories'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Browse Categories'));

		$browseCategoryStructure = BrowseCategoryGroupEntry::getObjectStructure($context);
		unset($browseCategoryStructure['weight']);
		unset($browseCategoryStructure['browseCategoryGroupId']);

		$browseCategoryUserStructure = BrowseCategoryGroupUser::getObjectStructure($context);
		unset($browseCategoryUserStructure['browseCategoryGroupId']);

		$objectStructure = [
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the group',
				'maxLength' => 50,
				'required' => true,
			],
			'defaultBrowseMode' => [
				'property' => 'defaultBrowseMode',
				'type' => 'enum',
				'label' => 'Default Viewing Mode',
				'description' => 'Sets how browse categories will be displayed when users haven\'t chosen themselves.',
				'hideInLists' => true,
				'values' => [
					'0' => 'Show Covers Only',
					'1' => 'Show as Grid',
				],
				'default' => '0',
			],
			'browseCategoryRatingsMode' => [
				'property' => 'browseCategoryRatingsMode',
				'type' => 'enum',
				'label' => 'Ratings Mode',
				'description' => 'Sets how ratings will be displayed and how user ratings will be enabled when a user is viewing a browse category in the &#34;covers&#34; browse mode. These settings only apply when User Ratings have been enabled. (These settings will also apply to search results viewed in covers mode.)',
				'values' => [
					'1' => 'Show rating stars and enable user rating via pop-up form.',
					'2' => 'Show rating stars and enable user ratings by clicking the stars.',
					'0' => 'Do not show rating stars.',
				],
				'default' => '1',
			],

			// The specific categories displayed in the carousel
			'browseCategories' => [
				'property' => 'browseCategories',
				'type' => 'oneToMany',
				'label' => 'Browse Categories',
				'description' => 'Browse Categories To Show on the Home Screen',
				'keyThis' => 'id',
				'keyOther' => 'browseCategoryGroupId',
				'subObjectType' => 'BrowseCategoryGroupEntry',
				'structure' => $browseCategoryStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],

			'additionalEditors' => [
				'property' => 'additionalEditors',
				'type' => 'oneToMany',
				'label' => 'Additional Users who can edit this group',
				'description' => 'A list of users that can only edit specified browse category groups',
				'keyThis' => 'id',
				'keyOther' => 'browseCategoryGroupId',
				'subObjectType' => 'BrowseCategoryGroupUser',
				'structure' => $browseCategoryUserStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
				'hideInLists' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this browse category group',
				'values' => $libraryList,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this browse category group',
				'values' => $locationList,
			],
		];

		if (UserAccount::userHasPermission('Administer Selected Browse Category Groups') && !(UserAccount::userHasPermission('Administer All Browse Categories') || UserAccount::userHasPermission('Administer Library Browse Categories'))) {
			unset($objectStructure['additionalEditors']);
			unset($objectStructure['libraries']);
			unset($objectStructure['locations']);
		}

		return $objectStructure;
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array {
		return ['name'];
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} elseif ($name == 'browseCategories') {
			return $this->getBrowseCategories();
		} elseif ($name == 'additionalEditors') {
			return $this->getAdditionalEditors();
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function getAdditionalEditors() {
		if (!isset($this->_additionalEditors) && $this->id) {
			$this->_additionalEditors = [];
			$browseCategoryUser = new BrowseCategoryGroupUser();
			$browseCategoryUser->browseCategoryGroupId = $this->id;
			$browseCategoryUser->find();
			while ($browseCategoryUser->fetch()) {
				$this->_additionalEditors[$browseCategoryUser->id] = clone($browseCategoryUser);
			}
			uasort($this->_additionalEditors, function ($a, $b) { return strcasecmp($a->getUserDisplayName(), $b->getUserDisplayName());});
		}
		return $this->_additionalEditors;
	}

	public function getBrowseCategories() {
		if (!isset($this->_browseCategories) && $this->id) {
			$this->_browseCategories = [];
			$browseCategory = new BrowseCategoryGroupEntry();
			$browseCategory->browseCategoryGroupId = $this->id;
			$browseCategory->orderBy('weight');
			$browseCategory->find();
			while ($browseCategory->fetch()) {
				$this->_browseCategories[$browseCategory->id] = clone($browseCategory);
			}
		}
		return $this->_browseCategories;
	}

	public function getBrowseCategoriesForLiDA($max = null, $appUser = null, $checkDismiss = true): array {
		if (!isset($this->_browseCategories) && $this->id) {
			if ($max) {
				$count = 0;
				$this->_browseCategories = [];
				$browseCategory = new BrowseCategoryGroupEntry();
				$browseCategory->browseCategoryGroupId = $this->id;
				$browseCategory->orderBy('weight');
				if ($browseCategory->find()) {
					while ($browseCategory->fetch()) {
						if ($count >= $max) {
							break;
						}
						if ($browseCategory->isValidForDisplay($appUser, $checkDismiss)) {
							$thisCategory = $browseCategory->getBrowseCategory();
							if ($thisCategory->textId != "system_saved_searches" && $thisCategory->textId != "system_user_lists") {
								$count++;
							}
							$this->_browseCategories[$browseCategory->id] = clone($browseCategory);
						}
					}
				}
			} else {
				$this->_browseCategories = [];
				$browseCategory = new BrowseCategoryGroupEntry();
				$browseCategory->browseCategoryGroupId = $this->id;
				$browseCategory->orderBy('weight');
				$browseCategory->find();
				while ($browseCategory->fetch()) {
					if ($browseCategory->isValidForDisplay($appUser, $checkDismiss)) {
						$this->_browseCategories[$browseCategory->id] = clone($browseCategory);
					}
				}
			}
		}
		return $this->_browseCategories;
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->setLibraries($value);
		} elseif ($name == "locations") {
			$this->setLocations($value);
		} elseif ($name == 'browseCategories') {
			$this->_browseCategories = $value;
		} elseif ($name == 'additionalEditors') {
			$this->_additionalEditors = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		//Updates to properly update settings based on the ILS
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveBrowseCategories();
			$this->saveAdditionalEditors();
		}

		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveBrowseCategories();
			$this->saveAdditionalEditors();
		}
		return $ret;
	}

	public function saveAdditionalEditors() {
		if (isset ($this->_additionalEditors) && is_array($this->_additionalEditors)) {
			$uniqueUsers = [];
			/**
			 * @var int $browseCategoryGroupUserId
			 * @var BrowseCategoryGroupUser $browseCategoryUser
			 */
			foreach ($this->_additionalEditors as $browseCategoryGroupUserId => $browseCategoryUser) {
				if (in_array($browseCategoryUser->userId, $uniqueUsers)) {
					$browseCategoryUser->delete();
					unset($this->_additionalEditors[$browseCategoryGroupUserId]);
				} else {
					$uniqueUsers[] = $browseCategoryUser->userId;
				}
			}
			$this->saveOneToManyOptions($this->_additionalEditors, 'browseCategoryGroupId');
			unset($this->_additionalEditors);
		}
	}

	public function saveBrowseCategories() {
		if (isset ($this->_browseCategories) && is_array($this->_browseCategories)) {
			$uniqueBrowseCategories = [];
			/**
			 * @var int $categoryId
			 * @var BrowseCategory $browseCategory
			 */
			foreach ($this->_browseCategories as $categoryId => $browseCategory) {
				if (in_array($browseCategory->browseCategoryId, $uniqueBrowseCategories)) {
					$browseCategory->delete();
					unset($this->_browseCategories[$categoryId]);
				} else {
					$uniqueBrowseCategories[] = $browseCategory->browseCategoryId;
				}
			}
			$this->saveOneToManyOptions($this->_browseCategories, 'browseCategoryGroupId');
			unset($this->_browseCategories);
		}
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Browse Categories'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->browseCategoryGroupId != $this->id) {
						$library->browseCategoryGroupId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->browseCategoryGroupId == $this->id) {
						$library->browseCategoryGroupId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Browse Categories'));
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
					if ($location->browseCategoryGroupId != $this->id) {
						$location->browseCategoryGroupId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->browseCategoryGroupId == $this->id) {
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->browseCategoryGroupId != -1) {
							$location->browseCategoryGroupId = -1;
						} else {
							$location->browseCategoryGroupId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return Library[] */
	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new Library();
			$obj->browseCategoryGroupId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	/** @return Location[] */
	public function getLocations(): ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new Location();
			$obj->browseCategoryGroupId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
	}

	public function setLibraries($val) {
		$this->_libraries = $val;
	}

	public function setLocations($val) {
		$this->_locations = $val;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//Browse Categories
		$browseCategoriesGroupEntries = $this->getBrowseCategories();
		$links['browseCategories'] = [];
		//We need to be careful of recursion here, so we will preload 2 levels of categories and sub categories
		foreach ($browseCategoriesGroupEntries as $browseCategoryGroupEntry) {
			$browseCategoryArray = $browseCategoryGroupEntry->toArray(false, true);
			$browseCategoryArray['links'] = $browseCategoryGroupEntry->getLinksForJSON();

			$links['browseCategories'][] = $browseCategoryArray;
		}

		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting);

		if (array_key_exists('browseCategories', $jsonLinks)) {
			$browseCategories = [];
			foreach ($jsonLinks['browseCategories'] as $browseCategory) {
				$browseCategoryObj = new BrowseCategoryGroupEntry();
				$browseCategoryObj->browseCategoryGroupId = $this->id;
				$browseCategoryObj->loadFromJSON($browseCategory, $mappings, $overrideExisting);
				$browseCategories[$browseCategoryObj->browseCategoryId] = $browseCategoryObj;
			}
			$this->_browseCategories = $browseCategories;
			$result = true;
		}
		return $result;
	}
}