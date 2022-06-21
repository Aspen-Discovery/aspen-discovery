<?php
require_once ROOT_DIR . '/sys/WebBuilder/LibraryBasicPage.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/BasicPageAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/BasicPageCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/BasicPageAccess.php';

class BasicPage extends DataObject
{
	public $__table = 'web_builder_basic_page';
	public $id;
	public $title;
	public $urlAlias;
	public $requireLogin;
	public $requireLoginUnlessInLibrary;
	public $teaser;
	public $contents;
	public $lastUpdate;

	private $_libraries;
	private $_audiences;
	private $_categories;
	private $_allowAccess;

	static function getObjectStructure() : array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Basic Pages'));
		$audiencesList = WebBuilderAudience::getAudiences();
		$categoriesList = WebBuilderCategory::getCategories();
		$patronTypeList = PType::getPatronTypeList();
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'title' => array('property' => 'title', 'type' => 'text', 'label' => 'Title', 'description' => 'The title of the page', 'size' => '40', 'maxLength'=>100),
			'urlAlias' => array('property' => 'urlAlias', 'type' => 'text', 'label' => 'URL Alias (no domain, should start with /)', 'description' => 'The url of the page (no domain name)', 'size' => '40', 'maxLength'=>100),
			'teaser' => ['property' => 'teaser', 'type' => 'textarea', 'label' => 'Teaser', 'description' => 'Teaser for display on portals', 'maxLength' => 512, 'hideInLists' => true],
			'contents' => array('property' => 'contents', 'type' => 'markdown', 'label' => 'Page Contents', 'description' => 'The contents of the page', 'hideInLists' => true),
			'requireLogin' => ['property' => 'requireLogin', 'type' => 'checkbox', 'label' => 'Require login to access', 'description' => 'Require login to access page', 'onchange' => 'return AspenDiscovery.WebBuilder.updateWebBuilderFields();', 'default' => 0],
			'requireLoginUnlessInLibrary' => ['property' => 'requireLoginUnlessInLibrary', 'type' => 'checkbox', 'label' => 'Allow access without logging in while in library', 'description' => 'Require login to access page unless in library', 'default' => 0],
			'allowAccess' => array(
				'property' => 'allowAccess',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Allow Access',
				'description' => 'Define what patron types should have access to the page',
				'values' => $patronTypeList,
				'hideInLists' => false,
			),
			'audiences' => array(
				'property' => 'audiences',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Audience',
				'description' => 'Define audiences for the page',
				'values' => $audiencesList,
				'hideInLists' => false
			),
			'categories' => array(
				'property' => 'categories',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Categories',
				'description' => 'Define categories for the page',
				'values' => $categoriesList,
				'hideInLists' => false
			),
			'lastUpdate' => array('property' => 'lastUpdate', 'type' => 'timestamp', 'label' => 'Last Update', 'description' => 'When the page was changed last', 'default' => 0),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true
			),
		];
	}

	public function getFormattedContents()
	{
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		return $parsedown->parse($this->contents);
	}

	public function insert(){
		$this->lastUpdate = time();
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
			$this->saveAccess();
		}
		return $ret;
	}

	public function update(){
		$this->lastUpdate = time();
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
			$this->saveAccess();
		}
		return $ret;
	}

	public function __get($name){
		if ($name == "libraries") {
			return $this->getLibraries();
		}elseif ($name == "audiences") {
			return $this->getAudiences();
		}elseif ($name == "categories") {
			return $this->getCategories();
		}elseif ($name == "allowAccess") {
			return $this->getAccess();
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "audiences") {
			$this->_audiences = $value;
		}elseif ($name == "categories") {
			$this->_categories = $value;
		}elseif ($name == "allowAccess") {
			$this->_allowAccess = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
			$this->clearAudiences();
			$this->clearCategories();
			$this->clearAccess();
		}
		return $ret;
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = array();
			$libraryLink = new LibraryBasicPage();
			$libraryLink->basicPageId = $this->id;
			$libraryLink->find();
			while($libraryLink->fetch()){
				$this->_libraries[$libraryLink->libraryId] = $libraryLink->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function getAudiences() {
		if (!isset($this->_audiences) && $this->id){
			$this->_audiences = array();
			$audienceLink = new BasicPageAudience();
			$audienceLink->basicPageId = $this->id;
			$audienceLink->find();
			while($audienceLink->fetch()){
				$this->_audiences[$audienceLink->audienceId] = $audienceLink->audienceId;
			}
		}
		return $this->_audiences;
	}

	public function getCategories() {
		if (!isset($this->_categories) && $this->id){
			$this->_categories = array();
			$categoryLink = new BasicPageCategory();
			$categoryLink->basicPageId = $this->id;
			$categoryLink->find();
			while($categoryLink->fetch()){
				$this->_categories[$categoryLink->categoryId] = $categoryLink->categoryId;
			}
		}
		return $this->_categories;
	}

	public function getAccess() {
		if (!isset($this->_allowAccess) && $this->id){
			$this->_allowAccess = array();
			$patronTypeLink = new BasicPageAccess();
			$patronTypeLink->basicPageId = $this->id;
			$patronTypeLink->find();
			while($patronTypeLink->fetch()){
				$this->_allowAccess[$patronTypeLink->patronTypeId] = $patronTypeLink->patronTypeId;
			}
		}
		return $this->_allowAccess;
	}

	public function saveLibraries(){
		if (isset($this->_libraries) && is_array($this->_libraries)){
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryLink = new LibraryBasicPage();

				$libraryLink->basicPageId = $this->id;
				$libraryLink->libraryId = $libraryId;
				$libraryLink->insert();
			}
			unset($this->_libraries);
		}
	}

	public function saveAudiences(){
		if (isset($this->_audiences) && is_array($this->_audiences)){
			$this->clearAudiences();

			foreach ($this->_audiences as $audienceId) {
				$link = new BasicPageAudience();

				$link->basicPageId = $this->id;
				$link->audienceId = $audienceId;
				$link->insert();
			}
			unset($this->_audiences);
		}
	}

	public function saveCategories(){
		if (isset($this->_categories) && is_array($this->_categories)){
			$this->clearCategories();

			foreach ($this->_categories as $categoryId) {
				$link = new BasicPageCategory();

				$link->basicPageId = $this->id;
				$link->categoryId = $categoryId;
				$link->insert();
			}
			unset($this->_categories);
		}
	}

	public function saveAccess(){
		if (isset($this->_allowAccess) && is_array($this->_allowAccess)){
			$this->clearAccess();

			foreach ($this->_allowAccess as $patronTypeId) {
				$link = new BasicPageAccess();

				$link->basicPageId = $this->id;
				$link->patronTypeId = $patronTypeId;
				$link->insert();
			}
			unset($this->_allowAccess);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryLink = new LibraryBasicPage();
		$libraryLink->basicPageId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearAudiences()
	{
		//Delete links to the libraries
		$link = new BasicPageAudience();
		$link->basicPageId = $this->id;
		return $link->delete(true);
	}

	private function clearCategories()
	{
		//Delete links to the libraries
		$link = new BasicPageCategory();
		$link->basicPageId = $this->id;
		return $link->delete(true);
	}

	private function clearAccess()
	{
		//Delete links to the patron types
		$link = new BasicPageAccess();
		$link->basicPageId = $this->id;
		return $link->delete(true);
	}
}