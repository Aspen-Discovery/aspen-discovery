<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_ContactInformation extends MyAccount {
	function launch() {
		global $configArray;
		global $interface;
		global $offlineMode;
		$user = UserAccount::getLoggedInUser();

		$ils = $configArray['Catalog']['ils'];
		$smsEnabled = $configArray['Catalog']['smsEnabled'];
		$interface->assign('showSMSNoticesInProfile', $ils == 'Sierra' && $smsEnabled == true);

		if ($user) {

			$patronUpdateForm = $user->getPatronUpdateForm();
			if ($patronUpdateForm != null) {
				$interface->assign('patronUpdateForm', $patronUpdateForm);
			} else {
				$user->loadContactInformation();
			}

			global $librarySingleton;
			// Get Library Settings from the home library of the current user-account being displayed
			$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($user);
			if ($patronHomeLibrary == null) {
				$canUpdateContactInfo = false;
				$canUpdateAddress = false;
				$canUpdatePhoneNumber = false;
				$showWorkPhoneInProfile = false;
				$showNoticeTypeInProfile = false;
				$allowPinReset = false;
				$showAlternateLibraryOptionsInProfile = false;
				$allowAccountLinking = true;
				$passwordLabel = 'Library Card Number';
			} else {
				$canUpdateContactInfo = ($patronHomeLibrary->allowProfileUpdates == 1);
				$canUpdateAddress = ($patronHomeLibrary->allowPatronAddressUpdates == 1);
				$canUpdatePhoneNumber = ($patronHomeLibrary->allowPatronPhoneNumberUpdates == 1);
				$showWorkPhoneInProfile = ($patronHomeLibrary->showWorkPhoneInProfile == 1);
				$showNoticeTypeInProfile = ($patronHomeLibrary->showNoticeTypeInProfile == 1);
				$allowPinReset = ($patronHomeLibrary->allowPinReset == 1);
				$showAlternateLibraryOptionsInProfile = ($patronHomeLibrary->showAlternateLibraryOptionsInProfile == 1);
				$allowAccountLinking = ($patronHomeLibrary->allowLinkedAccounts == 1);
				if (($user->_finesVal > $patronHomeLibrary->maxFinesToAllowAccountUpdates) && ($patronHomeLibrary->maxFinesToAllowAccountUpdates > 0)) {
					$canUpdateContactInfo = false;
					$canUpdateAddress = false;
				}
				$passwordLabel = str_replace('Your', '', $patronHomeLibrary->loginFormPasswordLabel ? $patronHomeLibrary->loginFormPasswordLabel : 'Library Card Number');
			}

			$interface->assign('canUpdateContactInfo', $canUpdateContactInfo);
			$interface->assign('canUpdateAddress', $canUpdateAddress);
			$interface->assign('canUpdatePhoneNumber', $canUpdatePhoneNumber);
			$interface->assign('showWorkPhoneInProfile', $showWorkPhoneInProfile);
			$interface->assign('showNoticeTypeInProfile', $showNoticeTypeInProfile);
			$interface->assign('allowPinReset', $allowPinReset);
			$interface->assign('showAlternateLibraryOptions', $showAlternateLibraryOptionsInProfile);
			$interface->assign('allowAccountLinking', $allowAccountLinking);
			$interface->assign('passwordLabel', $passwordLabel);
			$interface->assign('showPreferredNameInProfile', $user->showPreferredNameInProfile());

			// Determine Pickup Locations
			$pickupLocations = $user->getValidPickupBranches($user->getAccountProfile()->recordSource);
			$interface->assign('pickupLocations', $pickupLocations);

			// Save/Update Actions
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$updateScope = $_REQUEST['updateScope'];
				if ($updateScope == 'contact') {
					$result = $user->updatePatronInfo($canUpdateContactInfo, false);
					$user->updateMessage = implode('<br/>', $result['messages']);
					$user->updateMessageIsError = !$result['success'];
					$user->update();
				}

				session_write_close();
				$actionUrl = '/MyAccount/ContactInformation'; // redirect after form submit completion
				header("Location: " . $actionUrl);
				exit();
			} elseif (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			if (!empty($user->updateMessage)) {
				if ($user->updateMessageIsError) {
					$interface->assign('profileUpdateErrors', $user->updateMessage);
				} else {
					$interface->assign('profileUpdateMessage', $user->updateMessage);
				}
				$user->updateMessage = '';
				$user->update();
			}

			$interface->assign('profile', $user);
		} else {
			$canUpdateContactInfo = false;
			$canUpdateAddress = false;
		}

		// switch for hack for Millennium driver profile updating when updating is allowed but address updating is not allowed.
		$millenniumNoAddress = $canUpdateContactInfo && !$canUpdateAddress && in_array($ils, [
				'Millennium',
				'Sierra',
			]);
		$interface->assign('millenniumNoAddress', $millenniumNoAddress);


		// CarlX Specific Options
		if ($ils == 'CarlX' && !$offlineMode) {
			// Get Phone Types
			$phoneTypes = [];
			/** @var CarlX $driver */
			$driver = CatalogFactory::getCatalogConnectionInstance();
			$rawPhoneTypes = $driver->getPhoneTypeList();
			foreach ($rawPhoneTypes as $rawPhoneTypeSubArray) {
				foreach ($rawPhoneTypeSubArray as $phoneType => $phoneTypeLabel) {
					$phoneTypes["$phoneType"] = $phoneTypeLabel;
				}
			}
			$interface->assign('phoneTypes', $phoneTypes);
		}

		$this->display('contactInformation.tpl', 'Contact Information');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Contact Information');
		return $breadcrumbs;
	}
}