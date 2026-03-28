<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_AppRequests extends MyAccount {
	function launch(): void {
		global $interface;
		$user = UserAccount::getLoggedInUser();
		require_once ROOT_DIR . '/sys/SystemLogging/UserAppRequestLogEntry.php';
		$logs = new UserAppRequestLogEntry();
		$logs->userId = $user->id;
		$logs->orderBy('time DESC');
		$requestLogs = $logs->fetchAll();
		$interface->assign('requestLogs', $requestLogs);
		$interface->assign('user', $user);

		if (isset($_POST['allowAppRequestLogging'])) {
			$user->allowAppRequestLogging = $user->allowAppRequestLogging ? 0 : 1;
			$user->update();
		}

		$this->display('AppRequests.tpl', 'API Requests', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'API Requests');
		return $breadcrumbs;
	}
}