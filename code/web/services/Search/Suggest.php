<?php

require_once ROOT_DIR . '/Action.php';

/**
 * Class Suggest
 *
 * Used as part of Open Search
 */
class Suggest extends Action {

	function launch()
	{
		global $configArray;

		//header('Content-type: application/x-suggestions+json');
		header('Content-type: application/json');

		// Setup Search Engine Connection
		$db = new GroupedWorksSolrConnector($configArray['Index']['url']);

		$results = $db->getSuggestion(strtolower(strip_tags($_GET['lookfor'])), 'title_sort', 10);
		echo json_encode($results);
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}