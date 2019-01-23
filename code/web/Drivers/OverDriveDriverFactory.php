<?php
class OverDriveDriverFactory{
	static function getDriver(){
		global $configArray;
		//Version 1 is No longer supported (pure screen scraping version).
		/*if (!isset($configArray['OverDrive']['interfaceVersion']) || $configArray['OverDrive']['interfaceVersion'] == 1){

			//require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			//$overDriveDriver = new OverDriveDriver();
		}else */if (isset($configArray['OverDrive']['interfaceVersion'])
			&& $configArray['OverDrive']['interfaceVersion'] == 2){
			require_once ROOT_DIR . '/Drivers/OverDriveDriver2.php';
			$overDriveDriver = new OverDriveDriver2();
		}else{
			require_once ROOT_DIR . '/Drivers/OverDriveDriver3.php';
			$overDriveDriver = new OverDriveDriver3();
		}
		return $overDriveDriver;
	}
}