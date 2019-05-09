<?php

require_once ROOT_DIR . '/RecordDrivers/BookDriver.php';
class MagazineDriver extends BookDriver {

	public function getViewAction() {
		return 'Magazine';
	}

	public function getFormat(){
		return 'Magazine';
	}

}