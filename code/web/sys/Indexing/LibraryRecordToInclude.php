<?php
/**
 * Rules about which records to include in a scope
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/18/2015
 * Time: 10:31 AM
 */
require_once ROOT_DIR . '/sys/Indexing/RecordToInclude.php';
class LibraryRecordToInclude extends RecordToInclude{
	public $__table = 'library_records_to_include';    // table name
	public $libraryId;

	static function getObjectStructure(){
		$library = new Library();
		$library->orderBy('displayName');
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['libraryId'] = array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library');

		return $structure;
	}
}