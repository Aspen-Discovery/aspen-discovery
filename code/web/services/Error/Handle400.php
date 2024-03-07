<?php

require_once ROOT_DIR . '/Action.php';

class Error_Handle400 extends Action {
	function launch() {
		global $interface;
		$interface->assign('showBreadcrumbs', false);
		http_response_code(400);
		$this->display('400.tpl', 'Bad request', false);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}