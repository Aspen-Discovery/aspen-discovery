<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/Suggestions.php';
class MyAccount_Home extends MyAccount{
	function launch(){
		global $interface;

		// The script should only execute when a user is logged in, otherwise it calls Login.php
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getLoggedInUser();
			// Check to see if the user has rated any titles
			$interface->assign('hasRatings', $user->hasRatings());

			// Check to see what sidebar sections to display, if any
			$showUserCirculationModules = $interface->getVariable('showUserCirculationModules');
			$showCurbsidePickups = $interface->getVariable('showCurbsidePickups');
			$showFines = $interface->getVariable('showFines');
			$showRatings = $interface->getVariable('showRatings');
			$showFavorites = $interface->getVariable('showFavorites');
			$enableSavedSearches = $interface->getVariable('enableSavedSearches');
			$displayMaterialsRequest = $interface->getVariable('displayMaterialsRequest');
			$enableReadingHistory = $interface->getVariable('enableReadingHistory');
			$allowAccountLinking = $interface->getVariable('allowAccountLinking');
			$showUserPreferences = $interface->getVariable('showUserPreferences');
			$showUserContactInformation = $interface->getVariable('showUserContactInformation');
			$twoFactorEnabled = $interface->getVariable('twoFactorEnabled');
			$allowPinReset = $interface->getVariable('allowPinReset');
			$userIsStaff = $interface->getVariable('userIsStaff');


			$showMyAccount = false;
			if ($showUserCirculationModules || $showCurbsidePickups || $showFines || $showRatings || $showFavorites || $enableSavedSearches || $displayMaterialsRequest || $enableReadingHistory) {
				$showMyAccount = true;
			}

			$showAccountSettings = false;
			if ($allowAccountLinking || $showUserPreferences || $showUserContactInformation || $user->showMessagingSettings() || $twoFactorEnabled || $allowPinReset || $userIsStaff || $showUserCirculationModules) {
				$showAccountSettings = true;
			}

			$interface->assign('showMyAccount', $showMyAccount);
			$interface->assign('showAccountSettings', $showAccountSettings);

			// If neither sidebar sections are show, don't display the sidebar
			if ($showMyAccount || $showAccountSettings) {
				$this->display('home.tpl');
			} else {
				$this->display('home.tpl', '', false);
			}
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Your Account');
		return $breadcrumbs;
	}
}