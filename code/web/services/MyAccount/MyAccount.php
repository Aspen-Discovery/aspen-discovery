<?php

require_once ROOT_DIR . '/Action.php';

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';

abstract class MyAccount extends Action
{
	protected $requireLogin = true;

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		if ($this->requireLogin && !UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			$myAccountAction = new MyAccount_Login($isStandalonePage);
			$myAccountAction->launch();
			exit();
		}

		header("Cache-Control: no-cache, no-store, must-revalidate");
		header("Pragma: no-cache");
		header("Expires: 0");

		//Load system messages
		if (UserAccount::isLoggedIn()) {
			$accountMessages = [];
			try {
				$customAccountMessages = new SystemMessage();
				$now = time();
				global $action;
				if ($action == 'CheckedOut') {
					$customAccountMessages->whereAdd("showOn = 1 OR showOn = 2");
				} elseif ($action == 'Holds') {
					$customAccountMessages->whereAdd("showOn = 1 OR showOn = 3");
				} elseif ($action == 'Fines') {
					$customAccountMessages->whereAdd("showOn = 1 OR showOn = 4");
				} elseif ($action == 'ContactInformation') {
					$customAccountMessages->whereAdd("showOn = 1 OR showOn = 5");
				} else {
					$customAccountMessages->showOn = 1;
				}

				$customAccountMessages->whereAdd("startDate = 0 OR startDate <= $now");
				$customAccountMessages->whereAdd("endDate = 0 OR endDate > $now");
				$customAccountMessages->find();
				while ($customAccountMessages->fetch()) {
					if ($customAccountMessages->isValidForDisplay()) {
						$accountMessages[] = clone $customAccountMessages;
					}
				}
			}catch (Exception $e){
				//This happens before the table is created, ignore it.
			}

			global $interface;
			$interface->assign('accountMessages', $accountMessages);

			$ilsMessages = UserAccount::getActiveUserObj()->getILSMessages();
			$interface->assign('ilsMessages', $ilsMessages);

			// check if 2fa is available for user
			$twoFactor = UserAccount::has2FAEnabledForPType();
			$interface->assign('twoFactorEnabled', $twoFactor);

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


			$user = UserAccount::getLoggedInUser();
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
		}
		// Hide Covers when the user has set that setting on an Account Page
		$this->setShowCovers();
	}

	/**
	 * @param string $mainContentTemplate Name of the SMARTY template file for the main content of the Account Page
	 * @param string $pageTitle What to display is the html title tag, gets ran through the translator
	 * @param string|null $sidebar Sets the sidebar on the page to be displayed
	 * @param bool $translateTitle
	 */
	function display($mainContentTemplate, $pageTitle='Your Account', $sidebar='Search/home-sidebar.tpl', $translateTitle = true) {
		global $interface;
		$interface->setPageTitle($pageTitle);

		// If neither sidebar sections are show, don't display the sidebar
		if ($interface->getVariable('showMyAccount') || $interface->getVariable('showAccountSettings')) {
			parent::display($mainContentTemplate, $pageTitle);
		} else {
			parent::display($mainContentTemplate, $pageTitle, false);
		}
	}
}
