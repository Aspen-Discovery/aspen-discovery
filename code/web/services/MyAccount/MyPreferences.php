<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_MyPreferences extends MyAccount {
	function launch() {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			// Determine which user we are showing/updating settings for
			$linkedUsers = $user->getLinkedUsers();
			$patronId = isset($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;
			/** @var User $patron */
			$patron = $user->getUserReferredTo($patronId);

			// Linked Accounts Selection Form set-up
			if (count($linkedUsers) > 0) {
				array_unshift($linkedUsers, $user); // Adds primary account to list for display in account selector
				$interface->assign('linkedUsers', $linkedUsers);
				$interface->assign('selectedUser', $patronId);
			}

			global $librarySingleton;
			// Get Library Settings from the home library of the current user-account being displayed
			$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($patron);
			if ($patronHomeLibrary == null) {
				$canUpdateContactInfo = false;
				$showAlternateLibraryOptionsInProfile = true;
				$allowPickupLocationUpdates = true;
				$allowRememberPickupLocation = false;
				$allowHomeLibraryUpdates = false;
			} else {
				$canUpdateContactInfo = ($patronHomeLibrary->allowProfileUpdates == 1);
				$showAlternateLibraryOptionsInProfile = ($patronHomeLibrary->showAlternateLibraryOptionsInProfile == 1);
				$allowPickupLocationUpdates = ($patronHomeLibrary->allowPickupLocationUpdates == 1);
				$allowRememberPickupLocation = ($patronHomeLibrary->allowRememberPickupLocation == 1);
				$allowHomeLibraryUpdates = ($patronHomeLibrary->allowHomeLibraryUpdates == 1);
			}

			$interface->assign('canUpdateContactInfo', $canUpdateContactInfo);
			$interface->assign('showAlternateLibraryOptions', $showAlternateLibraryOptionsInProfile);
			$interface->assign('allowPickupLocationUpdates', $allowPickupLocationUpdates);
			$interface->assign('allowRememberPickupLocation', $allowRememberPickupLocation);
			$interface->assign('allowHomeLibraryUpdates', $allowHomeLibraryUpdates);

			// Determine Pickup Locations
			$homeLibraryLocations = $patron->getValidHomeLibraryBranches($patron->getAccountProfile()->recordSource);
			$interface->assign('homeLibraryLocations', $homeLibraryLocations);
			$pickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
			$interface->assign('pickupLocations', $pickupLocations);

			if ($patron->hasEditableUsername()) {
				$interface->assign('showUsernameField', true);
				$interface->assign('editableUsername', $patron->getEditableUsername());
			} else {
				$interface->assign('showUsernameField', false);
			}

			$showAutoRenewSwitch = $user->getShowAutoRenewSwitch();
			$interface->assign('showAutoRenewSwitch', $showAutoRenewSwitch);
			if ($showAutoRenewSwitch) {
				$interface->assign('autoRenewalEnabled', $user->isAutoRenewalEnabledForUser());
			}

			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$samePatron = true;
				if ($_REQUEST['patronId'] != $user->id){
					$samePatron = false;
				}
				if ($samePatron){
					$result = $patron->updateUserPreferences();
					if (isset($result['message'])) {
						$user->updateMessage = $result['message'];
					}
					$user->updateMessageIsError = !$result['success'];

					$cookieResult = $this->updateUserCookiePreferences($patron);
					if (isset($cookieResult['message'])) {
						$user->updateMessage .= ' ' . $cookieResult['message'];
					}
					$user->updateMessageIsError = $user->updateMessageIsError || $cookieResult['success'];

					// if ($canUpdateContactInfo && $allowHomeLibraryUpdates) {
					// 	$result2 = $user->updateHomeLibrary($_REQUEST['homeLocation']);
					// 	if (!empty($user->updateMessage)) {
					// 		$user->updateMessage .= '<br/>';
					// 	}
					// 	$user->updateMessage .= implode('<br/>', $result2['messages']);
					// 	$user->updateMessageIsError = $user->updateMessageIsError && !$result2['success'];
					// }
				}else{
					$user->updateMessage = translate([
						'text' => 'Wrong account credentials, please try again.',
						'isPublicFacing' => true,
					]);
					$user->updateMessageIsError = true;
				}
				$user->update();

				session_write_close();
				$actionUrl = '/MyAccount/MyPreferences' . ($patronId == $user->id ? '' : '?patronId=' . $patronId); // redirect after form submit completion
				header("Location: " . $actionUrl);
				exit();
			} elseif (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			global $enabledModules;
			global $library;
			$showEdsPreferences = false;
			if (array_key_exists('EBSCO EDS', $enabledModules) && !empty($library->edsSettingsId)) {
				$showEdsPreferences = true;
			}
			$interface->assign('showEdsPreferences', $showEdsPreferences);
			$interface->assign('cookieConsentEnabled', $library->cookieStorageConsent);

			if ($showAlternateLibraryOptionsInProfile) {
				//Get the list of locations for display in the user interface.

				$locationList = [];
				$locationList['0'] = translate([
					'text' => "No Alternate Location Selected",
					'isPublicFacing' => true,
					'inAttribute' => true
				]);
				foreach ($pickupLocations as $pickupLocation) {
					if (!is_string($pickupLocation)) {
						$locationList[$pickupLocation->locationId] = $pickupLocation->displayName;
					}
				}
				$interface->assign('locationList', $locationList);
			}

			$interface->assign('profile', $patron);

			if (!empty($user->updateMessage)) {
				if ($user->updateMessageIsError) {
					$interface->assign('profileUpdateErrors', $user->updateMessage);
				} else {
					$interface->assign('profileUpdateMessage', $user->updateMessage);
				}
				$user->updateMessage = '';
				$user->updateMessageIsError = 0;
				$user->update();
			}
		}

		$this->display('myPreferences.tpl', 'My Preferences');
	}

	function updateUserCookiePreferences($patron) {
		$siccess = true;
		$message = ' ';
		$patron->userCookiePreferenceEssential = 1; // Essential cookies are always enabled
        $patron->userCookiePreferenceAnalytics = isset($_POST['userCookieAnalytics']) ? 1 : 0;
        $patron->userCookiePreferenceAxis360 = isset($_POST['userCookieUserAxis360']) ? 1 : 0;
        $patron->userCookiePreferenceEbscoEds = isset($_POST['userCookieUserEbscoEds']) ? 1 : 0;
        $patron->userCookiePreferenceEbscoHost = isset($_POST['userCookieUserEbscoHost']) ? 1 : 0;
        $patron->userCookiePreferenceSummon = isset($_POST['userCookieUserSummon']) ? 1 : 0;
        $patron->userCookiePreferenceEvents = isset($_POST['userCookieUserEvents']) ? 1 : 0;
        $patron->userCookiePreferenceHoopla = isset($_POST['userCookieUserHoopla']) ? 1 : 0;
        $patron->userCookiePreferenceOpenArchives = isset($_POST['userCookieUserOpenArchives']) ? 1 : 0;
        $patron->userCookiePreferenceOverdrive = isset($_POST['userCookieUserOverdrive']) ? 1 : 0;
        $patron->userCookiePreferencePalaceProject = isset($_POST['userCookieUserPalaceProject']) ? 1 : 0;
        $patron->userCookiePreferenceSideLoad = isset($_POST['userCookieUserSideLoad']) ? 1 : 0;
		$patron->userCookiePreferenceCloudLibrary = isset($_POST['userCookieUserCloudLibrary']) ? 1 : 0;
		$patron->userCookiePreferenceWebsite = isset($_POST['userCookieUserWebsite']) ? 1 : 0;

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