<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . "/sys/MaterialsRequests/MaterialsRequest.php";

/**
 * MaterialsRequest Home Page, displays an existing Materials Request.
 */
class MaterialsRequest_NewRequest extends MyAccount {

	function launch() : void {
		global $interface;
		global $library;

		// Hold Pick-up Locations
		$user = UserAccount::getActiveUserObj();
		$location = new Location();
		$locations = $location->getPickupBranches($user);

		$pickupLocations = [];
		foreach ($locations as $curLocation) {
			if (is_object($curLocation)) {
				$pickupLocations[] = [
					'id' => $curLocation->locationId,
					'displayName' => $curLocation->displayName,
					'selected' => $curLocation->locationId == $user->pickupLocationId ? 'selected' : '',
				];
			}
		}
		$interface->assign('pickupLocations', $pickupLocations);

		//Get a list of formats to show
		$availableFormats = MaterialsRequest::getFormats(true);
		$interface->assign('availableFormats', $availableFormats);

		//Set up a default title based on the search term
		$interface->assign('new', true);
		$request = new MaterialsRequest();
		$request->placeHoldWhenAvailable = true; // set the place hold option on by default
		$request->illItem = true; // set the place hold option on by default
		if (isset($_REQUEST['isLocalIll'])) {
			$request->source = 2;
		}else{
			$request->source = 1;
		}
		if (isset($_REQUEST['title']) && strlen($_REQUEST['title']) > 0) {
			$request->title = urldecode($_REQUEST['title']);
			if (isset($_REQUEST['author']) && strlen($_REQUEST['author']) > 0) {
				$request->author = urldecode($_REQUEST['author']);
			}
			if (isset($_REQUEST['volume']) && strlen($_REQUEST['volume']) > 0) {
				$request->comments = "Volume " . urldecode($_REQUEST['volume']);
			}
		}else if (isset($_REQUEST['lookfor']) && strlen($_REQUEST['lookfor']) > 0) {
			$searchType = $_REQUEST['searchIndex'] ?? 'Keyword';
			if (strcasecmp($searchType, 'author') == 0) {
				$request->author = $_REQUEST['lookfor'];
			} else {
				$request->title = $_REQUEST['lookfor'];
			}
		} else {
			$lastSearchId = -1;
			if (isset($_REQUEST['searchId'])) {
				$lastSearchId = $_REQUEST['searchId'];
			} elseif (isset($_SESSION['searchId'])) {
				$lastSearchId = $_SESSION['searchId'];
			} elseif (isset($_SESSION['lastSearchId'])) {
				$lastSearchId = $_SESSION['lastSearchId'];
			}
			if ($lastSearchId != -1) {
				$searchObj = SearchObjectFactory::initSearchObject();
				$searchObj->init();
				$searchObj = $searchObj->restoreSavedSearch($lastSearchId, false, true);
				if ($searchObj !== false) {

					$searchTerms = $searchObj->getSearchTerms();
					if (is_array($searchTerms)) {
						if (count($searchTerms) == 1) {
							if (!isset($searchTerms[0]['index'])) {
								$request->title = $searchObj->displayQuery();
							} elseif ($searchTerms[0]['index'] == $searchObj->getDefaultIndex()) {
								$request->title = $searchTerms[0]['lookfor'];
							} else {
								if ($searchTerms[0]['index'] == 'Author') {
									$request->author = $searchTerms[0]['lookfor'];
								} else {
									$request->title = $searchTerms[0]['lookfor'];
								}
							}
						}
					} else {
						$request->title = $searchTerms;
					}
				}
			}
		}
		if( isset($_REQUEST['talpaOther'] )) {
			$request->author = $_REQUEST['author'];
			$request->publicationYear = $_REQUEST['publicationYear'];
		}

		$user = UserAccount::getActiveUserObj();
		$interface->assign('patronIdCheck', $user->id);
		if ($user) {
			$request->phone = str_replace([
				'### TEXT ONLY ',
				'### TEXT ONLY',
			], '', $user->phone);
			if ($user->email != 'notice@salidalibrary.org') {
				$request->email = $user->email;
			}
		}

		$interface->assign('materialsRequest', $request);

		// Get the Fields to Display for the form
		$requestFormFields = $request->getRequestFormFields($library->libraryId);
		$interface->assign('requestFormFields', $requestFormFields);

		// Add bookmobile Stop to the pickup locations if that form field is being used.
		foreach ($requestFormFields as $category) {
			/** @var MaterialsRequestFormFields $formField */
			foreach ($category as $formField) {
				if ($formField->fieldType == 'bookmobileStop') {
					$pickupLocations[] = [
						'id' => 'bookmobile',
						'displayName' => $formField->fieldLabel,
						'selected' => false,
					];
					$interface->assign('pickupLocations', $pickupLocations);
					break 2;
				}
			}
		}

		// Get Author Labels for all Formats and Formats that use Special Fields
		[
			$formatAuthorLabels,
			$specialFieldFormats,
		] = $request->getAuthorLabelsAndSpecialFields($library->libraryId);

		$interface->assign('formatAuthorLabelsJSON', json_encode($formatAuthorLabels));
		$interface->assign('specialFieldFormatsJSON', json_encode($specialFieldFormats));

		// Set up for User Log in
		$interface->assign('newMaterialsRequestSummary', $library->newMaterialsRequestSummary);

		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('selfRegistrationUrl', $library->selfRegistrationUrl);
		$interface->assign('usernameLabel', !empty($library->loginFormUsernameLabel) ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', !empty($library->loginFormPasswordLabel) ? $library->loginFormPasswordLabel : 'Library Card Number');

		$interface->assign('checkRequestsForExistingTitles', $library->checkRequestsForExistingTitles);

		$this->display('new.tpl', 'Materials Request');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'New Materials Request');
		return $breadcrumbs;
	}
}
