<?php
/**
 * Created by PhpStorm.
 * User: pbrammeier
 * Date: 7/16/2015
 * Time: 2:01 PM
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class MyAccount_Bookings extends MyAccount {

	function launch() {
		global $interface;
		global $library;
		$user = UserAccount::getLoggedInUser();

//		// Define sorting options
//		$sortOptions = array(
//			'title' => 'Title',
//			'author' => 'Author',
//			'format' => 'Format',
//			'placed' => 'Date Placed',
//			'location' => 'Pickup Location',
//			'status' => 'Status',
//		);

		// Get Booked Items
		$bookings = $user->getMyBookings();
		$interface->assign('recordList', $bookings);

		// Additional Template Settings
		if ($library->showLibraryHoursNoticeOnAccountPages) {
			$libraryHoursMessage = Location::getLibraryHoursMessage($user->homeLocationId);
			$interface->assign('libraryHoursMessage', $libraryHoursMessage);
		}

		// Build Page //
		$this->display('bookings.tpl', 'My Scheduled Items');
	}
}