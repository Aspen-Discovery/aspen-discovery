<?php
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLinkAccess.php';

class LibraryLink extends DataObject{
	public $__table = 'library_links';
    public $id;
	public $libraryId;
	public $category;
	public $iconName;
	public $linkText;
	public $url;
	public $weight;
	public /** @noinspection PhpUnused */ $htmlContents;
	public $showToLoggedInUsersOnly;
	public $showInTopMenu;
	public $alwaysShowIconInTopMenu;
	public $showExpanded;
	public $published;
	public /** @noinspection PhpUnused */ $openInNewTab;

	private $_allowAccess;

	public function getNumericColumnNames() : array
	{
		return ['openInNewTab', 'published', 'showExpanded', 'alwaysShowIconInTopMenu', 'showInTopMenu', 'showToLoggedInUsersOnly', 'weight'];
	}

	static function getObjectStructure() : array {
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$patronTypeList = PType::getPatronTypeList();
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'],
			'libraryId' => ['property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'],
			'category' => ['property'=>'category', 'type'=>'text', 'label'=>'Category', 'description'=>'The category of the link', 'size'=>'80', 'maxLength'=>100],
			'iconName' => ['property'=>'iconName', 'type' => 'text', 'label' => 'FontAwesome Icon Name (https://fontawesome.com/cheatsheet/free/solid)', 'description'=>'Show a font awesome icon next to the menu name'],
			'linkText' => ['property'=>'linkText', 'type'=>'text', 'label'=>'Link Text', 'description'=>'The text to display for the link ', 'size'=>'80', 'maxLength'=>100],
			'url' => ['property'=>'url', 'type'=>'text', 'label'=>'URL', 'description'=>'The url to link to', 'size'=>'80', 'maxLength'=>255],
			'htmlContents' => ['property'=>'htmlContents', 'type'=>'html', 'label'=>'HTML Contents', 'description'=>'Optional full HTML contents to show rather than showing a basic link within the sidebar.',],
			'showInTopMenu' => ['property'=>'showInTopMenu', 'type'=>'checkbox', 'label'=>'Show In Top Menu (large screens only)', 'description'=>'Show the link in the top menu for large screens', 'default'=>0],
			'alwaysShowIconInTopMenu' => ['property'=>'alwaysShowIconInTopMenu', 'type'=>'checkbox', 'label'=>'Show Icon In Top Menu (all screen sizes)', 'description'=>'Always show the icon in the top menu at all screen sizes', 'default'=>0],
			'showExpanded' => ['property'=>'showExpanded', 'type'=>'checkbox', 'label'=>'Show Expanded', 'description'=>'Expand the category by default',],
			'openInNewTab' => ['property' => 'openInNewTab', 'type'=>'checkbox', 'label'=>'Open In New Tab', 'description'=>'Determine whether or not the link should be opened in a new tab', 'default'=>1],
			'published' => ['property' => 'published', 'type' => 'checkbox','label'=>'Published', 'description'=>'The content is published and should be shown to all users','default'=>1],
			'weight' => ['property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.', 'required'=> true],
			'showToLoggedInUsersOnly' => ['property'=>'showToLoggedInUsersOnly', 'type'=>'checkbox', 'label'=>'Show to logged in users only', 'description'=>'Show the link only to users that have logged in.', 'onchange' => 'return AspenDiscovery.Admin.updateLibraryLinksFields();', 'default' => 0],
			'allowAccess' => array(
				'property' => 'allowAccess',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Display only for',
				'description' => 'Define what patron types should see the menu link',
				'values' => $patronTypeList,
			),
		];
	}

	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveAccess();
		}
	}

	public function update(){
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveAccess();
		}
	}

	public function __get($name){
		if ($name == "allowAccess") {
			return $this->getAccess();
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "allowAccess") {
			$this->_allowAccess = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete();
		if ($ret !== FALSE ){
			$this->clearAccess();
		}
	}

	public function getAccess() {
		if (!isset($this->_allowAccess) && $this->id){
			$this->_allowAccess = array();
			$patronTypeLink = new LibraryLinkAccess();
			$patronTypeLink->libraryLinkId = $this->id;
			$patronTypeLink->find();
			while($patronTypeLink->fetch()){
				$this->_allowAccess[$patronTypeLink->patronTypeId] = $patronTypeLink->patronTypeId;
			}
		}
		return $this->_allowAccess;
	}

	public function saveAccess(){
		if (isset($this->_allowAccess) && is_array($this->_allowAccess)){
			$this->clearAccess();

			foreach ($this->_allowAccess as $patronTypeId) {
				$link = new LibraryLinkAccess();

				$link->libraryLinkId = $this->id;
				$link->patronTypeId = $patronTypeId;
				$link->insert();
			}
			unset($this->_allowAccess);
		}
	}

	private function clearAccess()
	{
		//Delete links to the patron types
		$link = new LibraryLinkAccess();
		$link->libraryLinkId = $this->id;
		return $link->delete(true);
	}

	function getEditLink(){
		return '/Admin/LibraryLinks?objectAction=edit&id=' . $this->id;
	}

	function getEscapedCategory(){
		return preg_replace('/\W/', '_', strtolower($this->category));
	}
}