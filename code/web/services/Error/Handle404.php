<?php

require_once ROOT_DIR . '/Action.php';
class Error_Handle404 extends Action {
	function launch() {
		global $interface;
		$interface->assign('showBreadcrumbs', false);
		$this->display('404.tpl', 'Page Not Found');
	}
}