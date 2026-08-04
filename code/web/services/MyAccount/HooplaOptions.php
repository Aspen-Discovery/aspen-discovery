<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_HooplaOptions extends MyAccount {
	function launch() : void {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			// Determine which user we are showing/updating settings for
			$linkedUsers = $user->getLinkedUsers();

			$patronId = $_REQUEST['patronId'] ?? $user->id;
			/** @var User $patron */
			$patron = $user->getUserReferredTo($patronId);

			// Linked Accounts Selection Form set-up
			if (count($linkedUsers) > 0) {
				array_unshift($linkedUsers, $user); // Adds primary account to list for display in account selector
				$interface->assign('linkedUsers', $linkedUsers);
				$interface->assign('selectedUser', $patronId);
			}

			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$patron->updateHooplaOptions();
			}
			if (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			$interface->assign('profile', $patron);
		}
		$isFlexAvailable = $user->isValidForEContentSource('hoopla_flex');
		$interface->assign('isFlexAvailable', $isFlexAvailable);

		$this->display('hooplaOptions.tpl', 'Account Settings');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Hoopla Options');
		return $breadcrumbs;
	}
}