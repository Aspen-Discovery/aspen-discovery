<?php

require_once ROOT_DIR . '/RecordDrivers/IslandoraRecordDriver.php';
class NewsPaperRecordDriver extends IslandoraRecordDriver {


	public function getViewAction() {
		return 'Newspaper';
	}
}