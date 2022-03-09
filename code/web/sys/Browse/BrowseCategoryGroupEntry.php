<?php

require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';

class BrowseCategoryGroupEntry extends DataObject
{
	public $__table = 'browse_category_group_entry';
	public $id;
	public $weight;
	public $browseCategoryGroupId;
	public $browseCategoryId;

	static function getObjectStructure() : array{
		//Load Groups for lookup values
		$groups = new BrowseCategoryGroup();
		$groups->orderBy('name');
		$groups->find();
		$groupList = array();
		while ($groups->fetch()){
			$groupList[$groups->id] = $groups->name;
		}
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategories = new BrowseCategory();
		$browseCategories->orderBy('label');
		if (!UserAccount::userHasPermission('Administer All Browse Categories')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$libraryId = $library == null ? -1 : $library->libraryId;
			$browseCategories->whereAdd("sharing = 'everyone'");
			$browseCategories->whereAdd("sharing = 'library' AND libraryId = " . $libraryId, 'OR');
			$browseCategories->find();
			$browseCategoryList = [];
			while ($browseCategories->fetch()) {
				$browseCategoryList[$browseCategories->id] = $browseCategories->label . " ({$browseCategories->textId})";
			}
		} else if(UserAccount::userHasPermission('Administer All Browse Categories')) {
			$browseCategories->find();
			$browseCategoryList = [];
			while ($browseCategories->fetch()) {
				$browseCategoryList[$browseCategories->id] = $browseCategories->label . " ({$browseCategories->textId})";
			}
		}
		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
			'browseCategoryGroupId' => array('property'=>'browseCategoryGroupId', 'type'=>'enum', 'values'=>$groupList, 'label'=>'Group', 'description'=>'The group the browse category should be added in'),
			'browseCategoryId' => array('property'=>'browseCategoryId', 'type'=>'enum', 'values'=>$browseCategoryList, 'label'=>'Browse Category', 'description'=>'The browse category to display '),
			'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how lists are sorted within the group.  Lower weights are displayed to the left of the screen.', 'required'=> true),
		];
	}

	function getEditLink(){
		return '/Admin/BrowseCategories?objectAction=edit&id=' . $this->browseCategoryId;
	}

	private $_browseCategory = null;
	function getBrowseCategory(){
		if ($this->_browseCategory == null){
			$this->_browseCategory = new BrowseCategory();
			$this->_browseCategory->id = $this->browseCategoryId;
			if (!$this->_browseCategory->find(true)){
				$this->_browseCategory = false;
			}
		}
		return $this->_browseCategory;
	}

	public function canActiveUserEdit(){
		if ($this->getBrowseCategory()->sharing == 'everyone'){
			return UserAccount::userHasPermission('Administer All Browse Categories');
		}
		//Don't need to limit for the library since the user will need Administer Library Browse Categories to even view them.
		return true;
	}
}