<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/User/PageDefaults.php';

class MyAccount_MyPreferences extends MyAccount {
	function launch() : void {
		global $interface;
		global $library;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			// Determine which user we are showing/updating settings for
			$linkedUsers = $user->getLinkedUsers();
			$patronId = $_REQUEST['patronId'] ?? $user->id;
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
				$enableCostSavingsForLibrary = false;
				$campaignLeaderboardDisplay = $library->campaignLeaderboardDisplay;
				$offerImmediateHoldFreeze = false;
			} else {
				$canUpdateContactInfo = ($patronHomeLibrary->allowProfileUpdates == 1);
				$showAlternateLibraryOptionsInProfile = ($patronHomeLibrary->showAlternateLibraryOptionsInProfile == 1);
				$allowPickupLocationUpdates = ($patronHomeLibrary->allowPickupLocationUpdates == 1);
				$allowRememberPickupLocation = ($patronHomeLibrary->allowRememberPickupLocation == 1);
				$allowHomeLibraryUpdates = ($patronHomeLibrary->allowHomeLibraryUpdates == 1);
				$enableCostSavingsForLibrary = ($patronHomeLibrary->enableCostSavings == 1);
				$campaignLeaderboardDisplay = $patronHomeLibrary->campaignLeaderboardDisplay;
				$offerImmediateHoldFreeze = ($patronHomeLibrary->offerImmediateHoldFreeze == 1);
			}

			$isAssociatedWithILS = $user->hasIlsConnection();

			$interface->assign('canUpdateContactInfo', $canUpdateContactInfo);
			$interface->assign('showAlternateLibraryOptions', $showAlternateLibraryOptionsInProfile);
			$interface->assign('allowPickupLocationUpdates', $allowPickupLocationUpdates);
			$interface->assign('allowRememberPickupLocation', $allowRememberPickupLocation);
			$interface->assign('allowHomeLibraryUpdates', $allowHomeLibraryUpdates);
			$interface->assign('isAssociatedWithILS', $isAssociatedWithILS);
			$interface->assign('enableCostSavingsForLibrary', $enableCostSavingsForLibrary);
			$interface->assign('campaignLeaderboardDisplay', $campaignLeaderboardDisplay);
			$interface->assign('offerImmediateHoldFreeze', $offerImmediateHoldFreeze);

			// Determine Pickup Locations
			$homeLibraryLocations = $patron->getValidHomeLibraryBranches($patron->getAccountProfile()->recordSource);
			$interface->assign('homeLibraryLocations', $homeLibraryLocations);
			$pickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
			$interface->assign('pickupLocations', $pickupLocations);

			$pickupSublocations = [];
			foreach ($pickupLocations as $location) {
				if (is_object($location)) {
					$pickupSublocations[$location->locationId] = $patron->getValidSublocations($location->locationId);
				}
			}

			$interface->assign('pickupSublocations', $pickupSublocations);

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

			$holdPromptForEditions = false;
			if ($library->holdPromptForEditions > 0) {
				$holdPromptForEditions = true;
			}
			$interface->assign('showHoldPromptForEditions', $holdPromptForEditions);

			$campaign = new Campaign();
			$campaigns = $campaign->getUserEnrolledCampaigns($user->id);
			$usersCampaigns = [];
			foreach ($campaigns as $campaign) {
				$userCampaign = new UserCampaign();
				$userCampaign->campaignId = $campaign->id;
				$userCampaign->userId = $user->id;
				if ($userCampaign->find(true)) {
					$userCampaign->campaignName = $campaign->name;
					$usersCampaigns[] = clone $userCampaign;
				}
			}
			$interface->assign('usersCampaigns', $usersCampaigns);

			require_once(ROOT_DIR . '/Drivers/marmot_inc/SearchSources.php');
			$searchSources = new SearchSources();
			$validSearchSources = $searchSources->getSearchSources();
			$catalogSearchObject = SearchSources::getSearcherForSource('catalog');
			$validCatalogSorts = $catalogSearchObject->getSortOptions();
			$interface->assign('validCatalogSorts', $validCatalogSorts);
			$catalogPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Search', 'Results', null);
			$interface->assign('defaultCatalogSort', $catalogPageDefaults == null ? '' : $catalogPageDefaults->pageSort);

