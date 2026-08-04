<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_AppRequests extends MyAccount {
	function launch(): void {
		global $interface;
		$user = UserAccount::getLoggedInUser();
		require_once ROOT_DIR . '/sys/SystemLogging/UserAppRequestLogEntry.php';

		if (isset($_POST['toggleAppRequestLogging'])) {
			$user->allowAppRequestLogging = $user->allowAppRequestLogging ? 0 : 1;
			$user->update();
			if (!$user->allowAppRequestLogging) {
				//Clear previous log
				$logs = new UserAppRequestLogEntry();
				$logs->userId = $user->id;
				$logs->delete(true);
			}
			header('Location: /MyAccount/AppRequests');
			die();
		}

		$logs = new UserAppRequestLogEntry();
		$logs->userId = $user->id;
		$logs->orderBy('time DESC');
		$requestLogs = $logs->fetchAll();
		/** @var UserAppRequestLogEntry $log */
		foreach ($requestLogs as $log) {
			$log->queryString = str_replace('","', ", ", $log->queryString);
		}
		$interface->assign('requestLogs', $requestLogs);
		$interface->assign('user', $user);

		$this->display('AppRequests.tpl', 'API Requests', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'API Requests');
		return $breadcrumbs;
	}
}