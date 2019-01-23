<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 11/17/2017
 * Time: 4:00 PM
 */

require_once ROOT_DIR . '/Drivers/marmot_inc/CombinedResultSection.php';
class LibraryCombinedResultSection extends CombinedResultSection{
	public $__table = 'library_combined_results_section';    // table name
	public $libraryId;

	static function getObjectStructure(){
		$library = new Library();
		$library->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('libraryManager')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['libraryId'] = array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library');

		return $structure;
	}
}