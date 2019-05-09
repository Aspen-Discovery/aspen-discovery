<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class PdfRecordDriver extends IslandoraRecordDriver {

	public function getViewAction() {
		return 'Pdf';
	}

	public function getFormat(){
		return 'Pdf';
	}


}