<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/9/13
 * Time: 3:41 PM
 */

class OverDriveAPIProductAvailability extends DB_DataObject{
	public $__table = 'overdrive_api_product_availability';   // table name

	public $id;
	public $productId;
	public $libraryId;
	public $available;
	public $copiesOwned;
	public $copiesAvailable;
	public $numberOfHolds;

	function getLibraryName(){
		if ($this->libraryId == -1){
			return 'Shared Digital Collection';
		}else{
			$library = new Library();
			$library->libraryId = $this->libraryId;
			$library->find(true);
			return $library->displayName;
		}
	}
} 