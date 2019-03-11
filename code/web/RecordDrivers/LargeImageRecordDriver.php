<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class LargeImageRecordDriver extends IslandoraRecordDriver {

	public function getViewAction() {
		return 'LargeImage';
	}

	public function getFormat(){
		return 'Image';
	}
}