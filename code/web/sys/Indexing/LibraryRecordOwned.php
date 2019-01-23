<?php
/**
 * Contains information about which locations and sub locations are owned by a library system
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/18/2015
 * Time: 10:30 AM
 */

require_once ROOT_DIR . '/sys/Indexing/RecordOwned.php';
class LibraryRecordOwned extends RecordOwned{
	public $__table = 'library_records_owned';    // table name
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
		$libraryList = array();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['libraryId'] = array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library');

		return $structure;
	}
}