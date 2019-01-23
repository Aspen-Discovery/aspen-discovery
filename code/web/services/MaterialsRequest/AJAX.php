<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 *
 */

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/sys/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';

/**
 * MaterialsRequest AJAX Page, handles returing asynchronous information about Materials Requests.
 */
class MaterialsRequest_AJAX extends Action{

	function AJAX() {
	}

	function launch(){
		$method = $_GET['method'];
		if (in_array($method, array('CancelRequest', 'GetWorldCatTitles', 'GetWorldCatIdentifiers', 'MaterialsRequestDetails', 'UpdateMaterialsRequest'))){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$result = $this->$method();
			echo json_encode($result);
		}else{
			echo "Unknown Method";
		}
	}

	function CancelRequest(){
		if (!UserAccount::isLoggedIn()){
			return array('success' => false, 'error' => 'Could not cancel the request, you must be logged in to cancel the request.');
		}elseif (!isset($_REQUEST['id'])){
			return array('success' => false, 'error' => 'Could not cancel the request, no id provided.');
		}else{
			$id = $_REQUEST['id'];
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
			$materialsRequest->createdBy = UserAccount::getActiveUserId();
			if ($materialsRequest->find(true)){
				//get the correct status to set based on the user's home library
				$homeLibrary = Library::getPatronHomeLibrary();
				$cancelledStatus = new MaterialsRequestStatus();
				$cancelledStatus->isPatronCancel = 1;
				$cancelledStatus->libraryId = $homeLibrary->libraryId;
				$cancelledStatus->find(true);

				$materialsRequest->dateUpdated = time();
				$materialsRequest->status = $cancelledStatus->id;
				if ($materialsRequest->update()){
					return array('success' => true);
				}else{
					return array('success' => false, 'error' => 'Could not cancel the request, error during update.');
				}
			}else{
				return array('success' => false, 'error' => 'Could not cancel the request, could not find a request for the provided id.');
			}
		}
	}

