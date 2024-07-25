<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);

//Start offline mode
$systemVariables = SystemVariables::getSystemVariables();
if ($systemVariables !== false) {
	if($systemVariables->catalogStatus == 0) {
		//Enter offline mode
		$systemVariables->catalogStatus = 1;
		$systemVariables->update();
	}
}

die();