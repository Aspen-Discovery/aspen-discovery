<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_MyPreferences extends MyAccount
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

			global $librarySingleton;
			// Get Library Settings from the home library of the current user-account being displayed
			$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($patron);
			if ($patronHomeLibrary == null){
				$showAlternateLibraryOptionsInProfile = true;
			}else{
				$showAlternateLibraryOptionsInProfile = ($patronHomeLibrary->showAlternateLibraryOptionsInProfile == 1);
			}

			$interface->assign('showAlternateLibraryOptions', $showAlternateLibraryOptionsInProfile);

			// Determine Pickup Locations
			$pickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
			$interface->assign('pickupLocations', $pickupLocations);

			if ($patron->hasEditableUsername()){
				$interface->assign('showUsernameField', true);
				$interface->assign('editableUsername', $patron->getEditableUsername());
			}else{
				$interface->assign('showUsernameField', false);
			}

			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$result = $patron->updateUserPreferences();
				if ($result['success'] == false){
					$_SESSION['profileUpdateErrors'] = [];
					$_SESSION['profileUpdateErrors'][] = $result['message'];
				}else{
					$_SESSION['profileUpdateMessage'] = [];
					$_SESSION['profileUpdateMessage'][] = $result['message'];
				}

				session_write_close();
				$actionUrl = '/MyAccount/MyPreferences' . ( $patronId == $user->id ? '' : '?patronId='.$patronId ); // redirect after form submit completion
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
			if (array_key_exists('EBSCO EDS', $enabledModules) && !empty($library->edsSettingsId)){
				$showEdsPreferences = true;
			}
			$interface->assign('showEdsPreferences', $showEdsPreferences);

			if ($showAlternateLibraryOptionsInProfile) {
				//Get the list of locations for display in the user interface.

				$locationList = array();
				$locationList['0'] = "No Alternate Location Selected";
				foreach ($pickupLocations as $pickupLocation){
					if (!is_string($pickupLocation)){
						$locationList[$pickupLocation->locationId] = $pickupLocation->displayName;
					}
				}
				$interface->assign('locationList', $locationList);
			}

			$interface->assign('profile', $patron);

			if (!empty($_SESSION['profileUpdateErrors'])) {
				$interface->assign('profileUpdateErrors', $_SESSION['profileUpdateErrors']);
				@session_start();
				unset($_SESSION['profileUpdateErrors']);
			}
			if (!empty($_SESSION['profileUpdateMessage'])) {
				$interface->assign('profileUpdateMessage', $_SESSION['profileUpdateMessage']);
				@session_start();
				unset($_SESSION['profileUpdateMessage']);
			}
		}

		$this->display('myPreferences.tpl', 'My Preferences');
	}

}