	function UpdateMaterialsRequest(){
		global $interface;
		global $configArray;

		$useWorldCat = false;
		if (isset($configArray['WorldCat']) && isset($configArray['WorldCat']['apiKey'])){
			$useWorldCat = strlen($configArray['WorldCat']['apiKey']) > 0;
		}
		$interface->assign('useWorldCat', $useWorldCat);

		if (!isset($_REQUEST['id'])){
			$interface->assign('error', 'Please provide an id of the '. translate('materials request') .' to view.');
		}else {
			$id = $_REQUEST['id'];
			if (ctype_digit($id)) {
				if (UserAccount::isLoggedIn()) {
					$user = UserAccount::getLoggedInUser();
					$staffLibrary = $user->getHomeLibrary(); // staff member's home library

					if (!empty($staffLibrary)) {

						// Material Request
						$materialsRequest     = new MaterialsRequest();
						$materialsRequest->id = $id;

						// Statuses
						$statusQuery           = new MaterialsRequestStatus();
						$materialsRequest->joinAdd($statusQuery);

						// Pick-up Locations
						$locationQuery = new Location();
						$materialsRequest->joinAdd($locationQuery, "LEFT");

						// Format Labels
						$formats = new MaterialsRequestFormats();
						$formats->libraryId = $staffLibrary->libraryId;
						$usingDefaultFormats = $formats->count() == 0;

						$materialsRequest->selectAdd();
						$materialsRequest->selectAdd(
							'materials_request.*, description as statusLabel, location.displayName as location'
						);
						if (!$usingDefaultFormats) {
							$materialsRequest->joinAdd($formats, 'LEFT');
							$materialsRequest->selectAdd('materials_request_formats.formatLabel,materials_request_formats.authorLabel, materials_request_formats.specialFields');
						}

						if ($materialsRequest->find(true)) {
							$canUpdate   = false;
							$isAdminUser = false;

							//Load user information
							$requestUser     = new User();
							$requestUser->id = $materialsRequest->createdBy;
							if ($requestUser->find(true)) {
								$interface->assign('requestUser', $requestUser);

								// Get the Fields to Display for the form
								$requestFormFields = $materialsRequest->getRequestFormFields($staffLibrary->libraryId, true);
								$interface->assign('requestFormFields', $requestFormFields);

								if ($user->id == $materialsRequest->createdBy) {
									$canUpdate = true;
									$isAdminUser = UserAccount::userHasRole('library_material_requests');
								} elseif (UserAccount::userHasRole('library_material_requests')) {
									//User can update if the home library of the requester is their library

									$requestUserLibrary = $requestUser->getHomeLibrary();
									$canUpdate          = $requestUserLibrary->libraryId == $staffLibrary->libraryId;
									$isAdminUser        = true;
								}
								if ($canUpdate) {
									$interface->assign('isAdminUser', $isAdminUser);
									//Get a list of formats to show
									$availableFormats = MaterialsRequest::getFormats();
									$interface->assign('availableFormats', $availableFormats);

									// Get Author Labels for all Formats
									list($formatAuthorLabels, $specialFieldFormats) = $materialsRequest->getAuthorLabelsAndSpecialFields($staffLibrary->libraryId);
									if ($usingDefaultFormats) {
										$defaultFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats();
										/** @var MaterialsRequestFormats $format */
										foreach ($defaultFormats as $format) {
											// Get the default values for this request
											if ($materialsRequest->format == $format->format ){
												$materialsRequest->formatLabel = $format->formatLabel;
												$materialsRequest->authorLabel = $format->authorLabel;
												$materialsRequest->specialFields = $format->specialFields;
												break;
											}
										}
									}

									$interface->assign('formatAuthorLabelsJSON', json_encode($formatAuthorLabels));
									$interface->assign('specialFieldFormatsJSON', json_encode($specialFieldFormats));

									$interface->assign('showEbookFormatField', $configArray['MaterialsRequest']['showEbookFormatField']);
//									$interface->assign('showEaudioFormatField', $configArray['MaterialsRequest']['showEaudioFormatField']);
									$interface->assign('requireAboutField', $configArray['MaterialsRequest']['requireAboutField']);

									$interface->assign('materialsRequest', $materialsRequest);
									$interface->assign('showUserInformation', true);

									// Hold Pick-up Locations
									$location = new Location();
									$locationList = $location->getPickupBranches($requestUser, $materialsRequest->holdPickupLocation);
									$pickupLocations = array();
									foreach ($locationList as $curLocation) {
										$pickupLocations[] = array(
											'id' => $curLocation->locationId,
											'displayName' => $curLocation->displayName,
											'selected' => $curLocation->selected,
										);
									}

									// Add bookmobile Stop to the pickup locations if that form field is being used.
									foreach ($requestFormFields as $catagory) {
										/** @var MaterialsRequestFormFields $formField */
										foreach ($catagory as $formField) {
											if ($formField->fieldType == 'bookmobileStop') {
												$pickupLocations[] = array(
													'id' => 'bookmobile',
													'displayName' => $formField->fieldLabel,
													'selected' => $materialsRequest->holdPickupLocation == 'bookmobile',
												);
												break 2;
											}
										}
									}

									$interface->assign('pickupLocations', $pickupLocations);

									// Get Statuses
									$materialsRequestStatus = new MaterialsRequestStatus();
									$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
									$materialsRequestStatus->libraryId = $staffLibrary->libraryId;
									$availableStatuses = $materialsRequestStatus->fetchAll('id', 'description');
									$interface->assign('availableStatuses', $availableStatuses);

									// Get Barcode Column
									$barCodeColumn = null;
									if ($accountProfile = $user->getAccountProfile()) {
										$barCodeColumn = $accountProfile->loginConfiguration == 'name_barcode' ? 'cat_password' : 'cat_username';
									}
									$interface->assign('barCodeColumn', $barCodeColumn);

								} else {
									$interface->assign('error', 'Sorry, you don\'t have permission to update this '. translate('materials request') .'.');
								}
							} else {
								$interface->assign('error', 'Sorry, we couldn\'t find the user that made this '. translate('materials request') .'.');
							}
						} else {
							$interface->assign('error', 'Sorry, we couldn\'t find a '. translate('materials request') .' for that id.');
						}
					} else {
						$interface->assign('error', 'We could not determine your home library.');
					}
				} else {
					$interface->assign('error', 'Please log in to view & edit the '. translate('materials request') .'.');
				}
			} else {
				$interface->assign('error', 'Sorry, invalid id for a '. translate('materials request') .'.');
			}
		}
		$return = array(
			'title' => 'Update Materials Request',
			'modalBody' => $interface->fetch('MaterialsRequest/ajax-update-request.tpl'),
			'modalButtons' => $interface->get_template_vars('error') == null ?  "<button class='btn btn-primary' onclick='$(\"#materialsRequestUpdateForm\").submit();'>Update Request</button>" : ''
		);
		return $return;
	}

