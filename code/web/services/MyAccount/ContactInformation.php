<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_ContactInformation extends MyAccount {
	function launch() {
		global $interface;
		global $offlineMode;
		$user = UserAccount::getLoggedInUser();

		if ($user) {

			$patronUpdateForm = $user->getPatronUpdateForm();
			if ($patronUpdateForm != null) {
				$interface->assign('patronUpdateForm', $patronUpdateForm);
			} else {
				$user->loadContactInformation();
			}

			global $librarySingleton;
			// Get Library Settings from the home library of the current user-account being displayed
			$patronHomeLibrary = $user->getHomeLibrary(true);
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
			$interface->assign('allowUpdatesOfPreferredName', $user->allowUpdatesOfPreferredName());

			// Determine Pickup Locations
			$pickupLocations = $user->getValidPickupBranches($user->getAccountProfile()->recordSource);
			$interface->assign('pickupLocations', $pickupLocations);

			// Save/Update Actions
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$updateScope = $_REQUEST['updateScope'];
				$samePatron = true;
				if ($_REQUEST['patronId'] != $user->id){
					$samePatron = false;
				}
				if ($updateScope == 'contact' && $samePatron) {
					require_once ROOT_DIR . '/sys/Administration/USPS.php';
					$uspsInfo = USPS::getUSPSInfo();

					//if there's no USPS info, don't bother trying to validate
					if ($uspsInfo && $canUpdateAddress) {
						$streetAddress = '';
						$city = '';
						$state = '';
						$zip = '';

						//get the correct _REQUEST names as they differ across ILSes
						foreach ($_REQUEST as $contactInfoValue => $val){
							if (!(preg_match('/(.*?)address2(.*)|(.*?)borrower_B(.*)|(.*?)borrower_alt(.*)/', $contactInfoValue))){
								if (preg_match('/(.*?)address|street(.*)/', $contactInfoValue)){
									$streetAddress = $val;
								}
								elseif (preg_match('/(.*?)city(.*)/', $contactInfoValue)){
									$city = $val;
								}
								elseif (preg_match('/(.*?)state(.*)/', $contactInfoValue)){
									//USPS does not accept anything other than 2 character state codes but will use the ZIP to fill in the blank
									if (strlen($val) == 2){
										$state = $val;
									}
								}
								elseif (preg_match('/(.*?)zip(.*)/', $contactInfoValue)){
									$zip = $val;
								}
							}
						}
						require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
						//Submit form to ILS if address is validated
						if (SystemUtils::validateAddress($streetAddress, $city, $state, $zip)) {
							$result = $user->updatePatronInfo($canUpdateContactInfo, false);
							$user->updateMessage = implode('<br/>', $result['messages']);
							$user->updateMessageIsError = !$result['success'];
							$user->update();
						} else {
							$user->updateMessage = translate([
								'text' => 'The address you entered does not appear to be valid. Please check your address and try again.',
								'isPublicFacing' => true
							]);
							$user->updateMessageIsError = true;
							$user->update();
						}
					} else {
						$result = $user->updatePatronInfo($canUpdateContactInfo, false);
						$user->updateMessage = implode('<br/>', $result['messages']);
						$user->updateMessageIsError = !$result['success'];
						$user->update();
					}
				} else {
					$user->updateMessage = translate([
						'text' => 'Wrong account credentials, please try again.',
						'isPublicFacing' => true,
					]);
					$user->updateMessageIsError = true;
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
				$user->updateMessageIsError = 0;
				$user->update();
			}

			$interface->assign('profile', $user);
		} else {
			$canUpdateContactInfo = false;
			$canUpdateAddress = false;
		}

		// switch for hack for Millennium driver profile updating when updating is allowed but address updating is not allowed.
		$ils = $user->getILSName();
		$millenniumNoAddress = $canUpdateContactInfo && !$canUpdateAddress && in_array($ils, [
				'millennium',
				'mierra',
			]);
		$interface->assign('millenniumNoAddress', $millenniumNoAddress);


		// CarlX Specific Options
		if ($ils == 'carlx' && !$offlineMode) {
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

		$interface->assign('isHorizon', $ils == 'horizon');
		$interface->assign('isCarlX', $ils == 'carlx');

		$this->display('contactInformation.tpl', 'Contact Information');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Contact Information');
		return $breadcrumbs;
	}
}