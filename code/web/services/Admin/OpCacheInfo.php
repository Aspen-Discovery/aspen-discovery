<?php
/**
 * Created by PhpStorm.
 * User: pbrammeier
 * Date: 10/30/2014
 * Time: 4:23 PM
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Admin_OpCacheInfo extends Admin_Admin {
	function launch() {
		global $interface;

		ob_start();
		include_once 'opcache-admin-include.php';
		$info = ob_get_contents();
		ob_end_clean();

		$interface->assign("info", $info);

		$this->display('adminInfo.tpl', 'Zend OpCache Information');
	}

	function getAllowableRoles() {
		return array('opacAdmin');
	}
}