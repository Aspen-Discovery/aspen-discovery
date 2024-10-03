<?php

$gitBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
$githubRawUrl = "https://raw.githubusercontent.com/Aspen-Discovery/aspen-discovery/refs/heads/$gitBranch/code/web/openapi/aspen_openapi.json";

require_once ROOT_DIR . '/Action.php';

class API_Documentation extends Action {
	function launch() {
		global $interface;

		$interface->display('API/apiDocumentation.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		return $breadcrumbs;
	}
}
