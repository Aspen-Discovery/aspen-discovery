<?php

require_once ROOT_DIR . '/sys/DB/LibraryLocationLinkedObject.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotificationLibrary.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotificationLocation.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/LiDANotificationPType.php';

require_once ROOT_DIR . '/sys/Account/User.php';
require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';

class LiDANotification extends DB_LibraryLocationLinkedObject
{
	public $__table = 'aspen_lida_notifications';
	public $id;
	public $title;
	public $message;
	public $sendOn;
	public $expiresOn;
	public $ctaUrl;
	public $ctaLabel;
	public $sent;

	protected $_libraries;
	protected $_locations;
	protected $_ptypes;
	protected $_preFormattedMessage;

	static function getObjectStructure() : array{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All System Messages'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All System Messages'));
		$ptypeList = PType::getPatronTypeList();

		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'title' => array('property'=>'title', 'type'=>'text', 'label'=>'Title', 'description'=>'The title of the notification', 'required' => true),
			'message' => array('property'=>'message', 'type'=>'markdown', 'label'=>'Message', 'description'=>'The body of the notification', 'hideInLists' => true, 'required' => true, 'note' => 'HTML tags are not permitted and will be stripped out'),
			'sendOn' => array('property'=>'sendOn', 'type'=>'timestamp','label'=>'Sends on', 'description'=> 'When to send the notification to users', 'required' => true),
			'expireOn' => array('property'=>'expireOn', 'type'=>'timestamp','label'=>'Expires on', 'description'=> 'The time the notification will expire', 'note' => 'If left blank, expiration will be set to 7 days from send time'),
			'ctaUrl' => array('property' => 'ctaUrl', 'type' => 'text', 'label' => 'Call to Action URL', 'description' => 'A URL for users to be redirected to when opening the notification', 'hideInLists' => true),
			'ctaLabel' => array('property' => 'ctaLabel', 'type' => 'text', 'label' => 'Call to Action Label', 'description' => 'The title of the button triggering the call to action URL', 'hideInLists' => true, 'default' => 'View'),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that see this notification',
				'values' => $libraryList,
				'hideInLists' => true,
			),
			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this notification',
				'values' => $locationList,
				'hideInLists' => true,
			),
			'patronTypes' => array(
				'property' => 'patronTypes',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Patron Types',
				'description' => 'Define what patron types should receive this notification',
				'values' => $ptypeList,
				'hideInLists' => true,
			),
			'sent' => array('property'=>'sent', 'type'=>'checkbox','label'=>'Notification sent', 'description'=> 'Whether or not the system has processed and sent the notification', 'note' => 'Need to resend? Uncheck to trigger a new notification'),
		];
	}

	public function getNumericColumnNames() : array
	{
		return['sendOn', 'expiresOn'];
	}

	public function __get($name){
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} elseif ($name == "patronTypes") {
			return $this->getPatronTypes();
		} else{
			return $this->_data[$name];
		}
	}

	public function getLocations(): ?array
	{
		if (!isset($this->_locations) && $this->id){
			$this->_locations = [];
			$obj = new LiDANotificationLocation();
			$obj->lidaNotificationId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
	}

	public function getLibraries(): ?array
	{
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = [];
			$obj = new LiDANotificationLibrary();
			$obj->lidaNotificationId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function getPatronTypes(): ?array
	{
		if (!isset($this->_ptypes) && $this->id){
			$this->_ptypes = [];
			$obj = new LiDANotificationPType();
			$obj->lidaNotificationId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_ptypes[$obj->id] = $obj->id;
			}
		}
		return $this->_ptypes;
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "locations") {
			$this->_locations = $value;
		}elseif ($name == "patronTypes") {
			$this->_ptypes = $value;
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
			$this->savePatronTypes();
		}
		return $ret;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->savePatronTypes();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$lidaNotificationLibrary = new LiDANotificationLibrary();
			$lidaNotificationLibrary->lidaNotificationId = $this->id;
			$lidaNotificationLibrary->delete(true);

			$lidaNotificationLocation = new LiDANotificationLocation();
			$lidaNotificationLocation->lidaNotificationId = $this->id;
			$lidaNotificationLocation->delete(true);

			$lidaNotificationPType = new LiDANotificationPType();
			$lidaNotificationPType->lidaNotificationId = $this->id;
			$lidaNotificationPType->delete(true);
		}
		return $ret;
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All System Messages'));
			foreach ($libraryList as $libraryId => $displayName){
				$obj = new LiDANotificationLibrary();
				$obj->lidaNotificationId = $this->id;
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
				$obj = new LiDANotificationLocation();
				$obj->lidaNotificationId = $this->id;
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

	public function savePatronTypes(){
		if (isset ($this->_ptypes) && is_array($this->_ptypes)){
			$patronTypesList = PType::getPatronTypeList();
			foreach ($patronTypesList as $id => $pType) {
				$obj = new LiDANotificationPType();
				$obj->lidaNotificationId = $this->id;
				$obj->patronTypeId = $id;
				if (in_array($id, $this->_ptypes)) {
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

	public function isValidForScope(){
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();

		if ($location != null) {
			$lidaNotificationLocation = new LiDANotificationLocation();
			$lidaNotificationLocation->lidaNotificationId = $this->id;
			$lidaNotificationLocation->locationId = $location->locationId;
			return $lidaNotificationLocation->find(true);
		}else {
			$lidaNotificationLibrary = new LiDANotificationLibrary();
			$lidaNotificationLibrary->lidaNotificationId = $this->id;
			$lidaNotificationLibrary->libraryId = $library->libraryId;
			return $lidaNotificationLibrary->find(true);
		}
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

	public function okToExport(array $selectedFilters) : bool{
		return parent::okToExport($selectedFilters);
	}

	public function getEligibleUsers() {
		$users = [];
		$tokens = [];

		$libraryForNotifications = new LiDANotificationLibrary();
		$libraryForNotifications->lidaNotificationId = $this->id;
		$libraries = $libraryForNotifications->fetchAll('libraryId');

		$locationForNotifications = new LiDANotificationLocation();
		$locationForNotifications->lidaNotificationId = $this->id;
		$locations = $locationForNotifications->fetchAll('locationId');

		$ptypesForNotifications = new LiDANotificationPType();
		$ptypesForNotifications->lidaNotificationId = $this->id;
		$ptypes = $ptypesForNotifications->fetchAll('patronTypeId');

		foreach($ptypes as $ptype) {
			$getPTypes = new LiDANotificationPType();
			$displayLabel = $getPTypes->getPtypeById($ptype);

			$usersForPType = new PType();
			$usersForPType->pType = $displayLabel;
			$usersForPType->find();
			while($usersForPType->fetch()) {
				$user = new User();
				$user->patronType = $displayLabel;
				if($user->find() && $user->canReceiveNotifications($user, 'notifyCustom')) {
					$users[$displayLabel] = $user->fetchAll('id');
				}
			}
		}

		foreach($users as $user => $userArray) {
			foreach ($userArray as $obj) {
				$n = new User();
				$n->id = $obj;
				if ($n->find(true)) {
					$homeLocation = $n->getHomeLocation();
					$homeLibrary = $n->getHomeLibrary();
					if (in_array($homeLocation->locationId, $locations) && in_array($homeLibrary->libraryId, $libraries)) {
						$token = new UserNotificationToken();
						$token->userId = $obj;
						$token->notifyCustom = 1;
						$token->find();
						while ($token->fetch()) {
							$userToken['uid'] = $n->id;
							$userToken['token'] = $token->pushToken;
							$tokens[] = $userToken;
						}
					}
				}
			}
		}
		return $tokens;
	}
}