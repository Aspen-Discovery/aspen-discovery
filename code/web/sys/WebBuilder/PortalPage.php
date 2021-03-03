<?php
require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
require_once ROOT_DIR . '/sys/WebBuilder/LibraryPortalPage.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalPageAudience.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalPageCategory.php';

class PortalPage extends DataObject
{
	public $__table = 'web_builder_portal_page';
	public $id;
	public $title;
	public $urlAlias;
	public $lastUpdate;

	private $_rows;

	private $_libraries;
	private $_audiences;
	private $_categories;

	static function getObjectStructure() {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Custom Pages'));
		$audiencesList = WebBuilderAudience::getAudiences();
		$categoriesList = WebBuilderCategory::getCategories();

		$portalRowStructure = PortalRow::getObjectStructure();
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'title' => array('property' => 'title', 'type' => 'text', 'label' => 'Title', 'description' => 'The title of the page', 'size' => '40', 'maxLength'=>100),
			'urlAlias' => array('property' => 'urlAlias', 'type' => 'text', 'label' => 'URL Alias (no domain, should start with /)', 'description' => 'The url of the page (no domain name)', 'size' => '40', 'maxLength'=>100),

			'rows' => [
				'property'=>'rows',
				'type'=>'portalRow',
				'label'=>'Rows',
				'description'=>'Rows to show on the page',
				'keyThis' => 'id',
				'keyOther' => 'portalPageId',
				'subObjectType' => 'PortalRow',
				'structure' => $portalRowStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'hideInLists' => true
			],

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

	public function __get($name)
	{
		if ($name == "libraries") {
			return $this->getLibraries();
		}elseif ($name == "audiences") {
			return $this->getAudiences();
		}elseif ($name == "categories") {
			return $this->getCategories();
		}elseif ($name == 'rows') {
			return $this->getRows();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "audiences") {
			$this->_audiences = $value;
		}elseif ($name == "categories") {
			$this->_categories = $value;
		}elseif ($name == 'rows') {
			$this->_rows = $value;
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
		//Updates to properly update settings based on the ILS
		$this->lastUpdate = time();
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveRows();
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
		}

		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$this->lastUpdate = time();
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveRows();
			$this->saveLibraries();
			$this->saveAudiences();
			$this->saveCategories();
		}
		return $ret;
	}

	public function saveRows(){
		if (isset ($this->_rows) && is_array($this->_rows)){
			$this->saveOneToManyOptions($this->_rows, 'portalPageId');
			unset($this->_rows);
		}
	}

	/** @return PortalRow[] */
	public function getRows()
	{
		if (!isset($this->_rows) && $this->id){
			$this->_rows = [];
			$obj = new PortalRow();
			$obj->portalPageId = $this->id;
			$obj->orderBy('weight ASC');
			$obj->find();
			while($obj->fetch()){
				$this->_rows[$obj->id] = clone $obj;
			}
		}
		return $this->_rows;
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
			$libraryLink = new LibraryPortalPage();
			$libraryLink->portalPageId = $this->id;
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
			$audienceLink = new PortalPageAudience();
			$audienceLink->portalPageId = $this->id;
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
			$categoryLink = new PortalPageCategory();
			$categoryLink->portalPageId = $this->id;
			$categoryLink->find();
			while($categoryLink->fetch()){
				$this->_categories[$categoryLink->categoryId] = $categoryLink->categoryId;
			}
		}
		return $this->_categories;
	}

	public function saveLibraries(){
		if (isset($this->_libraries) && is_array($this->_libraries)){
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryLink = new LibraryPortalPage();

				$libraryLink->portalPageId = $this->id;
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
				$link = new PortalPageAudience();

				$link->portalPageId = $this->id;
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
				$link = new PortalPageCategory();

				$link->portalPageId = $this->id;
				$link->categoryId = $categoryId;
				$link->insert();
			}
			unset($this->_categories);
		}
	}

	private function clearLibraries()
	{
		//Delete links to the libraries
		$libraryLink = new LibraryPortalPage();
		$libraryLink->portalPageId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearAudiences()
	{
		//Delete links to the libraries
		$link = new PortalPageAudience();
		$link->portalPageId = $this->id;
		return $link->delete(true);
	}

	private function clearCategories()
	{
		//Delete links to the libraries
		$link = new PortalPageCategory();
		$link->portalPageId = $this->id;
		return $link->delete(true);
	}
}