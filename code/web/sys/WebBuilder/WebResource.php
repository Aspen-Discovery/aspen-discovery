<?php
require_once ROOT_DIR . '/sys/WebBuilder/LibraryWebResource.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourceAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourceCategory.php';

class WebResource extends DataObject
{
	public $__table = 'web_builder_resource';
	public $id;
	public $name;
	public $logo;
	public $url;
	public $openInNewTab;
	public /** @noinspection PhpUnused */ $featured;
	public /** @noinspection PhpUnused */ $requiresLibraryCard;
	public /** @noinspection PhpUnused */ $inLibraryUseOnly;
	public $requireLoginUnlessInLibrary;
	public /** @noinspection PhpUnused */ $teaser;
	public $description;
	public $lastUpdate;

	protected $_libraries;
	protected $_audiences;
	protected $_categories;

	public function getNumericColumnNames() : array
	{
		return ['id', 'openInNewTab', 'featured', 'requiresLibraryCard', 'inLibraryUseOnly', 'lastUpdate'];
	}

	static function getObjectStructure() : array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Web Resources'));
		$audiencesList = WebBuilderAudience::getAudiences();
		$categoriesList = WebBuilderCategory::getCategories();
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name of the resource', 'size' => '40', 'maxLength'=>100),
			'url' => array('property' => 'url', 'type' => 'url', 'label' => 'URL', 'description' => 'The url of the resource', 'size' => '40', 'maxLength'=>255),
			'openInNewTab' => array('property' => 'openInNewTab', 'type' => 'checkbox', 'label' => 'Open In New Tab', 'description' => 'Whether or not the link should open in a new tab', 'default' => false, 'hideInLists'=>true),
			'logo' => array('property' => 'logo', 'type' => 'image', 'label' => 'Logo', 'description' => 'An image to display for the resource', 'thumbWidth' => 200, 'hideInLists'=>true),
			'featured' => array('property' => 'featured', 'type' => 'checkbox', 'label' => 'Featured?', 'description' => 'Whether or not the resource is a featured resource', 'default'=>0),
			'inLibraryUseOnly' => array('property' => 'inLibraryUseOnly', 'type' => 'checkbox', 'label' => 'In Library Use Only?', 'description' => 'Whether or not the resource can only be used in the library', 'default'=>0, 'hideInLists'=>true),
			'requiresLibraryCard' => array('property' => 'requiresLibraryCard', 'type' => 'checkbox', 'label' => 'Requires Library Card?', 'description' => 'Whether or not the resource requires a library card to use it', 'default'=>0, 'hideInLists'=>true),
			'requireLoginUnlessInLibrary' => array('property' => 'requireLoginUnlessInLibrary', 'type' => 'checkbox', 'label' => 'Requires being logged in to access, unless in library', 'description' => 'Whether or not the resource requires patron to be logged in to use it unless they are in the library', 'default'=>0, 'hideInLists'=>true),
			'teaser' => array('property' => 'teaser', 'type' => 'markdown', 'label' => 'Teaser', 'description' => 'A short description of the resource to show in lists', 'hideInLists' => true),
			'description' => array('property' => 'description', 'type' => 'markdown', 'label' => 'Description', 'description' => 'A description of the resource', 'hideInLists' => true),
			'audiences' => array(
				'property' => 'audiences',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Audience',
				'description' => 'Define audiences for the page',
				'values' => $audiencesList,
				'hideInLists' => true
			),
			'categories' => array(
				'property' => 'categories',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Categories',
				'description' => 'Define categories for the page',
				'values' => $categoriesList,
				'hideInLists' => true
			),
			'lastUpdate' => array('property' => 'lastUpdate', 'type' => 'timestamp', 'label' => 'Last Update', 'description' => 'When the resource was changed last', 'default' => 0),
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

	/** @noinspection PhpUnused */
	public function getFormattedDescription()
	{
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		require_once ROOT_DIR .'/sys/SystemVariables.php';
		$parsedown->setBreaksEnabled(true);
		return $parsedown->parse($this->description);
	}

	public function insert(){
		$this->lastUpdate = time();
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
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
		}
		return $ret;
	}

	public function getLibraries() {
		if (!isset($this->_libraries) && $this->id){
			$this->_libraries = array();
			$libraryLink = new LibraryWebResource();
			$libraryLink->webResourceId = $this->id;
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
			$audienceLink = new WebResourceAudience();
			$audienceLink->webResourceId = $this->id;
			$audienceLink->find();
			while($audienceLink->fetch()){
				$audience = $audienceLink->getAudience();
				if ($audience != false){
					$this->_audiences[$audienceLink->audienceId] = $audience;
				}
			}
			$sorter = function(WebBuilderAudience $a, WebBuilderAudience $b) {
				return strcasecmp($a->name, $b->name);
			};
			uasort($this->_audiences, $sorter);
		}
		return $this->_audiences;
	}

	/**
	 * @return WebBuilderCategory[];
	 */
	public function getCategories() {
		if (!isset($this->_categories) && $this->id){
			$this->_categories = array();
			$categoryLink = new WebResourceCategory();
			$categoryLink->webResourceId = $this->id;
			$categoryLink->find();
			while($categoryLink->fetch()){
				$category = $categoryLink->getCategory();
				if ($category != false){
					$this->_categories[$categoryLink->categoryId] = $category;
				}
			}
			$sorter = function(WebBuilderCategory $a, WebBuilderCategory $b) {
				return strcasecmp($a->name, $b->name);
			};
			uasort($this->_categories, $sorter);
		}
		return $this->_categories;
	}

	public function saveLibraries(){
		if (isset($this->_libraries) && is_array($this->_libraries)){
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryLink = new LibraryWebResource();

				$libraryLink->webResourceId = $this->id;
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
				$link = new WebResourceAudience();

				$link->webResourceId = $this->id;
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
				$link = new WebResourceCategory();

				$link->webResourceId = $this->id;
				$link->categoryId = $categoryId;
				$link->insert();
			}
			unset($this->_categories);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryLink = new LibraryWebResource();
		$libraryLink->webResourceId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearAudiences()
	{
		//Delete links to the libraries
		$link = new WebResourceAudience();
		$link->webResourceId = $this->id;
		return $link->delete(true);
	}

	private function clearCategories()
	{
		//Delete links to the libraries
		$link = new WebResourceCategory();
		$link->webResourceId = $this->id;
		return $link->delete(true);
	}
}