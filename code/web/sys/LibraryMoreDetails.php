<?php
/**
 * Allows configuration of More Details for full record display
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/12/14
 * Time: 8:34 AM
 */

class LibraryMoreDetails extends DB_DataObject{
	public $__table = 'library_more_details';
	public $id;
	public $libraryId;
	public $source;
	public $collapseByDefault;
	public $weight;

	static function getObjectStructure(){
		//Load Libraries for lookup values
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

		require_once ROOT_DIR . '/RecordDrivers/Interface.php';
		$validSources = RecordInterface::getValidMoreDetailsSources();
		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
				'libraryId' => array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'),
				'source' => array('property'=>'source', 'type'=>'enum', 'label'=>'Source', 'values' => $validSources, 'description'=>'The source of the data to display'),
				'collapseByDefault' => array('property'=>'collapseByDefault', 'type'=>'checkbox', 'label'=>'Collapse By Default', 'description'=>'Whether or not the section should be collapsed by default', 'default' => true),
				'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how lists are sorted within the widget.  Lower weights are displayed to the left of the screen.', 'required'=> true),
		);
		return $structure;
	}

	function getEditLink(){
		return '';
	}
}