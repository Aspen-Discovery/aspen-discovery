<?php

require_once ROOT_DIR . '/RecordDrivers/CompoundRecordDriver.php';
class AcademicPaperDriver extends BookDriver {

	public function getViewAction() {
		return 'AcademicPaper';
	}


}