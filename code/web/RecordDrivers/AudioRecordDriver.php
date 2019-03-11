<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class AudioRecordDriver extends IslandoraRecordDriver {

	public function getViewAction() {
		return 'Audio';
	}

	public function getFormat(){
		$genre = $this->getModsValue('genre', 'mods');
		if ($genre != null){
			return ucwords($genre);
		}
		return 'Audio File';
	}
}