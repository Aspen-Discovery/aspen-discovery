<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Suggestions.php';

class MyAccount_Home extends MyAccount {
	function launch() : void {
		global $interface;

		// The script should only execute when a user is logged in, otherwise it calls Login.php
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$homeLibraryCommunityEngagementHighlight = $user->getHomeLibrary()->highlightCommunityEngagement;
			// Check to see if the user has rated any titles
			$interface->assign('hasRatings', $user->hasRatings());
			$interface->assign('highlightCommunityEngagement', $homeLibraryCommunityEngagementHighlight);

			parent::display('home.tpl');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Your Account');
		return $breadcrumbs;
	}
}