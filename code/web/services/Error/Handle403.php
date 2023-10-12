<?php

require_once ROOT_DIR . '/Action.php';

class Error_Handle403 extends Action {

	function launch() {
		global $interface;
		$interface->assign('showBreadcrumbs', false);
		http_response_code(403);
		$this->display('403.tpl', 'Forbidden', false);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}