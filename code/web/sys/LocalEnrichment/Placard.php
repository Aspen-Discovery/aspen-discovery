<?php

require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardTrigger.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardLibrary.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardLocation.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardLanguage.php';

class Placard extends DataObject
{
	public $__table = 'placards';
	public $id;
	public $title;
	public $body;
	public $image;
	public $link;
	public $css;
	public /** @noinspection PhpUnused */ $dismissable;
	public $startDate;
	public $endDate;

	private $_libraries;
	private $_locations;
	private $_languages;

	static function getObjectStructure() : array {
		$placardTriggerStructure = PlacardTrigger::getObjectStructure();
		unset($placardTriggerStructure['placardId']);

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Placards'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Placards'));
		$languageList = Language::getLanguageList();

		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'title' => array('property'=>'title', 'type'=>'text', 'label'=>'Title', 'description'=>'The title of the placard'),
			'startDate' => array('property'=>'startDate', 'type'=>'timestamp','label'=>'Start Date to Show', 'description'=> 'The first date the placard should be shown, leave blank to always show', 'unsetLabel'=>'No start date'),
			'endDate' => array('property'=>'endDate', 'type'=>'timestamp','label'=>'End Date to Show', 'description'=> 'The end date the placard should be shown, leave blank to always show', 'unsetLabel'=>'No end date'),
			'dismissable' => array('property' => 'dismissable', 'type' => 'checkbox', 'label' => 'Dismissable', 'description' => 'Whether or not a user can dismiss the placard'),
			'body' => array('property'=>'body', 'type'=>'html', 'label'=>'Body', 'description'=>'The body of the placard', 'allowableTags' => '<a><b><em><div><script><span><p><strong><sub><sup>', 'hideInLists' => true),
			'css' => array('property'=>'css', 'type'=>'textarea', 'label'=>'CSS', 'description'=>'Additional styling to apply to the placard', 'hideInLists' => true),
			'image' => array('property' => 'image', 'type' => 'image', 'label' => 'Image (800px x 150px max)', 'description' => 'The logo for use in the header', 'required' => false, 'maxWidth' => 800, 'maxHeight' => 150, 'hideInLists' => true),
			'link' => array('property' => 'link', 'type' => 'url', 'label' => 'Link', 'description' => 'An optional link when clicking on the placard (or link in the placard)', 'hideInLists' => true),
			'triggers' => array(
				'property'=>'triggers',
				'type'=>'oneToMany',
				'label'=>'Triggers',
				'description'=>'Trigger words that will cause the placard to display',
				'keyThis' => 'id',
				'keyOther' => 'placardId',
				'subObjectType' => 'PlacardTrigger',
				'structure' => $placardTriggerStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),
			'languages' => array(
				'property' => 'languages',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Languages',
				'description' => 'Define languages that use this placard',
				'values' => $languageList,
				'hideInLists' => true,
			),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that see this placard',
				'values' => $libraryList,
				'hideInLists' => true,
			),
			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this placard',
				'values' => $locationList,
				'hideInLists' => true,
			),
		];
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new PlacardLibrary();
				$obj->placardId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id){
				$this->_locations = [];
				$obj = new PlacardLocation();
				$obj->placardId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} elseif ($name == 'triggers') {
			$this->getTriggers();
			/** @noinspection PhpUndefinedFieldInspection */
			return $this->triggers;
		} elseif ($name == 'languages') {
			$this->getLanguages();
			return $this->_languages;
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "locations") {
			$this->_locations = $value;
		}elseif ($name == 'triggers') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->triggers = $value;
		}elseif ($name == 'languages') {
			$this->_languages = $value;
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
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveTriggers();
			$this->saveLanguages();
		}
		return $ret;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveTriggers();
			//When inserting a placard, if nothing exists, apply to all languages
			if (empty($this->_languages)){
				$languageList = Language::getLanguageList();
				foreach ($languageList as $languageId => $displayName) {
					$this->_languages[$languageId] = $languageId;
				}
			}
			$this->saveLanguages();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$triggers = new PlacardTrigger();
			$triggers->placardId = $this->id;
			$triggers->delete(true);

			$placardLibrary = new PlacardLibrary();
			$placardLibrary->placardId = $this->id;
			$placardLibrary->delete(true);

			$placardLocation = new PlacardLocation();
			$placardLocation->placardId = $this->id;
			$placardLocation->delete(true);

			$placardLocation = new PlacardLanguage();
			$placardLocation->placardId = $this->id;
			$placardLocation->delete(true);
		}
		return $ret;
	}

	public function saveTriggers(){
		if (isset ($this->triggers) && is_array($this->triggers)) {
			/** @var PlacardTrigger $trigger */
			foreach ($this->triggers as $trigger) {
				if ($trigger->_deleteOnSave == true) {
					$trigger->delete();
				} else {
					if (isset($trigger->id) && is_numeric($trigger->id)) {
						$trigger->update();
					} else {
						$trigger->placardId = $this->id;
						$trigger->insert();
					}
				}
			}
			unset($this->triggers);
		}
	}

	public function getTriggers(){
		if (!isset($this->triggers) && $this->id) {
			$this->triggers = [];
			$trigger = new PlacardTrigger();
			$trigger->placardId = $this->id;
			$trigger->orderBy('triggerWord');
			$trigger->find();
			while ($trigger->fetch()) {
				$this->triggers[$trigger->id] = clone($trigger);
			}
		}
		return $this->triggers;
	}

	public function getLanguages(){
		if (!isset($this->_languages) && $this->id) {
			$this->_languages = [];
			$language = new PlacardLanguage();
			$language->placardId = $this->id;
			$language->find();
			while ($language->fetch()) {
				$this->_languages[$language->languageId] = $language->languageId;
			}
		}
		return $this->_languages;
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Placards'));
			foreach ($libraryList as $libraryId => $displayName){
				$obj = new PlacardLibrary();
				$obj->placardId = $this->id;
				$obj->libraryId = $libraryId;
				if (in_array($libraryId, $this->_libraries)){
					if (!$obj->find(true)){
						$obj->insert();
					}
				}else{
					if ($obj->find(true)){
						$obj->delete();
					}
				}
			}
		}
	}

	public function saveLocations(){
		if (isset ($this->_locations) && is_array($this->_locations)){
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Placards'));
			foreach ($locationList as $locationId => $displayName) {
				$obj = new PlacardLocation();
				$obj->placardId = $this->id;
				$obj->locationId = $locationId;
				if (in_array($locationId, $this->_locations)) {
					if (!$obj->find(true)) {
						$obj->insert();
					}
				} else {
					if ($obj->find(true)) {
						$obj->delete();
					}
				}
			}
		}
	}

	public function saveLanguages(){
		if (isset ($this->_languages) && is_array($this->_languages)){
			$languageList = Language::getLanguageList();
			foreach ($languageList as $languageId => $displayName) {
				$obj = new PlacardLanguage();
				$obj->placardId = $this->id;
				$obj->languageId = $languageId;
				if (in_array($languageId, $this->_languages)) {
					if (!$obj->find(true)) {
						$obj->insert();
					}
				} else {
					if ($obj->find(true)) {
						$obj->delete();
					}
				}
			}
		}
	}

	public function isDismissed(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php';
		//Make sure the user has not dismissed the placard
		if (UserAccount::isLoggedIn()){
			$placardDismissal = new PlacardDismissal();
			$placardDismissal->placardId = $this->id;
			$placardDismissal->userId = UserAccount::getActiveUserId();
			if ($placardDismissal->find(true)){
				//The placard has been dismissed
				return true;
			}
		}
		return false;
	}

	public function isValidForScope(){
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();

		if ($location != null) {
			$placardLocation = new PlacardLocation();
			$placardLocation->placardId = $this->id;
			$placardLocation->find();
			//If no locations are selected, allow at any location
			if ($placardLocation->getNumResults() > 0) {
				$placardLocation->locationId = $location->locationId;
				if ($placardLocation->find(true)){
					return true;
				}else{
					return false;
				}
			}
		}
		$placardLibrary = new PlacardLibrary();
		$placardLibrary->placardId = $this->id;
		$placardLibrary->libraryId = $library->libraryId;
		return $placardLibrary->find(true);
	}

	public function isValidForDisplay(){
		$curTime = time();
		if ($this->startDate != 0 && $this->startDate > $curTime){
			return false;
		}
		if ($this->endDate != 0 && $this->endDate < $curTime){
			return false;
		}
		if ($this->isDismissed()){
			return false;
		}
		if (!$this->isValidForScope()){
			return false;
		}
		//Check to see if the placard is valid based on the language
		global $activeLanguage;
		$validLanguages = $this->getLanguages();
		if (!in_array($activeLanguage->id, $validLanguages)){
			return false;
		}
		return true;
	}
}