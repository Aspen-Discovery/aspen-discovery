<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroupEntry.php';
require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroupUser.php';

class HomeScreenLinkGroup extends DataObject {
	public $__table = 'aspen_lida_home_screen_link_group';
	public $__displayNameColumn = 'name';
	public $id;
	public $name;

	/** @var HomeScreenLinkGroupEntry[] */
	protected $_homeScreenLinks;

	protected $_libraries;
	protected $_locations;

	protected $_additionalEditors;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links'));

		$homeScreenLinkStructure = HomeScreenLinkGroupEntry::getObjectStructure($context);
		unset($homeScreenLinkStructure['weight']);
		unset($homeScreenLinkStructure['homeScreenLinkGroupId']);

		$homeScreenLinkGroupUserStructure = HomeScreenLinkGroupUser::getObjectStructure($context);
		unset($homeScreenLinkGroupUserStructure['homeScreenLinkGroupId']);

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
				'description' => 'The name of the group',
				'maxLength' => 50,
				'required' => true,
			],

			'homeScreenLinks' => [
				'property' => 'homeScreenLinks',
				'type' => 'oneToMany',
				'label' => 'Home Screen Links',
				'description' => 'The links to display on the home screen for this group',
				'keyThis' => 'id',
				'keyOther' => 'homeScreenLinkGroupId',
				'subObjectType' => 'HomeScreenLinkGroupEntry',
				'structure' => $homeScreenLinkStructure,
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
				'description' => 'A list of users that can only edit specified Home Screen link groups',
				'keyThis' => 'id',
				'keyOther' => 'homeScreenLinkGroupId',
				'subObjectType' => 'HomeScreenLinkGroupUser',
				'structure' => $homeScreenLinkGroupUserStructure,
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
				'description' => 'Define libraries that use this home screen link group',
				'values' => $libraryList,
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this home screen link group',
				'values' => $locationList,
			],
		];

		if (UserAccount::userHasPermission('Administer Selected Aspen LiDA Home Screen Link Groups') && !(UserAccount::userHasPermission('AAdminister All Aspen LiDA Home Screen Links') || UserAccount::userHasPermission('Administer Library Aspen LiDA Home Screen Links'))) {
			unset($structure['additionalEditors']);
			unset($structure['libraries']);
			unset($structure['locations']);
		}

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} elseif ($name == 'homeScreenLinks') {
			return $this->getHomeScreenLinks();
		} elseif ($name == 'additionalEditors') {
			return $this->getAdditionalEditors();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->setLibraries($value);
		} elseif ($name == "locations") {
			$this->setLocations($value);
		} elseif ($name == 'homeScreenLinks') {
			$this->_homeScreenLinks = $value;
		} elseif ($name == 'additionalEditors') {
			$this->_additionalEditors = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = ''): int|bool {
		//Updates to properly update settings based on the ILS
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveHomeScreenLinks();
			$this->saveAdditionalEditors();
		}

		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(string $context = ''): int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveHomeScreenLinks();
		}
		return $ret;
	}


	public function saveLibraries(): void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->lidaHomeScreenLinkGroupId != $this->id) {
						$library->lidaHomeScreenLinkGroupId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->lidaHomeScreenLinkGroupId == $this->id) {
						$library->lidaHomeScreenLinkGroupId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations(): void {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links'));
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
					if ($location->lidaHomeScreenLinkGroupId != $this->id) {
						$location->lidaHomeScreenLinkGroupId = $this->id;
						$location->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->lidaHomeScreenLinkGroupId == $this->id) {
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->lidaHomeScreenLinkGroupId != -1) {
							$location->lidaHomeScreenLinkGroupId = -1;
						} else {
							$location->lidaHomeScreenLinkGroupId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	public function saveHomeScreenLinks(): void {
		if (isset ($this->_homeScreenLinks) && is_array($this->_homeScreenLinks)) {
			$uniqueHomeScreenLinks = [];
			/**
			 * @var int $linkId
			 * @var HomeScreenLink $homeScreenLink
			 */
			foreach ($this->_homeScreenLinks as $linkId => $homeScreenLink) {
				if (in_array($homeScreenLink->homeScreenLinkId, $uniqueHomeScreenLinks)) {
					$homeScreenLink->delete();
					unset($this->_homeScreenLinks[$linkId]);
				} else {
					$uniqueHomeScreenLinks[] = $homeScreenLink->homeScreenLinkId;
				}
			}
			$this->saveOneToManyOptions($this->_homeScreenLinks, 'homeScreenLinkGroupId');
			unset($this->_homeScreenLinks);
		}
	}

	/** @return ?Library[] */
	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new Library();
			$obj->lidaHomeScreenLinkGroupId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	/** @return ?Location[] */
	public function getLocations(): ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new Location();
			$obj->lidaHomeScreenLinkGroupId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
	}

	public function setLibraries($val): void {
		$this->_libraries = $val;
	}

	public function setLocations($val): void {
		$this->_locations = $val;
	}

	public function getAdditionalEditors(): ?array {
		if (!isset($this->_additionalEditors) && $this->id) {
			$this->_additionalEditors = [];
			$homeScreenLinkGroupUser = new HomeScreenLinkGroupUser();
			$homeScreenLinkGroupUser->homeScreenLinkGroupId = $this->id;
			$homeScreenLinkGroupUser->find();
			while ($homeScreenLinkGroupUser->fetch()) {
				$this->_additionalEditors[$homeScreenLinkGroupUser->id] = clone($homeScreenLinkGroupUser);
			}
			uasort($this->_additionalEditors, function ($a, $b) {
				return strcasecmp($a->getUserDisplayName(), $b->getUserDisplayName());
			});
		}
		return $this->_additionalEditors;
	}

	public function saveAdditionalEditors(): void {
		if (isset ($this->_additionalEditors) && is_array($this->_additionalEditors)) {
			$uniqueUsers = [];
			/**
			 * @var int $homeScreenLinkGroupUserId
			 * @var HomeScreenLinkGroupUser $homeScreenLinkUser
			 */
			foreach ($this->_additionalEditors as $homeScreenLinkGroupUserId => $homeScreenLinkUser) {
				if (in_array($homeScreenLinkUser->userId, $uniqueUsers)) {
					$homeScreenLinkUser->delete();
					unset($this->_additionalEditors[$homeScreenLinkGroupUserId]);
				} else {
					$uniqueUsers[] = $homeScreenLinkUser->userId;
				}
			}
			$this->saveOneToManyOptions($this->_additionalEditors, 'homeScreenLinkGroupId');
			unset($this->_additionalEditors);
		}
	}

	public function getHomeScreenLinks(): ?array {
		if (!isset($this->_homeScreenLinks) && $this->id) {
			$this->_homeScreenLinks = [];
			$homeScreenLink = new HomeScreenLinkGroupEntry();
			$homeScreenLink->homeScreenLinkGroupId = $this->id;
			$homeScreenLink->orderBy('weight');
			$homeScreenLink->find();
			while ($homeScreenLink->fetch()) {
				$this->_homeScreenLinks[$homeScreenLink->id] = clone($homeScreenLink);
			}
		}
		return $this->_homeScreenLinks;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/AspenLiDA/HomeScreenLinkGroups?objectAction=edit&id=' . $this->id;
	}
}