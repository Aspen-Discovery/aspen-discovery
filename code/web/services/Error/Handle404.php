<?php

require_once ROOT_DIR . '/Action.php';
class Error_Handle404 extends Action {
	function launch() {
		global $interface;
		$interface->assign('showBreadcrumbs', false);
		$interface->assign('sidebar', 'Search/home-sidebar.tpl');
		$interface->setTemplate('404.tpl');
		$interface->setPageTitle('Page Not Found');
		$interface->display('layout.tpl');
	}
}