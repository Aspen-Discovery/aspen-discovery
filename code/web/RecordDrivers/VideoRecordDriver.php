<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class VideoRecordDriver extends IslandoraRecordDriver {

	public function getViewAction() {
		return 'Video';
	}

	public function getFormat(){
		$genre = $this->getModsValue('genre', 'mods');
		if ($genre != null){
			return ucwords($genre);
		}
		return 'Video';
	}
}