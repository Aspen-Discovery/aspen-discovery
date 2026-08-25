<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Bookings extends MyAccount {
	function launch(): void {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		$driver = $user->getCatalogDriver();
		if (!$driver || !$driver->hasBookingsSupport()) {
			$interface->assign('accessWarningMessage', 'Booking features cannot be accessed via this Aspen library site.');
			$this->display('bookings.tpl', 'My Bookings');
			return;
		}

		global $library;
		if (!$library->enableBookingDisplay) {
			$interface->assign('accessWarningMessage', 'Booking features cannot be accessed via this Aspen library site.');	
			$this->display('bookings.tpl', 'My Bookings');
			return;
		}

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
