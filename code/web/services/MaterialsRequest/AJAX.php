<?php

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
		if (method_exists($this, $method)) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$result = $this->$method();
			echo json_encode($result);
		}else{
			echo json_encode(array('error'=>'invalid_method'));
		}
	}

	/** @noinspection PhpUnused */
	function cancelRequest(){
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

	/** @noinspection PhpUnused */
	function updateMaterialsRequest(){
		global $interface;

		if (!isset($_REQUEST['id'])){
			$interface->assign('error', translate(['text' => 'Please provide an id of the materials request to view.', 'isPublicFacing'=>true]));
		}else {
			$id = $_REQUEST['id'];
			if (ctype_digit($id)) {
				if (UserAccount::isLoggedIn()) {
					$user = UserAccount::getLoggedInUser();
					$staffLibrary = $user->getHomeLibrary(); // staff member's home library

					if (!empty($staffLibrary)) {
						// Material Request
						$materialsRequest = new MaterialsRequest();
						$materialsRequest->id = $id;

						// Statuses
						$statusQuery = new MaterialsRequestStatus();
						$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');

						// Pick-up Locations
						$locationQuery = new Location();
						$materialsRequest->joinAdd($locationQuery, "LEFT", 'location', 'holdPickupLocation', 'locationId');

						// Format Labels
						$formats = new MaterialsRequestFormats();
						$formats->libraryId = $staffLibrary->libraryId;
						$usingDefaultFormats = $formats->count() == 0;

						$materialsRequest->selectAdd();
						$materialsRequest->selectAdd(
							'materials_request.*, status.description as statusLabel, location.displayName as location'
						);
						if (!$usingDefaultFormats) {
							$materialsRequest->joinAdd($formats, 'LEFT', 'materials_request_formats', 'formatId', 'id');
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
									$isAdminUser = UserAccount::userHasPermission('Manage Library Materials Requests');
								} elseif (UserAccount::userHasPermission('Manage Library Materials Requests')) {
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
												/** @noinspection PhpUndefinedFieldInspection */
												$materialsRequest->formatLabel = $format->formatLabel;
												/** @noinspection PhpUndefinedFieldInspection */
												$materialsRequest->authorLabel = $format->authorLabel;
												/** @noinspection PhpUndefinedFieldInspection */
												$materialsRequest->specialFields = $format->specialFields;
												break;
											}
										}
									}

									$interface->assign('formatAuthorLabelsJSON', json_encode($formatAuthorLabels));
									$interface->assign('specialFieldFormatsJSON', json_encode($specialFieldFormats));

									$interface->assign('materialsRequest', $materialsRequest);
									$interface->assign('showUserInformation', true);

									// Hold Pick-up Locations
									$location = new Location();
									$locationList = $location->getPickupBranches($requestUser);
									$pickupLocations = array();
									foreach ($locationList as $curLocation) {
										if (is_object($curLocation)) {
											$pickupLocations[] = array(
												'id' => $curLocation->locationId,
												'displayName' => $curLocation->displayName,
												'selected' => is_object($curLocation) ? ($curLocation->locationId == $materialsRequest->holdPickupLocation ? 'selected' : '') : '',
											);
										}
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
									$materialsRequestStatus->find();
									$availableStatuses = [];
									while ($materialsRequestStatus->fetch()){
										$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
									}
									$interface->assign('availableStatuses', $availableStatuses);

									// Get Barcode Column
									$barCodeColumn = null;
									if ($accountProfile = $user->getAccountProfile()) {
										$barCodeColumn = $accountProfile->loginConfiguration == 'name_barcode' ? 'cat_password' : 'cat_username';
									}
									$interface->assign('barCodeColumn', $barCodeColumn);

								} else {
									$interface->assign('error', translate(['text' => 'Sorry, you don\'t have permission to update this materials request.', 'isPublicFacing'=>true]));
								}
							} else {
								$interface->assign('error', translate(['text' => 'Sorry, we couldn\'t find the user that made this materials request.', 'isPublicFacing'=>true]));
							}
						} else {
							$interface->assign('error', translate(['text' => 'Sorry, we couldn\'t find a materials request for that id.', 'isPublicFacing'=>true]));
						}
					} else {
						$interface->assign('error', translate(['text' => 'We could not determine your home library.', 'isPublicFacing'=>true]));
					}
				} else {
					$interface->assign('error', translate(['text' => 'Please log in to view & edit the materials request.', 'isPublicFacing'=>true]));
				}
			} else {
				$interface->assign('error', translate(['text' => 'Sorry, invalid id for a materials request.', 'isPublicFacing'=>true]));
			}
		}
		return array(
			'title' => 'Update Materials Request',
			'modalBody' => $interface->fetch('MaterialsRequest/ajax-update-request.tpl'),
			'modalButtons' => $interface->get_template_vars('error') == null ?  "<button class='btn btn-primary' onclick='$(\"#materialsRequestUpdateForm\").submit();'>" . translate(['text' => "Update Request", 'isPublicFacing'=>true]) . "</button>" : ''
		);
	}

	/** @noinspection PhpUnused */
	function MaterialsRequestDetails(){
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if (!isset($_REQUEST['id'])) {
			$interface->assign('error', translate(['text' => 'Please provide an id of the materials request to view.', 'isPublicFacing'=>true]));
		}elseif (empty($user)) {
			$interface->assign('error', translate(['text' => 'Please log in to view details.', 'isPublicFacing'=>true]));
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
					$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');

					// Pick-up Locations
					$locationQuery = new Location();
					$materialsRequest->joinAdd($locationQuery, "LEFT", 'location', 'holdPickupLocation', 'locationId');

					// Format Labels
					$formats = new MaterialsRequestFormats();
					$formats->libraryId = $requestLibrary->libraryId;
					$usingDefaultFormats = $formats->count() == 0;

					$materialsRequest->selectAdd();
					$materialsRequest->selectAdd(
						'materials_request.*, status.description as statusLabel, location.displayName as location'
					);
					if (!$usingDefaultFormats) {
						$materialsRequest->joinAdd($formats, 'LEFT', 'materials_request_formats', 'formatId', 'id');
						$materialsRequest->selectAdd('materials_request_formats.formatLabel,materials_request_formats.authorLabel, materials_request_formats.specialFields');
					}

					if ($materialsRequest->find(true)) {
						if ($usingDefaultFormats) {
							$defaultFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats();
							/** @var MaterialsRequestFormats $format */
							foreach ($defaultFormats as $format) {
								if ($materialsRequest->format == $format->format ){
									/** @noinspection PhpUndefinedFieldInspection */
									$materialsRequest->formatLabel = $format->formatLabel;
									/** @noinspection PhpUndefinedFieldInspection */
									$materialsRequest->authorLabel = $format->authorLabel;
									/** @noinspection PhpUndefinedFieldInspection */
									$materialsRequest->specialFields = $format->specialFields;
									break;
								}
							}
						}

						$interface->assign('materialsRequest', $materialsRequest);

						if ($user && UserAccount::userHasPermission('Manage Library Materials Requests')) {
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
						$interface->assign('error', translate(['text' => 'Sorry, we couldn\'t find a materials request for that id.', 'isPublicFacing'=>true]));
					}
				} else {
					$interface->assign('error', translate(['text' => 'Could not determine your home library.', 'isPublicFacing'=>true]));
				}
			} else {
				$interface->assign('error', translate(['text' => 'Invalid Request ID.', 'isPublicFacing'=>true]));
			}
		}
		return array(
				'title'        => translate(['text' => 'Materials Request Details', 'isPublicFacing'=>true]),
				'modalBody'    => $interface->fetch('MaterialsRequest/ajax-request-details.tpl'),
				'modalButtons' => '' //TODO idea: add Update Request button (for staff only?)
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
			/** @var stdClass $worldCatData */
			$worldCatData = simplexml_load_file($worldCatUrl);
			//print_r($worldCatData);
			$worldCatResults = array();
			foreach($worldCatData->channel->item as $item){
				/** @var SimpleXMLElement $item */
				/** @noinspection PhpUndefinedFieldInspection */
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
					$summaryInfo = GoDeeperData::getSummary(null, $curTitle["ISBN"][0], null);
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

	/** @noinspection PhpUnused */
	function getImportRequestForm(){
		global $interface;

		return array(
			'title' => 'Import Materials Requests',
			'modalBody' => $interface->fetch("MaterialsRequest/import-requests.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#importRequestsForm\").submit()'>" . translate(['text'=>'Import Requests','isAdminFacing'=>true, 'inAttribute'=>true]) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function importRequests(){
		$result = [
			'success' => false,
			'title' => 'Importing Requests',
			'message' => 'Sorry your requests could not be imported'
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Import Materials Requests'))){
			if (isset($_FILES['exportFile'])) {
				$uploadedFile = $_FILES['exportFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = "No file was uploaded";
				} else if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] =  "Error in file upload " . $uploadedFile["error"];
				} else {
					try {
						$inputFileType = PHPExcel_IOFactory::identify($uploadedFile['tmp_name']);
						$objReader = PHPExcel_IOFactory::createReader($inputFileType);
						/** @var PHPExcel $objPHPExcel */
						$objPHPExcel = $objReader->load($uploadedFile['tmp_name']);

						global $library;
						global $configArray;
						$libraryId = $library->libraryId;

						$allStatuses = [];
						$materialRequestStatus = new MaterialsRequestStatus();
						$materialRequestStatus->libraryId = $libraryId;
						$materialRequestStatus->find();
						while ($materialRequestStatus->fetch()){
							$allStatuses[$materialRequestStatus->id] = $materialRequestStatus->description;
						}

						/** @var  $sheet */
						$sheet = $objPHPExcel->getSheet(0);
						if ($sheet->getCellByColumnAndRow(0, 1)->getValue() == 'Materials Requests'){
							//Get the request data
							$highestRow = $sheet->getHighestRow();
							$headers = $rowData = $sheet->rangeToArray('A3:' . $sheet->getHighestColumn() . '3',
								NULL,
								TRUE,
								FALSE)[2];
							$showEBookFormatField = in_array('Sub Format', $headers);
							$showBookTypeField = in_array('Type', $headers);
							$showAgeField = in_array('Age Level', $headers);
							$showPlaceHoldField = in_array('Hold', $headers);
							$showIllField = in_array('ILL', $headers);
							$numImported = 0;
							$numSkippedCouldNotFindUser = 0;
							$numSkippedCouldNotFindStatus = 0;
							$numSkippedFailedInsert = 0;
							for ($rowNum = 4; $rowNum <= $highestRow; $rowNum++){
								$materialRequest = new MaterialsRequest();
								$curCol = 1;
								$materialRequest->libraryId = $libraryId;
								$materialRequest->title = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->season = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$magazineInfo = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$magazineTitle = $magazineInfo;
								//TODO: Split up magazine information?
								$materialRequest->magazineTitle = $magazineTitle; //This isn't quite right, date will append to title
								$materialRequest->author = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->format = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								if ($showEBookFormatField){
									$materialRequest->subFormat = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								}
								if ($showBookTypeField){
									$materialRequest->bookType = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								}
								if ($showAgeField){
									$materialRequest->ageLevel = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								}
								$materialRequest->isbn = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->upc = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->issn = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->oclcNumber = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->publisher = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->publicationYear = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->abridged = ($sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue() == 'Unabridged' ? 0 : ($sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue() == 'Abridged' ? 1 : 2));
								$materialRequest->about = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$materialRequest->comments = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$username = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$barcode = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getFormattedValue();
								$email = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getFormattedValue();
								if (is_numeric($barcode)){
									$barcode = (int)$barcode;
								}
								$requestUser = new User();
								$barcodeProperty = $configArray['Catalog']['barcodeProperty'];

								$requestUser->$barcodeProperty = $barcode;
								$requestUser->find();
								if ($requestUser->getNumResults() == 0){
									//Try looking by last name, first
									$requestUser = new User();
									$requestUser->cat_username = $username;
									$requestUser->find();
									if ($requestUser->getNumResults() == 0) {
										$requestUser = new User();
										$requestUser->email = $email;
										$requestUser->find();
										if (empty($email) || $requestUser->getNumResults() == 0) {
											//See if we can fetch the user from the ils
											$requestUser = UserAccount::findNewUser($barcode);
											if ($requestUser == false) {
												//We didn't get a user, skip this one.
												$numSkippedCouldNotFindUser++;
												continue;
											}
										}else{
											$requestUser->fetch();                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
										}
									}else{
										$requestUser->fetch();
									}
								}else{
									$requestUser->fetch();
								}
								$materialRequest->createdBy = $requestUser->id;
								$materialRequest->email = $email;
								if ($showPlaceHoldField){
									$placeHold = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
									if ($placeHold == 'No'){
										$materialRequest->placeHoldWhenAvailable = 0;
									}else{
										$materialRequest->placeHoldWhenAvailable = 1;
									}
								}
								if ($showIllField){
									$materialRequest->illItem = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue() == 'Yes' ? 1 : 0;
								}
								$materialRequest->status = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								if (is_numeric($materialRequest->status)){
									$materialRequest->status = (int)$materialRequest->status;
								}
								if (!array_key_exists($materialRequest->status, $allStatuses)){
									$numSkippedCouldNotFindStatus++;
									continue;
								}
								$dateCreated = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();
								$dateTimeCreated = date_create_from_format('m/d/Y', $dateCreated);
								$materialRequest->dateCreated = $dateTimeCreated->getTimestamp();
								$materialRequest->dateUpdated = $dateTimeCreated->getTimestamp();
								/** @noinspection PhpUnusedLocalVariableInspection */
								$assignedTo = $sheet->getCellByColumnAndRow($curCol++, $rowNum)->getValue();

								if ($materialRequest->insert() == 1){
									$numImported++;
								}else{
									$numSkippedFailedInsert++;
								}
							}
							$result['success'] = true;
							$result['message'] = "Imported file, $numImported entries were imported successfully.";
							if ($numSkippedFailedInsert > 0) {
								$result['message'] .= "<br/>$numSkippedFailedInsert could not be inserted in the database.";
								$result['success'] = false;
							}
							if ($numSkippedCouldNotFindStatus > 0) {
								$result['message'] .= "<br/>$numSkippedCouldNotFindStatus did not have a proper status.";
								$result['success'] = false;
							}
							if ($numSkippedCouldNotFindUser > 0) {
								$result['message'] .= "<br/>$numSkippedCouldNotFindUser could not find a user.";
								$result['success'] = false;
							}
						}else{
							$result['message'] =  "This does not look like a valid export of Material Request data";
						}
					} catch(Exception $e) {
						$result['message'] =  "Error reading file : " . $e->getMessage();
					}

				}
			}else{
				$result['message'] = 'No file was selected, please try again.';
			}
		}
		return $result;
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}