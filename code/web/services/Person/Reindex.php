<?php

require_once ROOT_DIR . '/sys/Genealogy/Person.php';
require_once ROOT_DIR . '/Action.php';

class Reindex extends Action {
	function launch() {
		global $timer;

		$timer->logTime("Starting to reindex person");
		$recordId = $_REQUEST['id'];
		$quick = isset($_REQUEST['quick']) ? true : false;
		$person = new Person();
		$person->personId = $recordId;
		if ($person->find(true)) {
			$ret = $person->saveToSolr($quick);
			if ($ret) {
				echo(json_encode(["success" => true]));
			} else {
				echo(json_encode([
					"success" => false,
					"error" => "Could not update solr",
				]));
			}
		} else {
			echo(json_encode([
				"success" => false,
				"error" => "Could not find a record with that id",
			]));
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}