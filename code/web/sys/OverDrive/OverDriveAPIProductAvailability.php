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

	private $_libraryName;
	private $_settingName;

	function getLibraryName(){
		if ($this->libraryId == -1){
			return 'Shared Digital Collection';
		}else{
			if (empty($this->_libraryName)) {
				$library = new Library();
				$library->libraryId = $this->libraryId;
				$library->find(true);
				$this->_libraryName = $library->displayName;
			}
			return $this->_libraryName;
		}
	}

	function getSettingName(){
		if (empty($this->_settingName)) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
			$setting = new OverDriveSetting();
			$setting->id = $this->settingId;
			if ($setting->find(true)) {
				$this->_settingName = (string)$setting;
			}else{
				$this->_settingName = 'Unknown';
			}
		}
		return $this->_settingName;
	}
} 