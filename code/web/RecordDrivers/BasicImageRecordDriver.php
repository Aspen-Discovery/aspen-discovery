<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class BasicImageRecordDriver extends IslandoraRecordDriver {


	public function getViewAction() {
		return 'Image';
	}

	public function getFormat(){
		return 'Image';
	}
}