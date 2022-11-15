<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . "/sys/MaterialsRequest.php";

/**
 * MaterialsRequest Home Page, displays an existing Materials Request.
 */
class MaterialsRequest_NewRequest extends MyAccount
{

	function launch()
	{
		global $interface;
		global $library;

		// Hold Pick-up Locations
		$user = UserAccount::getActiveUserObj();
		$location = new Location();
		$locations = $location->getPickupBranches($user);

		$pickupLocations = array();
		foreach ($locations as $curLocation) {
			if (is_object($curLocation)) {
				$pickupLocations[] = array(
					'id' => $curLocation->locationId,
					'displayName' => $curLocation->displayName,
					'selected' => is_object($curLocation) ? ($curLocation->locationId == $user->pickupLocationId ? 'selected' : '') : '',
				);
			}
		}
		$interface->assign('pickupLocations', $pickupLocations);

		//Get a list of formats to show
		$availableFormats = MaterialsRequest::getFormats(true);
		$interface->assign('availableFormats', $availableFormats);

		//Setup a default title based on the search term
		$interface->assign('new', true);
		$request                         = new MaterialsRequest();
		$request->placeHoldWhenAvailable = true; // set the place hold option on by default
		$request->illItem                = true; // set the place hold option on by default
		if (isset($_REQUEST['lookfor']) && strlen($_REQUEST['lookfor']) > 0) {
			$searchType = isset($_REQUEST['searchIndex']) ? $_REQUEST['searchIndex'] : 'Keyword';
			if (strcasecmp($searchType, 'author') == 0) {
				$request->author = $_REQUEST['lookfor'];
			} else {
				$request->title = $_REQUEST['lookfor'];
			}
		}else{
			$lastSearchId = -1;
			if (isset($_REQUEST['searchId'])){
				$lastSearchId = $_REQUEST['searchId'];
			}else if (isset($_SESSION['searchId'])){
				$lastSearchId = $_SESSION['searchId'];
			}else if (isset($_SESSION['lastSearchId'])){
				$lastSearchId = $_SESSION['lastSearchId'];
			}
			if ($lastSearchId != -1){
				$searchObj = SearchObjectFactory::initSearchObject();
				$searchObj->init();
				$searchObj = $searchObj->restoreSavedSearch($lastSearchId, false, true);
				if ($searchObj != false) {

					$searchTerms = $searchObj->getSearchTerms();
					if (is_array($searchTerms)) {
						if (count($searchTerms) == 1) {
							if (!isset($searchTerms[0]['index'])) {
								$request->title = $searchObj->displayQuery();
							} else if ($searchTerms[0]['index'] == $searchObj->getDefaultIndex()) {
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

		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$request->phone = str_replace(array('### TEXT ONLY ', '### TEXT ONLY'), '', $user->phone);
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
					$pickupLocations[] = array(
						'id' => 'bookmobile',
						'displayName' => $formField->fieldLabel,
						'selected' => false,
					);
					$interface->assign('pickupLocations', $pickupLocations);
					break 2;
				}
			}
		}

		// Get Author Labels for all Formats and Formats that use Special Fields
		list($formatAuthorLabels, $specialFieldFormats) = $request->getAuthorLabelsAndSpecialFields($library->libraryId);

		$interface->assign('formatAuthorLabelsJSON', json_encode($formatAuthorLabels));
		$interface->assign('specialFieldFormatsJSON', json_encode($specialFieldFormats));

		// Set up for User Log in
		$interface->assign('newMaterialsRequestSummary', $library->newMaterialsRequestSummary);

		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('selfRegistrationUrl', $library->selfRegistrationUrl);
		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');

		$this->display('new.tpl', 'Materials Request');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'New Materials Request');
		return $breadcrumbs;
	}
}