	function MaterialsRequestDetails(){
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if (!isset($_REQUEST['id'])) {
			$interface->assign('error', 'Please provide an id of the '. translate('materials request') .' to view.');
		}elseif (empty($user)) {
			$interface->assign('error', 'Please log in to view details.');
		}else {
			$id = $_REQUEST['id'];
			if (!empty($id) && ctype_digit($id)) {
				$requestLibrary = $user->getHomeLibrary(); // staff member's or patron's home library
				if (!empty($requestLibrary)) {
					$materialsRequest = new MaterialsRequest();
					$materialsRequest->id  = $id;

					$staffView = isset($_REQUEST['staffView']) ? $_REQUEST['staffView'] : true;
					$requestFormFields = $materialsRequest->getRequestFormFields($requestLibrary->libraryId, $staffView);
					$interface->assign('requestFormFields', $requestFormFields);


					// Statuses
					$statusQuery           = new MaterialsRequestStatus();
					$materialsRequest->joinAdd($statusQuery);

					// Pick-up Locations
					$locationQuery = new Location();
					$materialsRequest->joinAdd($locationQuery, "LEFT");

					// Format Labels
					$formats = new MaterialsRequestFormats();
					$formats->libraryId = $requestLibrary->libraryId;
					$usingDefaultFormats = $formats->count() == 0;

					$materialsRequest->selectAdd();
					$materialsRequest->selectAdd(
						'materials_request.*, description as statusLabel, location.displayName as location'
					);
					if (!$usingDefaultFormats) {
						$materialsRequest->joinAdd($formats, 'LEFT');
						$materialsRequest->selectAdd('materials_request_formats.formatLabel,materials_request_formats.authorLabel, materials_request_formats.specialFields');
					}

					if ($materialsRequest->find(true)) {
						if ($usingDefaultFormats) {
							$defaultFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats();
							/** @var MaterialsRequestFormats $format */
							foreach ($defaultFormats as $format) {
								if ($materialsRequest->format == $format->format ){
									$materialsRequest->formatLabel = $format->formatLabel;
									$materialsRequest->authorLabel = $format->authorLabel;
									$materialsRequest->specialFields = $format->specialFields;
									break;
								}
							}
						}

						$interface->assign('materialsRequest', $materialsRequest);

						if ($user && UserAccount::userHasRole('library_material_requests')) {
							$interface->assign('showUserInformation', true);
							//Load user information
							$requestUser     = new User();
							$requestUser->id = $materialsRequest->createdBy;
							if ($requestUser->find(true)) {
								$interface->assign('requestUser', $requestUser);

								// Get Barcode Column
								$barCodeColumn = null;
								if ($accountProfile = $requestUser->getAccountProfile()) {
									$barCodeColumn = $accountProfile->loginConfiguration == 'name_barcode' ? 'cat_password' : 'cat_username';
								}
								$interface->assign('barCodeColumn', $barCodeColumn);

							}
						} else {
							$interface->assign('showUserInformation', false);
						}
					} else {
						$interface->assign('error', 'Sorry, we couldn\'t find a '. translate('materials request') .' for that id.');
					}
				} else {
					$interface->assign('error', 'Could not determine your home library.');
				}
			} else {
				$interface->assign('error', 'Invalid Request ID.');
			}
		}
		$return = array(
				'title'        => translate('Materials_Request_alt') . ' Details',
				'modalBody'    => $interface->fetch('MaterialsRequest/ajax-request-details.tpl'),
				'modalButtons' => '' //TODO idea: add Update Request button (for staff only?)
		);
		return $return;
	}

