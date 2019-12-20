<?php
require_once ROOT_DIR . '/sys/LibraryLocation/FacetSetting.php';

class LocationFacetSetting extends FacetSetting {
	public $__table = 'location_facet_setting';    // table name
	public $locationId;

	static function getObjectStructure($availableFacets = NULL){
		$location = new Location();
		$location->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('libraryManager')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
        $locationList = [];
		while ($location->fetch()){
			$locationList[$location->locationId] = $location->displayName;
		}

		$structure = parent::getObjectStructure();
		$structure['locationId'] = array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The id of a location');

		return $structure;
	}

	function getEditLink(){
		return '/Admin/LocationFacetSettings?objectAction=edit&id=' . $this->id;
	}

	/** @return string[] */
	public static function getAvailableFacets()
	{
		return [];
	}
}