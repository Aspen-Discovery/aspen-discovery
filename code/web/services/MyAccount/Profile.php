<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_Profile extends MyAccount
{
	function launch()
	{
		global $configArray;
		global $interface;
		$user = UserAccount::getLoggedInUser();

		$ils = $configArray['Catalog']['ils'];
		$smsEnabled = $configArray['Catalog']['smsEnabled'];
		$interface->assign('showSMSNoticesInProfile', $ils == 'Sierra' && $smsEnabled == true);

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

			/** @var Library $librarySingleton */
			global $librarySingleton;
			// Get Library Settings from the home library of the current user-account being displayed
			$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($patron);
			if ($patronHomeLibrary == null){
				$canUpdateContactInfo = true;
				$canUpdateAddress = true;
				$showWorkPhoneInProfile = false;
				$showNoticeTypeInProfile = true;
				$showPickupLocationInProfile = false;
				$treatPrintNoticesAsPhoneNotices = false;
				$allowPinReset = false;
				$showAlternateLibraryOptionsInProfile = true;
				$allowAccountLinking = true;
			}else{
				$canUpdateContactInfo = ($patronHomeLibrary->allowProfileUpdates == 1);
				$canUpdateAddress = ($patronHomeLibrary->allowPatronAddressUpdates == 1);
				$showWorkPhoneInProfile = ($patronHomeLibrary->showWorkPhoneInProfile == 1);
				$showNoticeTypeInProfile = ($patronHomeLibrary->showNoticeTypeInProfile == 1);
				$treatPrintNoticesAsPhoneNotices = ($patronHomeLibrary->treatPrintNoticesAsPhoneNotices == 1);
				$showPickupLocationInProfile = ($patronHomeLibrary->showPickupLocationInProfile == 1);
				$allowPinReset = ($patronHomeLibrary->allowPinReset == 1);
				$showAlternateLibraryOptionsInProfile = ($patronHomeLibrary->showAlternateLibraryOptionsInProfile == 1);
				$allowAccountLinking = ($patronHomeLibrary->allowLinkedAccounts == 1);
				if (($user->finesVal > $patronHomeLibrary->maxFinesToAllowAccountUpdates) && ($patronHomeLibrary->maxFinesToAllowAccountUpdates > 0)){
					$canUpdateContactInfo = false;
					$canUpdateAddress = false;
				}
			}

			$interface->assign('showUsernameField', $patron->getShowUsernameField());
			$interface->assign('canUpdateContactInfo', $canUpdateContactInfo);
			$interface->assign('canUpdateContactInfo', $canUpdateContactInfo);
			$interface->assign('canUpdateAddress', $canUpdateAddress);
			$interface->assign('showWorkPhoneInProfile', $showWorkPhoneInProfile);
			$interface->assign('showPickupLocationInProfile', $showPickupLocationInProfile);
			$interface->assign('showNoticeTypeInProfile', $showNoticeTypeInProfile);
			$interface->assign('treatPrintNoticesAsPhoneNotices', $treatPrintNoticesAsPhoneNotices);
			$interface->assign('allowPinReset', $allowPinReset);
			$interface->assign('showAlternateLibraryOptions', $showAlternateLibraryOptionsInProfile);
			$interface->assign('allowAccountLinking', $allowAccountLinking);

			// Determine Pickup Locations
			$pickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
			$interface->assign('pickupLocations', $pickupLocations);

			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$updateScope = $_REQUEST['updateScope'];
				if ($updateScope == 'contact') {
					$errors = $patron->updatePatronInfo($canUpdateContactInfo);
					session_start(); // any writes to the session storage also closes session. Happens in updatePatronInfo (for Horizon). plb 4-21-2015
					$_SESSION['profileUpdateErrors'] = $errors;

				}  elseif ($updateScope == 'userPreference') {
					$patron->updateUserPreferences();
				}  elseif ($updateScope == 'staffSettings') {

					$patron->updateUserPreferences(); // update bypass autolog out option

					if (isset($_REQUEST['materialsRequestEmailSignature'])) {
						$patron->setMaterialsRequestEmailSignature($_REQUEST['materialsRequestEmailSignature']);
					}
					if (isset($_REQUEST['materialsRequestReplyToAddress'])) {
						$patron->setMaterialsRequestReplyToAddress($_REQUEST['materialsRequestReplyToAddress']);
					}
						$patron->setStaffSettings();
				} elseif ($updateScope == 'overdrive') {
					// overdrive setting keep changing
					/*	require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
						$overDriveDriver = OverDriveDriverFactory::getDriver();
						$result = $overDriveDriver->updateLendingOptions();
		*/
					$patron->updateOverDriveOptions();
				} elseif ($updateScope == 'hoopla') {
					$patron->updateHooplaOptions();
				} elseif ($updateScope == 'pin') {
					$errors = $patron->updatePin();
					session_start(); // any writes to the session storage also closes session. possibly happens in updatePin. plb 4-21-2015
					$_SESSION['profileUpdateErrors'] = $errors;
					// Template checks for update Pin success message and presents as success even though stored in this errors variable
				}

				session_write_close();
				$actionUrl = $configArray['Site']['path'] . '/MyAccount/Profile' . ( $patronId == $user->id ? '' : '?patronId='.$patronId ); // redirect after form submit completion
				header("Location: " . $actionUrl);
				exit();
			} elseif (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}


			/*require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$overDriveDriver = OverDriveDriverFactory::getDriver();
			if ($overDriveDriver->version >= 2){
				$lendingPeriods = $overDriveDriver->getLendingPeriods($user);
				$interface->assign('overDriveLendingOptions', $lendingPeriods);
			}*/

//			$interface->assign('overDriveUrl', $configArray['OverDrive']['url']);
			global $translator;
			$notice         = $translator->translate('overdrive_account_preferences_notice');
			$replacementUrl = empty($configArray['OverDrive']['url']) ? '#' : $configArray['OverDrive']['url'];
			$notice         = str_replace('{OVERDRIVEURL}', $replacementUrl, $notice); // Insert the Overdrive URL into the notice
			$interface->assign('overdrivePreferencesNotice', $notice);


			if (!empty($_SESSION['profileUpdateErrors'])) {
				$interface->assign('profileUpdateErrors', $_SESSION['profileUpdateErrors']);
				unset($_SESSION['profileUpdateErrors']);
			}

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

			$userIsStaff = $patron->isStaff();
			$interface->assign('userIsStaff', $userIsStaff);

			$interface->assign('profile', $patron); //
			$interface->assign('barcodePin', $patron->getAccountProfile()->loginConfiguration == 'barcode_pin');
				// Switch for displaying the barcode in the account profile
		}

		// switch for hack for Millennium driver profile updating when updating is allowed but address updating is not allowed.
		$millenniumNoAddress = $canUpdateContactInfo && !$canUpdateAddress && in_array($ils, array('Millennium', 'Sierra'));
		$interface->assign('millenniumNoAddress', $millenniumNoAddress);


		// CarlX Specific Options
		if ($ils == 'CarlX' && !$offlineMode) {
			// Get Phone Types
			$phoneTypes = array();
			/** @var CarlX $driver */
			$driver        = CatalogFactory::getCatalogConnectionInstance();
			$rawPhoneTypes = $driver->getPhoneTypeList();
			foreach ($rawPhoneTypes as $rawPhoneTypeSubArray){
				foreach ($rawPhoneTypeSubArray as $phoneType => $phoneTypeLabel) {
					$phoneTypes["$phoneType"] = $phoneTypeLabel;
				}
			}
			$interface->assign('phoneTypes', $phoneTypes);
		}

		$this->display('profile.tpl', 'Account Settings');
	}

}