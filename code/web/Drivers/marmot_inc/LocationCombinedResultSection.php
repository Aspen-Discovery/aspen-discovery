<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 11/17/2017
 * Time: 4:01 PM
 */

require_once ROOT_DIR . '/Drivers/marmot_inc/CombinedResultSection.php';
class LocationCombinedResultSection extends CombinedResultSection{
	public $__table = 'location_combined_results_section';    // table name
	public $locationId;

	static function getObjectStructure(){
		$location = new Location();
		$location->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('libraryManager')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		while ($location->fetch()){
			$locationList[$location->locationId] = $location->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['locationId'] = array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location');

		return $structure;
	}
}