<?php

require_once ROOT_DIR . '/Action.php';

class API_Documentation extends Action {
	function launch() {
		global $interface;

		$apiFile = "/openapi/aspen_openapi.json";

		$interface->assign('apiFile', $apiFile);
		$interface->display('API/apiDocumentation.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		return $breadcrumbs;
	}
}