	function GetWorldCatIdentifiers(){
		$worldCatTitles = $this->GetWorldCatTitles();
		if ($worldCatTitles['success'] == false){
			return $worldCatTitles;
		}else{
			$suggestedIdentifiers = array();
			foreach ($worldCatTitles['titles'] as $title){
				$identifier = null;
				if (isset($title['ISBN'])){
					//Get the first 13 digit ISBN if available
					foreach ($title['ISBN'] as $isbn){
						$identifier = $isbn;
						if (strlen($isbn) == 13){
							break;
						}
					}
					$title['isbn'] = $identifier;
				}elseif (isset($title['oclcNumber'])){
					$identifier = $title['oclcNumber'];
				}
				if (!is_null($identifier) && !array_key_exists($identifier, $suggestedIdentifiers)){
					$suggestedIdentifiers[$identifier] = $title;
				}
			}
		}
		global $interface;
		$interface->assign('suggestedIdentifiers', $suggestedIdentifiers);
		return array(
			'success' => true,
			'identifiers' => $suggestedIdentifiers,
			'formattedSuggestions' => $interface->fetch('MaterialsRequest/ajax-suggested-identifiers.tpl')
		);
	}

	function GetWorldCatTitles(){
		global $configArray;
		if (!isset($_REQUEST['title']) && !isset($_REQUEST['author'])){
			return array(
				'success' => false,
				'error' => 'Cannot load titles from WorldCat, an API Key must be provided in the config file.'
			);
		}else if (isset($configArray['WorldCat']['apiKey']) & strlen($configArray['WorldCat']['apiKey']) > 0){
			$worldCatUrl = "http://www.worldcat.org/webservices/catalog/search/opensearch?q=";
			if (isset($_REQUEST['title'])){
				$worldCatUrl .= urlencode($_REQUEST['title']);
			}
			if (isset($_REQUEST['author'])){
				$worldCatUrl .= '+' . urlencode($_REQUEST['author']);
			}
			if (isset($_REQUEST['format'])){
				if (in_array($_REQUEST['format'],array('dvd', 'cassette', 'vhs', 'playaway'))){
					$worldCatUrl .= '+' . urlencode($_REQUEST['format']);
				}elseif (in_array($_REQUEST['format'],array('cdAudio', 'cdMusic'))){
					$worldCatUrl .= '+' . urlencode('cd');
				}
			}
			$worldCatUrl .= "&wskey=" . $configArray['WorldCat']['apiKey'];
			$worldCatUrl .= "&format=rss&cformat=mla";
			//echo($worldCatUrl);
			$worldCatData = simplexml_load_file($worldCatUrl);
			//print_r($worldCatData);
			$worldCatResults = array();
			foreach($worldCatData->channel->item as $item){
				/** @var SimpleXMLElement $item */
				$curTitle= array(
					'title' => (string)$item->title,
					'author' => (string)$item->author->name,
					'description' => (string)$item->description,
					'link' => (string)$item->link
				);

				$oclcChildren = $item->children('oclcterms', TRUE);
				foreach ($oclcChildren as $child){
					/** @var SimpleXMLElement $child */
					if ($child->getName() == 'recordIdentifier'){
						$curTitle['oclcNumber'] = (string)$child;
					}

				}
				$dcChildren = $item->children('dc', TRUE);
				foreach ($dcChildren as $child){
					if ($child->getName() == 'identifier'){
						$identifierFields = explode(":", (string)$child);
						$curTitle[$identifierFields[1]][] = $identifierFields[2];
					}
				}

				$contentChildren = $item->children('content', TRUE);
				foreach ($contentChildren as $child){
					if ($child->getName() == 'encoded'){
						$curTitle['citation'] = (string)$child;
					}
				}

				if (strlen($curTitle['description']) == 0 && isset($curTitle["ISBN"]) && is_array($curTitle["ISBN"]) && count($curTitle["ISBN"]) > 0){
					//Get the description from syndetics
					require_once ROOT_DIR . '/Drivers/marmot_inc/GoDeeperData.php';
					$summaryInfo = GoDeeperData::getSummary($curTitle["ISBN"][0], null);
					if (isset($summaryInfo['summary'])){
						$curTitle['description'] = $summaryInfo['summary'];
					}
				}
				$worldCatResults[] = $curTitle;
			}
			return array(
				'success' => true,
				'titles' => $worldCatResults
			);
		}else{
			return array(
				'success' => false,
				'error' => 'Cannot load titles from WorldCat, an API Key must be provided in the config file.'
			);
		}
	}
}