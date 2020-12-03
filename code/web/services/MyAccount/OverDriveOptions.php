<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_OverDriveOptions extends MyAccount
{
	function launch()
	{
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			// Determine which user we are showing/updating settings for
			$linkedUsers = $user->getLinkedUsers();

			$patronId    = isset($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;
			/** @var User $patron */
			$patron      = $user->getUserReferredTo($patronId);

			// Linked Accounts Selection Form set-up
			if (count($linkedUsers) > 0) {
				array_unshift($linkedUsers, $user); // Adds primary account to list for display in account selector
				$interface->assign('linkedUsers', $linkedUsers);
				$interface->assign('selectedUser', $patronId);
			}

			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$patron->updateOverDriveOptions();

				session_write_close();
				$actionUrl = '/MyAccount/OverDriveOptions' . ( $patronId == $user->id ? '' : '?patronId='.$patronId ); // redirect after form submit completion
				header("Location: " . $actionUrl);
				exit();
			} elseif (!$offlineMode) {
				$currentOptions = $patron->getOverDriveOptions();
				$interface->assign('options', $currentOptions);
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			/** @var Translator $translator */
			global $translator;
			$notice         = $translator->translate('overdrive_account_preferences_notice');
            require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
            $overDriveSettings = new OverDriveSetting();
            $overDriveSettings->find((true));
            $overDriveUrl = $overDriveSettings->url;
			$replacementUrl = empty($overDriveUrl) ? '#' : $overDriveUrl;
			$notice         = str_replace('{OVERDRIVEURL}', $replacementUrl, $notice); // Insert the Overdrive URL into the notice
			$interface->assign('overdrivePreferencesNotice', $notice);


			$interface->assign('profile', $patron);
		}

		$this->display('overDriveOptions.tpl', 'Account Settings');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'OverDrive Options');
		return $breadcrumbs;
	}
}