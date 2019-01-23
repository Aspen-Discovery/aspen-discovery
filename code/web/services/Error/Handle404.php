<?php

/**
 * Handler for 404 errors based on httpd conf file
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 1/22/2016
 * Time: 9:42 AM
 */
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