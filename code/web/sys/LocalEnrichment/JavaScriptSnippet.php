<?php

require_once ROOT_DIR . '/sys/DB/LIbraryLocationLinkedObject.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippetLibrary.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippetLocation.php';
class JavaScriptSnippet extends DB_LibraryLocationLinkedObject
{
	public $__table = 'javascript_snippets';
	public $id;
	public $name;
	public $snippet;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure() : array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));

		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'name' => ['property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the snippet', 'maxLength' => 50],
			'snippet' => ['property'=>'snippet', 'type'=>'javascript', 'label'=>'Snippet (include script tags)', 'description'=>'The JavaScript Snippet to add to pages', 'hideInLists' => true],
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this snippet',
				'values' => $libraryList,
				'hideInLists' => true
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this snippet',
				'values' => $locationList,
				'hideInLists' => true
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
			$javascriptSnippetLibrary = new JavaScriptSnippetLibrary();
			$javascriptSnippetLibrary->javascriptSnippetId = $this->id;
			$javascriptSnippetLibrary->delete(true);

			$javascriptSnippetLocation = new JavaScriptSnippetLocation();
			$javascriptSnippetLocation->javascriptSnippetId = $this->id;
			$javascriptSnippetLocation->delete(true);
		}
		return $ret;
	}

	public function __get($name){
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		}else{
			return $this->_data[$name];
		}
	}

	/**
	 * @return int[]
	 */
	public function getLibraries() : array{
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = [];
			$obj = new JavaScriptSnippetLibrary();
			$obj->javascriptSnippetId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	/**
	 * @return int[]
	 */
	public function getLocations() : array{
		if (!isset($this->_locations) && $this->id){
			$this->_locations = [];
			$obj = new JavaScriptSnippetLocation();
			$obj->javascriptSnippetId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
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

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));
			foreach ($libraryList as $libraryId => $displayName){
				$obj = new JavaScriptSnippetLibrary();
				$obj->javascriptSnippetId = $this->id;
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
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All JavaScript Snippets'));
			foreach ($locationList as $locationId => $displayName) {
				$obj = new JavaScriptSnippetLocation();
				$obj->javascriptSnippetId = $this->id;
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

	public function getLinksForJSON() : array{
		return parent::getLinksForJSON();
	}

	public function loadLinksFromJSON($jsonLinks, $mappings){
		parent::loadLinksFromJSON($jsonLinks, $mappings);
	}

	public function okToExport(array $selectedFilters) : bool{
		return true;
	}
}