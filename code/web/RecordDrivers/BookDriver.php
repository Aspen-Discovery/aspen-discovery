<?php

require_once ROOT_DIR . '/RecordDrivers/CompoundRecordDriver.php';
class BookDriver extends CompoundRecordDriver {

	public function getViewAction() {
		return 'Book';
	}

	public function getFormat() {
		$genre = $this->getModsValue('genre', 'mods');
		if ($genre != null && strlen($genre) > 0){
			return ucfirst($genre);
		}
		return "Book";
	}
}