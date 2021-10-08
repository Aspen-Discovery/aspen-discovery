<?php
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';

/**
 * Settings for Library Market - Library Calendar integration
 */
class LMLibraryCalendarSetting extends DataObject
{
	public $__table = 'lm_library_calendar_settings';
	public $id;
	public $name;
	public $baseUrl;
	public /** @noinspection PhpUnused */ $clientId;
	public /** @noinspection PhpUnused */ $clientSecret;
	public $username;
	public $password;

	private $_libraries;

	public static function getObjectStructure() : array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Library Calendar Settings'));

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings'),
			'baseUrl' => array('property' => 'baseUrl', 'type' => 'url', 'label' => 'Base URL (i.e. https://yoursite.librarycalendar.com)', 'description' => 'The URL for the site'),
			'clientId' => array('property' => 'clientId', 'type' => 'text', 'label' => 'Client ID', 'description' => 'Client ID for retrieving the staff feed', 'maxLength' => 36),
			'clientSecret' => array('property' => 'clientSecret', 'type' => 'storedPassword', 'label' => 'Client Secret', 'description' => 'Client Secret for retrieving the staff feed', 'maxLength' => 36, 'hideInLists' => true),
			'username' => array('property' => 'username', 'type' => 'text', 'label' => 'Library Calendar Admin Username', 'description' => 'Username for retrieving the staff feed', 'default'=>'lc_feeds_staffadmin', 'maxLength' => 36),
			'password' => array('property' => 'password', 'type' => 'storedPassword', 'label' => 'Library Calendar Admin Password', 'description' => 'Password for retrieving the staff feed', 'maxLength' => 36, 'hideInLists' => true),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true
			),
		);
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
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
		}
		return $ret;
	}

	public function __get($name){
		if ($name == "libraries") {
			return $this->getLibraries();
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
		}
		return $ret;
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = array();
			$library = new LibraryEventsSetting();
			$library->settingSource = 'library_market';
			$library->settingId = $this->id;
			$library->find();
			while($library->fetch()){
				$this->_libraries[$library->libraryId] = $library->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries(){
		if (isset($this->_libraries) && is_array($this->_libraries)){
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryEventSetting = new LibraryEventsSetting();

				$libraryEventSetting->settingSource = 'library_market';
				$libraryEventSetting->settingId = $this->id;
				$libraryEventSetting->libraryId = $libraryId;
				$libraryEventSetting->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryEventSetting = new LibraryEventsSetting();
		$libraryEventSetting->settingSource = 'library_market';
		$libraryEventSetting->settingId = $this->id;
		return $libraryEventSetting->delete(true);
	}
}