<?php
require_once ROOT_DIR . '/sys/LibraryLocation/FacetSetting.php';

class LibraryFacetSetting extends FacetSetting {
	public $__table = 'library_facet_setting';    // table name
	public $libraryId;

	static function getObjectStructure(array $availableFacets = null){
		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = parent::getObjectStructure($availableFacets);
		$structure['libraryId'] = array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library');

		return $structure;
	}

	function getEditLink(){
		return '/Admin/LibraryFacetSettings?objectAction=edit&id=' . $this->id;
	}

	/** @return string[] */
	public static function getAvailableFacets()
	{
		return [];
	}
}