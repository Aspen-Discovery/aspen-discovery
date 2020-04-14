<?php

class LibraryLink extends DataObject{
	public $__table = 'library_links';
    public $id;
	public $libraryId;
	public $category;
	public $linkText;
	public $url;
	public $weight;
	public $htmlContents;
	public $showToLoggedInUsersOnly;
	public $showInAccount;
	public $showInHelp;
	public $showExpanded;
	public $openInNewTab;

	static function getObjectStructure(){
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
			'libraryId' => array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'),
			'category' => array('property'=>'category', 'type'=>'text', 'label'=>'Category', 'description'=>'The category of the link', 'size'=>'80', 'maxLength'=>100),
			'linkText' => array('property'=>'linkText', 'type'=>'text', 'label'=>'Link Text', 'description'=>'The text to display for the link ', 'size'=>'80', 'maxLength'=>100),
			'url' => array('property'=>'url', 'type'=>'text', 'label'=>'URL', 'description'=>'The url to link to', 'size'=>'80', 'maxLength'=>255),
			'htmlContents' => array('property'=>'htmlContents', 'type'=>'html', 'label'=>'HTML Contents', 'description'=>'Optional full HTML contents to show rather than showing a basic link within the sidebar.',),
			'showToLoggedInUsersOnly' => array('property'=>'showToLoggedInUsersOnly', 'type'=>'checkbox', 'label'=>'Show to logged in users only', 'description'=>'Show the link only to users that have logged in.',),
			'showInAccount' => array('property'=>'showInAccount', 'type'=>'checkbox', 'label'=>'Show in Account', 'description'=>'Show the link within the Account Menu.',),
			'showInHelp' => array('property'=>'showInHelp', 'type'=>'checkbox', 'label'=>'Show In Help', 'description'=>'Show the link within the Help Menu','default'=>'1'),
			'showExpanded' => array('property'=>'showExpanded', 'type'=>'checkbox', 'label'=>'Show Expanded', 'description'=>'Expand the category by default',),
			'openInNewTab' => ['property' => 'openInNewTab', 'type'=>'checkbox', 'label'=>'Open In New Tab', 'description'=>'Determine whether or not the link should be opened in a new tab', 'default'=>1],
			'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.', 'required'=> true),

		);
		return $structure;
	}

	function getEditLink(){
		return '/Admin/LibraryLinks?objectAction=edit&id=' . $this->id;
	}
}