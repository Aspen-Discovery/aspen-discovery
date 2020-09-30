<?php
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

	public function getNumericColumnNames()
	{
		return ['openInNewTab', 'published', 'showExpanded', 'alwaysShowIconInTopMenu', 'showInTopMenu', 'showToLoggedInUsersOnly', 'weight'];
	}

	static function getObjectStructure(){
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
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'],
			'libraryId' => ['property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'],
			'category' => ['property'=>'category', 'type'=>'text', 'label'=>'Category', 'description'=>'The category of the link', 'size'=>'80', 'maxLength'=>100],
			'iconName' => ['property'=>'iconName', 'type' => 'text', 'label' => 'FontAwesome Icon Name (https://fontawesome.com/cheatsheet/free/solid)', 'description'=>'Show a font awesome icon next to the menu name'],
			'linkText' => ['property'=>'linkText', 'type'=>'text', 'label'=>'Link Text', 'description'=>'The text to display for the link ', 'size'=>'80', 'maxLength'=>100],
			'url' => ['property'=>'url', 'type'=>'text', 'label'=>'URL', 'description'=>'The url to link to', 'size'=>'80', 'maxLength'=>255],
			'htmlContents' => ['property'=>'htmlContents', 'type'=>'html', 'label'=>'HTML Contents', 'description'=>'Optional full HTML contents to show rather than showing a basic link within the sidebar.',],
			'showToLoggedInUsersOnly' => ['property'=>'showToLoggedInUsersOnly', 'type'=>'checkbox', 'label'=>'Show to logged in users only', 'description'=>'Show the link only to users that have logged in.',],
			'showInTopMenu' => ['property'=>'showInTopMenu', 'type'=>'checkbox', 'label'=>'Show In Top Menu (large screens only)', 'description'=>'Show the link in the top menu for large screens', 'default'=>0],
			'alwaysShowIconInTopMenu' => ['property'=>'alwaysShowIconInTopMenu', 'type'=>'checkbox', 'label'=>'Always Show Icon In Top Menu', 'description'=>'Always show the icon in the top menu at all screen sizes', 'default'=>0],
			'showExpanded' => ['property'=>'showExpanded', 'type'=>'checkbox', 'label'=>'Show Expanded', 'description'=>'Expand the category by default',],
			'openInNewTab' => ['property' => 'openInNewTab', 'type'=>'checkbox', 'label'=>'Open In New Tab', 'description'=>'Determine whether or not the link should be opened in a new tab', 'default'=>1],
			'published' => ['property' => 'published', 'type' => 'checkbox','label'=>'Published', 'description'=>'The content is published and should be shown to all users','default'=>1],
			'weight' => ['property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.', 'required'=> true],

		];
	}

	function getEditLink(){
		return '/Admin/LibraryLinks?objectAction=edit&id=' . $this->id;
	}

	function getEscapedCategory(){
		return preg_replace('/\W/', '_', strtolower($this->category));
	}
}