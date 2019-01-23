<?php
/**
 * A location defined for a browse category
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/4/14
 * Time: 9:26 PM
 */

class LocationBrowseCategory extends DB_DataObject{
	public $__table = 'browse_category_location';
	public $id;
	public $weight;
	public $browseCategoryTextId;
	public $locationId;

	static function getObjectStructure(){
		//Load Libraries for lookup values
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
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategories = new BrowseCategory();
		$browseCategories->orderBy('label');
		$browseCategories->find();
		$browseCategoryList = array(
			'system_recommended_for_you' =>  translate('Recommended for you'). ' (system_recommended_for_you) [Only displayed when user is logged in]'
		);
		while($browseCategories->fetch()){
			$browseCategoryList[$browseCategories->textId] = $browseCategories->label . " ({$browseCategories->textId})";
		}
		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
				'locationId' => array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'A link to the location which the browse category belongs to'),
				'browseCategoryTextId' => array('property'=>'browseCategoryTextId', 'type'=>'enum', 'values'=>$browseCategoryList, 'label'=>'Browse Category', 'description'=>'The browse category to display '),
				'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how lists are sorted within the widget.  Lower weights are displayed to the left of the screen.', 'required'=> true),

		);
		return $structure;
	}
}