<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_MyCookiePreferences extends MyAccount {
	function launch() {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			//Determine which user we are showing/updating settings for
			$linkedUsers = $user->getLinkedUsers();
			$patronId = isset($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;
			/** @var $patron */
			$patron = $user->getUserReferredTo($patronId);

			//Linked Accounts Selection Form set-up
			if (count($linkedUsers) > 0) {
				array_unshift($linkedUsers, $user);
				$interface->assign('linkedUsers', $linkedUsers);
				$interface->assign('selectedUser', $patronId);
			}

			//Save/update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$samePatron = true;
				if ($_REQUEST['patronId'] != $user->id) {
					$samePatron = false;
				}
				if ($samePatron) {
					$cookieResult = $this->updateUserCookiePreferences($patron);
					if (isset($cookieResult['message'])) {
						$user->updateMessage .= ' ' . $cookieResult['message'];
					}
					$user->updateMessageIsError = $user->updateMessageIsError || $cookieResult['success'];
				} else {
					$user->updateMessage = translate([
						'text' => 'Wrong account credentials, please try again.',
						'isPublicFacing' => true,
					]);
					$user->updateMessageIsError = true;
				}
				$user->update();

				session_write_close();
				$actionUrl = '/MyAccount/MyCookiePreferences' . ($patronId == $user->id ? '': 'patronId=' . $patronId);
				header("Location: " . $actionUrl);
				exit();
			} elseif (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			global $library;
			$interface->assign('cookieConsentEnabled', $library->cookieStorageConsent);

			if (!empty($user->updateMessage)) {
				if ($user->updateMessageIsError) {
					$interface->assign('profileUpdateErrors', $user->updateMessage);
				} else {
					$interface->assign('profileUpdateMessage', $user->updateMessage);
				}
				$user->upadteMessage = '';
				$user->updateMessageIsError = 0;
			}
		}
		$this->display('myCookiePreferences.tpl', 'My Cookie Preferences');
	}

	function updateUserCookiePreferences($patron) {
		$success = true;
		$message = ' ';
		$patron->userCookiePreferenceEssential = 1;
		$patron->userCookiePreferenceAnalytics = isset($_POST['userCookieAnalytics']) ? 1 : 0;
		$patron->userCookiePreferenceLocalAnalytics = isset($_POST['userCookieUserLocalAnalytics']) ? 1 : 0;

		if ($patron->userCookiePreferenceLocalAnalytics == 0) {
			$this->removeLocalAnalyticsTrackingForUser($patron->id);
		}
		if (!$patron->update()) {
			$success = false;
			$message = 'Failed to update cookie preferences.';
		}
		return ['success' => $success, 'message' => $message];
	}

	public function removeLocalAnalyticsTrackingForUser($userId) {
		require_once ROOT_DIR . '/sys/Summon/UserSummonUsage.php';
		require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php';
		require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
		require_once ROOT_DIR . '/sys/Ebsco/UserEbscohostUsage.php';
		require_once ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php';
		require_once ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php';
		require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
		require_once ROOT_DIR . '/sys/PalaceProject/UserPalaceProjectUsage.php';
		require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
		require_once ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php';
		require_once ROOT_DIR . '/sys/Events/UserEventsUsage.php';

		$userId = UserAccount::getActiveUserId();

		if ($userId) {
			$userSummonUsage = new UserSummonUsage();
			$userSummonUsage->userId = $userId;
			$userSummonUsage->delete(true);

			$userAxis360Usage = new UserAxis360Usage();
			$userAxis360Usage->userId = $userId;
			$userAxis360Usage->delete(true);

			$userCloudLibraryUsage = new UserCloudLibraryUsage();
			$userCloudLibraryUsage->userId = $userId;
			$userCloudLibraryUsage->delete(true);

			$userEbscoEdsUsage = new UserEbscoEdsUsage();
			$userEbscoEdsUsage->userId = $userId;
			$userEbscoEdsUsage->delete(true);

			$userEbscoHostUsage = new UserEbscohostUsage();
			$userEbscoHostUsage->userId = $userId;
			$userEbscoEdsUsage->delete(true);

			$userHooplaUsage = new UserHooplaUsage();
			$userHooplaUsage->userId = $userId;
			$userHooplaUsage->delete(true);

			$userOpenArchivesUsage = new UserOpenArchivesUsage();
			$userOpenArchivesUsage->userId = $userId;
			$userOpenArchivesUsage->delete(true);
			
			$userOverDriveUsage = new UserOverDriveUsage();
			$userOverDriveUsage->userId = $userId;
			$userOverDriveUsage->delete(true);

			$userPalaceProjectUsage = new UserPalaceProjectUsage();
			$userPalaceProjectUsage->userId = $userId;
			$userPalaceProjectUsage->delete(true);

			$userSideLoadUsage = new UserSideLoadUsage();
			$userSideLoadUsage->userId = $userId;
			$userSideLoadUsage->delete(true);

			$userWebsiteUsage = new UserWebsiteUsage();
			$userWebsiteUsage->userId = $userId;
			$userWebsiteUsage->delete(true);

			$userEventsUsage = new UserEventsUsage();
			$userEventsUsage->userId = $userId;
			$userEventsUsage->delete(true);
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Your Preferences');
		return $breadcrumbs;
	}
}