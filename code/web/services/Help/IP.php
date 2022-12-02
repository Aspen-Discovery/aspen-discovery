<?php

require_once ROOT_DIR . '/Action.php';

class Help_IP extends Action {
	function launch() {
		global $interface;

		$ip_address = IPAddress::getActiveIp();
		$interface->assign('ip_address', $ip_address);

		if (IPAddress::showDebuggingInformation()) {
			global $aspenUsage;
			$interface->assign('instanceName', $aspenUsage->instance);
			$interface->assign('validServerNames', getValidServerNames());
		}

		$this->display('ip.tpl', 'IP Address', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'IP Address');
		return $breadcrumbs;
	}
}