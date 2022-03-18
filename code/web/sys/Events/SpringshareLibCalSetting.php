<?php
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';

/**
 * Settings for Springshare - LibCal integration
 */
class SpringshareLibCalSetting extends DataObject
{
	public $__table = 'springshare_libcal_settings';
	public $id;
	public $name;
	public $baseUrl;
    public $calId;
	public /** @noinspection PhpUnused */ $clientId;
	public /** @noinspection PhpUnused */ $clientSecret;
	public $username;
	public $password;

	private $_libraries;

	public static function getObjectStructure() : array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Springshare LibCal Settings'));

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings'),
			'baseUrl' => array('property' => 'baseUrl', 'type' => 'url', 'label' => 'Base URL (i.e. https://yoursite.libcal.com)', 'description' => 'The URL for the site'),
			'calId' => array('property' => 'calId', 'type' => 'text', 'label' => 'Calendar IDs', 'description' => 'Comma-delimited list of LibCal Calendar IDs. Leave blank to retrieve all public calendars.', 'default' => '', 'maxLength' => 255),
            'clientId' => array('property' => 'clientId', 'type' => 'integer', 'label' => 'Client ID', 'description' => 'Client ID'),
			'clientSecret' => array('property' => 'clientSecret', 'type' => 'storedPassword', 'label' => 'Client Secret', 'description' => 'Client Secret', 'maxLength' => 36, 'hideInLists' => true),
			'username' => array('property' => 'username', 'type' => 'text', 'label' => 'Springshare LibCal Admin Username', 'description' => 'Username', 'default'=>'', 'maxLength' => 36),
			'password' => array('property' => 'password', 'type' => 'storedPassword', 'label' => 'Springshare LibCal Admin Password', 'description' => 'Password', 'maxLength' => 36, 'hideInLists' => true),

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
			$library->settingSource = 'springshare';
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

				$libraryEventSetting->settingSource = 'springshare';
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
		$libraryEventSetting->settingSource = 'springshare';
		$libraryEventSetting->settingId = $this->id;
		return $libraryEventSetting->delete(true);
	}
}