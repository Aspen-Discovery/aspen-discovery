<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class MyAccount_Holds extends MyAccount{
	function launch()
	{
		global $interface;
		global $library;
		$user = UserAccount::getActiveUserObj();

		if (isset($_REQUEST['tab'])){
			$tab = $_REQUEST['tab'];
		}else{
			$tab = 'all';
		}
		$interface->assign('tab', $tab);

		if ($library->showLibraryHoursNoticeOnAccountPages) {
			$libraryHoursMessage = Location::getLibraryHoursMessage($user->homeLocationId);
			$interface->assign('libraryHoursMessage', $libraryHoursMessage);
		}

		// Present to the user
		$this->display('holds.tpl', 'My Holds');
	}
}