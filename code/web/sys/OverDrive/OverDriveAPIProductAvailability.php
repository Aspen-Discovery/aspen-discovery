<?php

class OverDriveAPIProductAvailability extends DataObject{
	public $__table = 'overdrive_api_product_availability';   // table name

	public $id;
	public $productId;
	public $libraryId;
	public $settingId;
	public $available;
	public $copiesOwned;
	public $copiesAvailable;
	public $numberOfHolds;
	public $shared;

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