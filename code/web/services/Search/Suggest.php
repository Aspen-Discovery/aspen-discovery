<?php

require_once ROOT_DIR . '/Action.php';

/**
 * Class Suggest
 *
 * Used as part of Open Search
 */
class Suggest extends Action {

	function launch() {
		global $configArray;

		//header('Content-type: application/x-suggestions+json');
		header('Content-type: application/json');

		// Setup Search Engine Connection

		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1) {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$db = new GroupedWorksSolrConnector($url);
		} else {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$db = new GroupedWorksSolrConnector2($url);
		}

		$results = $db->getSuggestion(strtolower(strip_tags($_GET['lookfor'])), 'title_sort', 10);
		echo json_encode($results);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}