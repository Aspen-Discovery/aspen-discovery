<?php

require_once ROOT_DIR . '/Action.php';

/**
 * Returns OpenSearch information that allows adding the catalog as a search source
 * within the patron's browser
 */
class OpenSearch extends Action {

	function launch() {
		header('Content-type: text/xml');

		if (isset($_GET['method'])) {
			$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
			if (method_exists($this, $method)) {
				$this->$method();
			} else {
				//echo '<Error>Invalid Method. Use either "describe" or "search"</Error>';
				echo '<Error>Invalid Method. Only "describe" is supported</Error>';
			}
		} else {
			$this->describe();
		}
	}

	function describe() {
		global $interface;
		global $configArray;

		$interface->assign('site', $configArray['Site']);

		$interface->display('Search/opensearch-describe.tpl');
	}

	function getBreadcrumbs(): array {
		return [];
	}
}