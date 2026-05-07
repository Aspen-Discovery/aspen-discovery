<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Bookings extends MyAccount {
	function launch(): void {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		$interface->assign('profile', $user);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Bookings');
		return $breadcrumbs;
	}
}
