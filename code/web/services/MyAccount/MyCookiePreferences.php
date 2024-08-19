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
        $patron->userCookiePreferenceEvents = isset($_POST['userCookieUserEvents']) ? 1 : 0;
        $patron->userCookiePreferenceOpenArchives = isset($_POST['userCookieUserOpenArchives']) ? 1 : 0;
		$patron->userCookiePreferenceWebsite = isset($_POST['userCookieUserWebsite']) ? 1 : 0;
		$patron->userCookiePreferenceExternalSearchServices = isset($_POST['userCookieUserExternalSearchServices']) ? 1 : 0;
		$patron->userCookiePreferenceLocalAnalytics = isset($_POST['userCookieUserLocalAnalytics']) ? 1 : 0;

		if (!$patron->update()) {
			$success = false;
			$message = 'Failed to update cookie preferences.';
		}
		return ['success' => $success, 'message' => $message];
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Your Preferences');
		return $breadcrumbs;
	}
}