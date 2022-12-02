<?php

require_once ROOT_DIR . '/Action.php';

class Error_Handle401 extends Action {
	function launch() {
		global $interface;
		$interface->assign('showBreadcrumbs', false);
		http_response_code(401);
		$this->display('401.tpl', 'Unauthorized', false);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}