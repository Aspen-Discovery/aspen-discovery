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

	private $_libraries;

	public static function getObjectStructure()
	{
		$libraryList = Library::getLibraryList();

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings'),
			'baseUrl' => array('property' => 'baseUrl', 'type' => 'url', 'label' => 'url', 'description' => 'The URL for the site'),

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
		return $structure;
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
			$libraryLink = new LibraryEventsSetting();
			$libraryLink->settingSource = 'library_market';
			$libraryLink->settingId = $this->id;
			$libraryLink->find();
			while($libraryLink->fetch()){
				$this->_libraries[] = $libraryLink->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function saveLibraries(){
		if (isset($this->_libraries) && is_array($this->_libraries)){
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				/** @var LibraryEventsSetting $libraryLink */
				$libraryLink = new LibraryEventsSetting();

				$libraryLink->settingSource = 'library_market';
				$libraryLink->settingId = $this->id;
				$libraryLink->libraryId = $libraryId;
				$libraryLink->insert();
			}
			unset($this->_libraries);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryLink = new LibraryEventsSetting();
		$libraryLink->settingSource = 'library_market';
		$libraryLink->settingId = $this->id;
		return $libraryLink->delete(true);
	}
}