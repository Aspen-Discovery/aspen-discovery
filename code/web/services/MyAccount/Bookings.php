<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Bookings extends MyAccount {
	function launch(): void {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		$interface->assign('profile', $user);
		$this->display('bookings.tpl', 'My Bookings');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'My Bookings');
		return $breadcrumbs;
	}
}
