<?php

require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupEntry.php';
require_once ROOT_DIR . '/sys/DB/LibraryLocationLinkedObject.php';

class BrowseCategoryGroup extends DB_LibraryLocationLinkedObject
{
	public $__table = 'browse_category_group';
	public $__displayNameColumn = 'name';
	public $id;
	public $name;

	public $defaultBrowseMode;
	public $browseCategoryRatingsMode;

	protected $_browseCategories;

	protected $_libraries;
	protected $_locations;

	public static function getObjectStructure() : array{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Browse Categories'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Browse Categories'));
		
		$browseCategoryStructure = BrowseCategoryGroupEntry::getObjectStructure();
		unset($browseCategoryStructure['weight']);
		unset($browseCategoryStructure['browseCategoryGroupId']);

		return [
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name of the group', 'maxLength' => 50, 'required' => true),
			'defaultBrowseMode' => array('property' => 'defaultBrowseMode', 'type' => 'enum', 'label'=>'Default Viewing Mode', 'description' => 'Sets how browse categories will be displayed when users haven\'t chosen themselves.', 'hideInLists' => true,
				'values'=> array('0' => 'Show Covers Only', '1' => 'Show as Grid'),
				'default' => '0'
			),
			'browseCategoryRatingsMode' => array('property' => 'browseCategoryRatingsMode', 'type' => 'enum', 'label' => 'Ratings Mode', 'description' => 'Sets how ratings will be displayed and how user ratings will be enabled when a user is viewing a browse category in the &#34;covers&#34; browse mode. These settings only apply when User Ratings have been enabled. (These settings will also apply to search results viewed in covers mode.)',
				'values' => array(
					'1' => 'Show rating stars and enable user rating via pop-up form.',
					'2' => 'Show rating stars and enable user ratings by clicking the stars.',
					'0' => 'Do not show rating stars.'
				),
				'default' => '1'
			),

			// The specific categories displayed in the carousel
			'browseCategories' => array(
				'property'=>'browseCategories',
				'type'=>'oneToMany',
				'label'=>'Browse Categories',
				'description'=>'Browse Categories To Show on the Home Screen',
				'keyThis' => 'id',
				'keyOther' => 'browseCategoryGroupId',
				'subObjectType' => 'BrowseCategoryGroupEntry',
				'structure' => $browseCategoryStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
			),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this browse category group',
				'values' => $libraryList,
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this browse category group',
				'values' => $locationList,
			),
		];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields() : array
	{
		return ['name'];
	}

	public function __get($name)
	{
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} elseif ($name == 'browseCategories') {
			return $this->getBrowseCategories();
		} else {
			return $this->_data[$name];
		}
	}

	public function getBrowseCategories()
	{
		if (!isset($this->_browseCategories) && $this->id) {
			$this->_browseCategories = array();
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

	public function getBrowseCategoriesForLiDA($max = null, $appUser = null): array
	{
		if (!isset($this->_browseCategories) && $this->id) {
			if ($max) {
				$count = 0;
				$this->_browseCategories = array();
				$browseCategory = new BrowseCategoryGroupEntry();
				$browseCategory->browseCategoryGroupId = $this->id;
				$browseCategory->orderBy('weight');
				if($browseCategory->find()) {
					while($browseCategory->fetch()) {
						if ($count >= $max){
							break;
						}
						if ($browseCategory->isValidForDisplay($appUser)) {
							$count++;
							$this->_browseCategories[$browseCategory->id] = clone($browseCategory);
						}
					}
				}
			} else {
				$this->_browseCategories = array();
				$browseCategory = new BrowseCategoryGroupEntry();
				$browseCategory->browseCategoryGroupId = $this->id;
				$browseCategory->orderBy('weight');
				$browseCategory->find();
				while ($browseCategory->fetch()) {
					if($browseCategory->isValidForDisplay($appUser)) {
						$this->_browseCategories[$browseCategory->id] = clone($browseCategory);
					}
				}
			}
		}
		return $this->_browseCategories;
	}

	public function __set($name, $value)
	{
		if ($name == "libraries") {
			$this->setLibraries($value);
		}elseif ($name == "locations") {
			$this->setLocations($value);
		}elseif ($name == 'browseCategories') {
			$this->_browseCategories = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		//Updates to properly update settings based on the ILS
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveBrowseCategories();
		}

		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveBrowseCategories();
		}
		return $ret;
	}

	public function saveBrowseCategories(){
		if (isset ($this->_browseCategories) && is_array($this->_browseCategories)){
			$uniqueBrowseCategories = [];
			/**
			 * @var int $categoryId
			 * @var BrowseCategory $browseCategory
			 */
			foreach ($this->_browseCategories as $categoryId => $browseCategory){
				if (in_array($browseCategory->browseCategoryId, $uniqueBrowseCategories)){
					$browseCategory->delete();
					unset($this->_browseCategories[$categoryId]);
				}else{
					$uniqueBrowseCategories[] = $browseCategory->browseCategoryId;
				}
			}
			$this->saveOneToManyOptions($this->_browseCategories, 'browseCategoryGroupId');
			unset($this->_browseCategories);
		}
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Browse Categories'));
			foreach ($libraryList as $libraryId => $displayName){
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)){
					//We want to apply the scope to this library
					if ($library->browseCategoryGroupId != $this->id){
						$library->browseCategoryGroupId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->browseCategoryGroupId == $this->id){
						$library->browseCategoryGroupId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations(){
		if (isset ($this->_locations) && is_array($this->_locations)){
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Browse Categories'));
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName){
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)){
					//We want to apply the scope to this library
					if ($location->browseCategoryGroupId != $this->id){
						$location->browseCategoryGroupId = $this->id;
						$location->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->browseCategoryGroupId == $this->id){
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->browseCategoryGroupId != -1){
							$location->browseCategoryGroupId = -1;
						}else{
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
	public function getLibraries() : ?array
	{
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = [];
			$obj = new Library();
			$obj->browseCategoryGroupId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	/** @return Location[] */
	public function getLocations() : ?array
	{
		if (!isset($this->_locations) && $this->id){
			$this->_locations = [];
			$obj = new Location();
			$obj->browseCategoryGroupId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
	}

	public function setLibraries($val)
	{
		$this->_libraries = $val;
	}

	public function setLocations($val)
	{
		$this->_locations = $val;
	}

	public function getLinksForJSON() : array{
		$links = parent::getLinksForJSON();
		//Browse Categories
		$browseCategoriesGroupEntries = $this->getBrowseCategories();
		$links['browseCategories'] = [];
		//We need to be careful of recursion here, so we will preload 2 levels of categories and sub categories
		foreach ($browseCategoriesGroupEntries as $browseCategoryGroupEntry){
			$browseCategoryArray = $browseCategoryGroupEntry->toArray(false, true);
			$browseCategoryArray['links'] = $browseCategoryGroupEntry->getLinksForJSON();

			$links['browseCategories'][] = $browseCategoryArray;
		}

		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting = 'keepExisting') : bool
	{
		$result = parent::loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting);

		if (array_key_exists('browseCategories', $jsonLinks)){
			$browseCategories = [];
			foreach ($jsonLinks['browseCategories'] as $browseCategory){
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