			if (array_key_exists('ebsco_eds', $validSearchSources)){
				$edsSearchObject = SearchSources::getSearcherForSource('ebsco_eds');
				$validEdsSorts = $edsSearchObject->getSortOptions();
				$interface->assign('validEdsSorts', $validEdsSorts);
				$ebscoEDSPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'EBSCO', 'Results', null);
				$interface->assign('defaultEbscoEDSSort', $ebscoEDSPageDefaults == null ? '' : $ebscoEDSPageDefaults->pageSort);
			}

			if (array_key_exists('ebscohost', $validSearchSources)){
				$ebscohostSearchObject = SearchSources::getSearcherForSource('ebscohost');
				$validEbscohostSorts = $ebscohostSearchObject->getSortOptions();
				$interface->assign('validEbscohostSorts', $validEbscohostSorts);
				$ebscoHostPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'EBSCOhost', 'Results', null);
				$interface->assign('defaultEbscoHostSort', $ebscoHostPageDefaults == null ? '' : $ebscoHostPageDefaults->pageSort);
			}

			if (array_key_exists('summon', $validSearchSources)){
				$summonSearchObject = SearchSources::getSearcherForSource('summon');
				$validSummonSorts = $summonSearchObject->getSortOptions();
				$interface->assign('validSummonSorts', $validSummonSorts);
				$summonPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Summon', 'Results', null);
				$interface->assign('defaultSummonSort', $summonPageDefaults == null ? '' : $summonPageDefaults->pageSort);
			}

			if (array_key_exists('gale', $validSearchSources)){
				$galeSearchObject = SearchSources::getSearcherForSource('gale');
				$validGaleSorts = $galeSearchObject->getSortOptions();
				$interface->assign('validGaleSorts', $validGaleSorts);
				$galePageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Gale', 'Results', null);
				$interface->assign('defaultGaleSort', $galePageDefaults == null ? '' : $galePageDefaults->pageSort);
			}

			if (array_key_exists('course_reserves', $validSearchSources)){
				$courseReservesSearchObject = SearchSources::getSearcherForSource('course_reserves');
				$validCourseReservesSorts = $courseReservesSearchObject->getSortOptions();
				$interface->assign('validCourseReservesSorts', $validCourseReservesSorts);
				$courseReservesPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'CourseReserves', 'Results', null);
				$interface->assign('defaultCourseReservesSort', $courseReservesPageDefaults == null ? '' : $courseReservesPageDefaults->pageSort);
			}

			if (array_key_exists('events', $validSearchSources)){
				$eventsSearchObject = SearchSources::getSearcherForSource('events');
				$validEventsSorts = $eventsSearchObject->getSortOptions();
				$interface->assign('validEventsSorts', $validEventsSorts);
				$eventsPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Events', 'Results', null);
				$interface->assign('defaultEventsSort', $eventsPageDefaults == null ? '' : $eventsPageDefaults->pageSort);
			}

			if (array_key_exists('genealogy', $validSearchSources)){
				$genealogySearchObject = SearchSources::getSearcherForSource('genealogy');
				$validGenealogySorts = $genealogySearchObject->getSortOptions();
				$interface->assign('validGenealogySorts', $validGenealogySorts);
				$genealogyPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Genealogy', 'Results', null);
				$interface->assign('defaultGenealogySort', $genealogyPageDefaults == null ? '' : $genealogyPageDefaults->pageSort);
			}

			if (array_key_exists('lists', $validSearchSources)){
				$listsSearchObject = SearchSources::getSearcherForSource('lists');
				$validListsSorts = $listsSearchObject->getSortOptions();
				$interface->assign('validListsSorts', $validListsSorts);
				$listsPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Lists', 'Results', null);
				$interface->assign('defaultListsSort', $listsPageDefaults == null ? '' : $listsPageDefaults->pageSort);
			}

			if (array_key_exists('series', $validSearchSources)){
				$seriesSearchObject = SearchSources::getSearcherForSource('series');
				$validSeriesSorts = $seriesSearchObject->getSortOptions();
				$interface->assign('validSeriesSorts', $validSeriesSorts);
				$seriesPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Series', 'Results', null);
				$interface->assign('defaultSeriesSort', $seriesPageDefaults == null ? '' : $seriesPageDefaults->pageSort);
			}

			if (array_key_exists('open_archives', $validSearchSources)){
				$openArchivesSearchObject = SearchSources::getSearcherForSource('open_archives');
				$validOpenArchivesSorts = $openArchivesSearchObject->getSortOptions();
				$interface->assign('validOpenArchivesSorts', $validOpenArchivesSorts);
				$openArchivesPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'OpenArchives', 'Results', null);
				$interface->assign('defaultOpenArchivesSort', $openArchivesPageDefaults == null ? '' : $openArchivesPageDefaults->pageSort);
			}

			if (array_key_exists('websites', $validSearchSources)){
				$websitesSearchObject = SearchSources::getSearcherForSource('websites');
				$validWebsitesSorts = $websitesSearchObject->getSortOptions();
				$interface->assign('validWebsitesSorts', $validWebsitesSorts);
				$websitesPageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'Websites', 'Results', null);
				$interface->assign('defaultWebsitesSort', $websitesPageDefaults == null ? '' : $websitesPageDefaults->pageSort);
			}

			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$samePatron = true;
				if ($_REQUEST['patronId'] != $user->id) {
					$samePatron = false;
				}
				if ($samePatron) {
					$result = $patron->updateUserPreferences();
					if (isset($result['message'])) {
						$user->updateMessage = $result['message'];
					}
					$user->updateMessageIsError = !$result['success'];

					if ($canUpdateContactInfo && $allowHomeLibraryUpdates) {
						$result2 = $user->updateHomeLibrary($_REQUEST['homeLocation']);
						if (!empty($user->updateMessage)) {
							$user->updateMessage .= '<br/>';
						}
						if (!empty($result2)) { // $result2 may be null, guard clause required
							if (is_array($result2['messages'])) {
								$user->updateMessage .= implode('<br/>', $result2['messages']);
							} else {
								$user->updateMessage .=$result2['messages'];
							}
							$user->updateMessageIsError = $user->updateMessageIsError && !$result2['success'];
						}
					}

					if (!empty($_REQUEST['defaultCatalogSort']) && array_key_exists($_REQUEST['defaultCatalogSort'], $validCatalogSorts)) {
						PageDefaults::updatePageDefaultsForUser($user->id, 'Search', 'Results', null, null, $_REQUEST['defaultCatalogSort']);
					}else{
						PageDefaults::updatePageDefaultsForUser($user->id, 'Search', 'Results', null, null, '');
					}
					if (!empty($validEdsSorts)) {
						if (!empty($_REQUEST['defaultEdsSort']) && array_key_exists($_REQUEST['defaultEdsSort'], $validEdsSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'EBSCO', 'Results', null, null, $_REQUEST['defaultEdsSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'EBSCO', 'Results', null, null, '');
						}
					}
					if (!empty($validEbscoHostSorts)) {
						if (!empty($_REQUEST['defaultEbscohostSort']) && array_key_exists($_REQUEST['defaultEbscohostSort'], $validEbscoHostSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'EBSCOhost', 'Results', null, null, $_REQUEST['defaultEbscohostSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'EBSCOhost', 'Results', null, null, '');
						}
					}
					if (!empty($validSummonSorts)) {
						if (!empty($_REQUEST['defaultSummonSort']) && array_key_exists($_REQUEST['defaultSummonSort'], $validSummonSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Summon', 'Results', null, null, $_REQUEST['defaultSummonSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Summon', 'Results', null, null, '');
						}
					}
					if (!empty($validGaleSorts)) {
						if (!empty($_REQUEST['defaultGaleSort']) && array_key_exists($_REQUEST['defaultGaleSort'], $validGaleSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Gale', 'Results', null, null, $_REQUEST['defaultGaleSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Gale', 'Results', null, null, '');
						}
					}
					if (!empty($validCourseReservesSorts)) {
						if (!empty($_REQUEST['defaultCourseReservesSort']) && array_key_exists($_REQUEST['defaultCourseReservesSort'], $validCourseReservesSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'CourseReserves', 'Results', null, null, $_REQUEST['defaultCourseReservesSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'CourseReserves', 'Results', null, null, '');
						}
					}
					if (!empty($validEventsSorts)) {
						if (!empty($_REQUEST['defaultEventsSort']) && array_key_exists($_REQUEST['defaultEventsSort'], $validEventsSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Events', 'Results', null, null, $_REQUEST['defaultEventsSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Events', 'Results', null, null, '');
						}
					}
					if (!empty($validGenealogySorts)) {
						if (!empty($_REQUEST['defaultGenealogySort']) && array_key_exists($_REQUEST['defaultGenealogySort'], $validGenealogySorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Genealogy', 'Results', null, null, $_REQUEST['defaultGenealogySort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Genealogy', 'Results', null, null, '');
						}
					}
					if (!empty($validOpenArchivesSorts)) {
						if (!empty($_REQUEST['defaultOpenArchivesSort']) && array_key_exists($_REQUEST['defaultOpenArchivesSort'], $validOpenArchivesSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'OpenArchives', 'Results', null, null, $_REQUEST['defaultOpenArchivesSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'OpenArchives', 'Results', null, null, '');
						}
					}
					if (!empty($validListsSorts)) {
						if (!empty($_REQUEST['defaultListsSort']) && array_key_exists($_REQUEST['defaultListsSort'], $validListsSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Lists', 'Results', null, null, $_REQUEST['defaultListsSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Lists', 'Results', null, null, '');
						}
					}
					if (!empty($validSeriesSorts)) {
						if (!empty($_REQUEST['defaultSeriesSort']) && array_key_exists($_REQUEST['defaultSeriesSort'], $validSeriesSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Series', 'Results', null, null, $_REQUEST['defaultSeriesSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Series', 'Results', null, null, '');
						}
					}
					if (!empty($validWebsitesSorts)) {
						if (!empty($_REQUEST['defaultWebsitesSort']) && array_key_exists($_REQUEST['defaultWebsitesSort'], $validWebsitesSorts)) {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Websites', 'Results', null, null, $_REQUEST['defaultWebsitesSort']);
						} else {
							PageDefaults::updatePageDefaultsForUser($user->id, 'Websites', 'Results', null, null, '');
						}
					}
				} else {
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

			$aspenNativeEventsEnabled = false;
			if (array_key_exists('Events', $enabledModules)) {
				require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
				$aspenNativeEventsEnabled = LibraryEventsSetting::libraryHasSource($library->libraryId, 'aspenEvents');
			}
			$interface->assign('aspenNativeEventsEnabled', $aspenNativeEventsEnabled);
		}

		$this->display('myPreferences.tpl', 'Preferences');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Your Preferences');
		return $breadcrumbs;
	}
}