<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
class MyAccount_CheckedOut extends MyAccount{

	const SORT_LAST_ALPHA = 'zzzzz';

	function launch(){
		global $interface;
		global $library;
		$user = UserAccount::getLoggedInUser();

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

		$showAutoRenewSwitch = $user->getShowAutoRenewSwitch();
		$interface->assign('showAutoRenewSwitch', $showAutoRenewSwitch);
		if ($showAutoRenewSwitch){
			$interface->assign('autoRenewalEnabled', $user->isAutoRenewalEnabledForUser());
		}

		$interface->assign('profile', $user);
		$this->display('checkedout.tpl','Checked Out Titles');
	}

}
