<?php

require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageLibrary.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageLocation.php';
class SystemMessage extends DataObject
{
	public $__table = 'system_messages';
	public $id;
	public $title;
	public $message;
	public $showOn;
	public /** @noinspection PhpUnused */ $dismissable;
	public $messageStyle;
	public $startDate;
	public $endDate;

	private $_libraries;
	private $_locations;
	private $_preFormattedMessage;

	static function getObjectStructure() : array{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All System Messages'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All System Messages'));
		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'title' => array('property'=>'title', 'type'=>'text', 'label'=>'Title (not shown)', 'description'=>'The title of the system message'),
			'message' => array('property'=>'message', 'type'=>'markdown', 'label'=>'Message to show', 'description'=>'The body of the system message', 'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>', 'hideInLists' => true),
			'showOn' => array('property'=>'showOn', 'type'=>'enum', 'values' => [0=>'All Pages', 1=>'All Account Pages', 2=>'Checkouts Page', 3=>'Holds Page', 4=>'Fines Page'], 'label' => 'Show On', 'description'=>'The pages this message should be shown on'),
			'messageStyle' => array('property'=>'messageStyle', 'type'=>'enum', 'values' => [''=>'none', 'danger'=>'Danger (red)', 'warning'=>'Warning (yellow)', 'info'=>'Info (blue)', 'success'=>'Success (Green)'], 'label' => 'Message Style', 'description'=>'The default style of the message'),
			'startDate' => array('property'=>'startDate', 'type'=>'timestamp','label'=>'Start Date to Show', 'description'=> 'The first date the system message should be shown, leave blank to always show', 'unsetLabel'=>'No start date'),
			'endDate' => array('property'=>'endDate', 'type'=>'timestamp','label'=>'End Date to Show', 'description'=> 'The end date the system message should be shown, leave blank to always show', 'unsetLabel'=>'No end date'),
			'dismissable' => array('property' => 'dismissable', 'type' => 'checkbox', 'label' => 'Dismissable', 'description' => 'Whether or not a user can dismiss the system message'),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that see this system message',
				'values' => $libraryList,
				'hideInLists' => true,
			),
			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this system message',
				'values' => $locationList,
				'hideInLists' => true,
			),
		];
	}

	public function getNumericColumnNames() : array
	{
		return['showOn', 'startDate', 'endDate', 'dismissable'];
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new SystemMessageLibrary();
				$obj->systemMessageId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id){
				$this->_locations = [];
				$obj = new SystemMessageLocation();
				$obj->systemMessageId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "locations") {
			$this->_locations = $value;
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
		}
		return $ret;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$systemMessageLibrary = new SystemMessageLibrary();
			$systemMessageLibrary->systemMessageId = $this->id;
			$systemMessageLibrary->delete(true);

			$systemMessageLocation = new SystemMessageLocation();
			$systemMessageLocation->systemMessageId = $this->id;
			$systemMessageLocation->delete(true);
		}
		return $ret;
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All System Messages'));
			foreach ($libraryList as $libraryId => $displayName){
				$obj = new SystemMessageLibrary();
				$obj->systemMessageId = $this->id;
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
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All System Messages'));
			foreach ($locationList as $locationId => $displayName) {
				$obj = new SystemMessageLocation();
				$obj->systemMessageId = $this->id;
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

	public function isDismissed(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
		//Make sure the user has not dismissed the system message
		if (UserAccount::isLoggedIn()){
			$systemMessageDismissal = new SystemMessageDismissal();
			$systemMessageDismissal->systemMessageId = $this->id;
			$systemMessageDismissal->userId = UserAccount::getActiveUserId();
			if ($systemMessageDismissal->find(true)){
				//The system message has been dismissed
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
			$systemMessageLocation = new SystemMessageLocation();
			$systemMessageLocation->systemMessageId = $this->id;
			$systemMessageLocation->locationId = $location->locationId;
			return $systemMessageLocation->find(true);
		}else {
			$systemMessageLibrary = new SystemMessageLibrary();
			$systemMessageLibrary->systemMessageId = $this->id;
			$systemMessageLibrary->libraryId = $library->libraryId;
			return $systemMessageLibrary->find(true);
		}
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
		return true;
	}

	public function getFormattedMessage(){
		if (empty($this->_preFormattedMessage)){
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$parsedown->setBreaksEnabled(true);
			return translate(['text'=>$parsedown->parse($this->message),'isPublicFacing'=>true, 'isAdminEnteredData'=>true]);
		}else{
			return translate(['text'=>$this->_preFormattedMessage,'isPublicFacing'=>true, 'isAdminEnteredData'=>true]);
		}
	}

	public function setPreFormattedMessage($message){
		$this->_preFormattedMessage = $message;
	}
}