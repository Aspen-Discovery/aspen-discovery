<?php
/**
 * Information about what records a location owns
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/18/2015
 * Time: 10:30 AM
 */

class LocationRecordOwned extends RecordOwned{
	public $__table = 'location_records_owned';    // table name
	public $locationId;

	static function getObjectStructure(){
		$location = new Location();
		$location->orderBy('displayName');
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('libraryAdmin')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		$locationList = array();
		while ($location->fetch()){
			$locationList[$location->locationId] = $location->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['locationId'] = array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location');

		return $structure;
	}
}