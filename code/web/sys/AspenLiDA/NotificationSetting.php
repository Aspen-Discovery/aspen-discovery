<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class NotificationSetting extends DataObject
{

	public $__table = 'aspen_lida_notification_setting';
	public $id;
	public $name;
	public $sendTo;
	public $notifySavedSearch;

	private $_libraries;

	static function getObjectStructure() : array {
		$sendToOptions = [0 => 'None (disabled)', 1 => 'Only Staff Users', 2 => 'All Users'];
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name for the app without spaces', 'maxLength' => 50, 'required' => true),
			'sendTo' => array('property' => 'sendTo', 'type' => 'enum', 'values' => $sendToOptions, 'label' => 'Send In-App Notifications To', 'description' => 'Determine who should receive in-app notifications.', 'hideInLists' => true),
			'notificationTypeSection'=> array('property'=>'notificationTypeSection', 'type' => 'section', 'label' =>'Notification Types', 'renderAsHeading' => true, 'hideInLists' => true, 'properties' => array(
				'notifySavedSearch' => array('property' => 'notifySavedSearch', 'type' => 'checkbox', 'label' => 'Saved Searches', 'description' => 'Whether or not to send notifications for saved search updates.'),
			)),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
			),
		);
		if (!UserAccount::userHasPermission('Administer Aspen LiDA Settings')){
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->lidaNotificationSettingId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update(){
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName){
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)){
					//We want to apply the scope to this library
					if ($library->lidaNotificationSettingId != $this->id){
						$library->lidaNotificationSettingId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->lidaNotificationSettingId == $this->id){
						$library->lidaNotificationSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}