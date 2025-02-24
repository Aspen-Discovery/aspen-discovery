<?php

require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';

/**
 * MaterialsRequest AJAX Page, handles returning asynchronous information about Materials Requests.
 */
class MaterialsRequest_AJAX extends Action {

	function AJAX() {}

	function launch() : void {
		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$result = $this->$method();
			echo json_encode($result);
		} else {
			echo json_encode(['error' => 'invalid_method']);
		}
	}

	/** @noinspection PhpUnused */
	function cancelRequest() : array {
		if (!UserAccount::isLoggedIn()) {
			return [
				'success' => false,
				'error' => 'Could not cancel the request, you must be logged in to cancel the request.',
			];
		} elseif (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'error' => 'Could not cancel the request, no id provided.',
			];
		} else {
			$id = $_REQUEST['id'];
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
			$materialsRequest->createdBy = UserAccount::getActiveUserId();
			if ($materialsRequest->find(true)) {
				//get the correct status to set based on the user's home library
				$homeLibrary = Library::getPatronHomeLibrary();
				if (is_null($homeLibrary)) {
					global $library;
					$homeLibrary = $library;
				}
				$cancelledStatus = new MaterialsRequestStatus();
				$cancelledStatus->isPatronCancel = 1;
				$cancelledStatus->libraryId = $homeLibrary->libraryId;
				$cancelledStatus->find(true);

				$materialsRequest->dateUpdated = time();
				$materialsRequest->status = $cancelledStatus->id;
				if ($materialsRequest->update()) {
					require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestUsage.php';
					MaterialsRequestUsage::incrementStat($materialsRequest->status, $materialsRequest->libraryId);

					return ['success' => true];
				} else {
					return [
						'success' => false,
						'error' => 'Could not cancel the request, error during update.',
					];
				}
			} else {
				return [
					'success' => false,
					'error' => 'Could not cancel the request, could not find a request for the provided id.',
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function updateMaterialsRequest() : array {
		global $interface;

		if (!isset($_REQUEST['id'])) {
			$interface->assign('error', translate([
				'text' => 'Please provide an id of the materials request to view.',
				'isPublicFacing' => true,
			]));
		} else {
			$id = $_REQUEST['id'];
			if (ctype_digit($id)) {
				if (UserAccount::isLoggedIn()) {
					$user = UserAccount::getLoggedInUser();
					$staffLibrary = $user->getHomeLibrary(); // staff member's home library
					if (is_null($staffLibrary)) {
						global $library;
						$staffLibrary = $library;
					}

					if (!empty($staffLibrary)) {
						// Material Request
						require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
						require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
						$materialsRequest = new MaterialsRequest();
						$materialsRequest->id = $id;

						// Statuses
						$statusQuery = new MaterialsRequestStatus();
						$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');

						// Pick-up Locations
						$locationQuery = new Location();
						$materialsRequest->joinAdd($locationQuery, "LEFT", 'location', 'holdPickupLocation', 'locationId');

						// Format Labels
						$formats = new MaterialsRequestFormat();
						$formats->libraryId = $staffLibrary->libraryId;
						$usingDefaultFormats = $formats->count() == 0;

						$materialsRequest->selectAdd();
						$materialsRequest->selectAdd('materials_request.*, status.description as statusLabel, location.displayName as location');
						if (!$usingDefaultFormats) {
							$materialsRequest->joinAdd($formats, 'LEFT', 'materials_request_formats', 'formatId', 'id');
							$materialsRequest->selectAdd('materials_request_formats.formatLabel,materials_request_formats.authorLabel, materials_request_formats.specialFields');
						}

						if ($materialsRequest->find(true)) {
							$canUpdate = false;
							$isAdminUser = false;

							//Load user information
							$requestUser = new User();
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
									if (is_null($requestUserLibrary)) {
										global $library;
										$requestUserLibrary = $library;
									}
									$canUpdate = $requestUserLibrary->libraryId == $staffLibrary->libraryId;
									$isAdminUser = true;
								}
								if ($canUpdate) {
									$interface->assign('isAdminUser', $isAdminUser);
									//Get a list of formats to show
									$availableFormats = MaterialsRequest::getFormats(false);
									$interface->assign('availableFormats', $availableFormats);

									// Get Author Labels for all Formats
									[
										$formatAuthorLabels,
										$specialFieldFormats,
									] = $materialsRequest->getAuthorLabelsAndSpecialFields($staffLibrary->libraryId);
									if ($usingDefaultFormats) {
										$defaultFormats = MaterialsRequestFormat::getDefaultMaterialRequestFormats();
										/** @var MaterialsRequestFormat $format */
										foreach ($defaultFormats as $format) {
											// Get the default values for this request
											if ($materialsRequest->format == $format->format) {
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

									$interface->assign('checkRequestsForExistingTitles', $staffLibrary->checkRequestsForExistingTitles);

									// Hold Pick-up Locations
									$location = new Location();
									$locationList = $location->getPickupBranches($requestUser);
									$pickupLocations = [];
									foreach ($locationList as $curLocation) {
										if (is_object($curLocation)) {
											$pickupLocations[] = [
												'id' => $curLocation->locationId,
												'displayName' => $curLocation->displayName,
												'selected' => $curLocation->locationId == $materialsRequest->holdPickupLocation ? 'selected' : '',
											];
										}
									}

									// Add bookmobile Stop to the pickup locations if that form field is being used.
									foreach ($requestFormFields as $category) {
										/** @var MaterialsRequestFormFields $formField */
										foreach ($category as $formField) {
											if ($formField->fieldType == 'bookmobileStop') {
												$pickupLocations[] = [
													'id' => 'bookmobile',
													'displayName' => $formField->fieldLabel,
													'selected' => $materialsRequest->holdPickupLocation == 'bookmobile',
												];
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
									while ($materialsRequestStatus->fetch()) {
										$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
									}
									$interface->assign('availableStatuses', $availableStatuses);

									// Get Barcode Column
									$interface->assign('barCodeColumn', 'ils_barcode');

								} else {
									$interface->assign('error', translate([
										'text' => 'Sorry, you don\'t have permission to update this materials request.',
										'isPublicFacing' => true,
									]));
								}
							} else {
								$interface->assign('error', translate([
									'text' => "Sorry, we couldn't find the user that made this materials request.",
									'isPublicFacing' => true,
								]));
							}
						} else {
							$interface->assign('error', translate([
								'text' => "Sorry, we couldn't find a materials request for that id.",
								'isPublicFacing' => true,
							]));
						}
					} else {
						$interface->assign('error', translate([
							'text' => 'We could not determine your home library.',
							'isPublicFacing' => true,
						]));
					}
				} else {
					$interface->assign('error', translate([
						'text' => 'Please log in to view & edit the materials request.',
						'isPublicFacing' => true,
					]));
				}
			} else {
				$interface->assign('error', translate([
					'text' => 'Sorry, invalid id for a materials request.',
					'isPublicFacing' => true,
				]));
			}
		}
		return [
			'title' => 'Update Materials Request',
			'modalBody' => $interface->fetch('MaterialsRequest/ajax-update-request.tpl'),
			'modalButtons' => $interface->get_template_vars('error') == null ? "<button class='btn btn-primary' onclick='$(\"#materialsRequestUpdateForm\").submit();'>" . translate([
					'text' => "Update Request",
					'isPublicFacing' => true,
				]) . "</button>" : '',
		];
	}

	/** @noinspection PhpUnused */
	function MaterialsRequestDetails() : array {
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if (!isset($_REQUEST['id'])) {
			$interface->assign('error', translate([
				'text' => 'Please provide an id of the materials request to view.',
				'isPublicFacing' => true,
			]));
		} elseif (empty($user)) {
			$interface->assign('error', translate([
				'text' => 'Please log in to view details.',
				'isPublicFacing' => true,
			]));
		} else {
			$id = $_REQUEST['id'];
			if (!empty($id) && ctype_digit($id)) {
				$requestLibrary = $user->getHomeLibrary(); // staff member's or patron's home library
				if (is_null($requestLibrary)) {
					global $library;
					$requestLibrary = $library;
				}
				if (!empty($requestLibrary)) {
					$materialsRequest = new MaterialsRequest();
					$materialsRequest->id = $id;

					$staffView = $_REQUEST['staffView'] ?? true;
					$requestFormFields = $materialsRequest->getRequestFormFields($requestLibrary->libraryId, $staffView);
					$interface->assign('requestFormFields', $requestFormFields);


					// Statuses
					$statusQuery = new MaterialsRequestStatus();
					$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');

					// Pick-up Locations
					$locationQuery = new Location();
					$materialsRequest->joinAdd($locationQuery, "LEFT", 'location', 'holdPickupLocation', 'locationId');

					// Format Labels
					$formats = new MaterialsRequestFormat();
					$formats->libraryId = $requestLibrary->libraryId;
					$usingDefaultFormats = $formats->count() == 0;

					$materialsRequest->selectAdd();
					$materialsRequest->selectAdd('materials_request.*, status.description as statusLabel, location.displayName as location');
					if (!$usingDefaultFormats) {
						$materialsRequest->joinAdd($formats, 'LEFT', 'materials_request_formats', 'formatId', 'id');
						$materialsRequest->selectAdd('materials_request_formats.formatLabel,materials_request_formats.authorLabel, materials_request_formats.specialFields');
					}

					if ($materialsRequest->find(true)) {
						if ($usingDefaultFormats) {
							$defaultFormats = MaterialsRequestFormat::getDefaultMaterialRequestFormats();
							/** @var MaterialsRequestFormat $format */
							foreach ($defaultFormats as $format) {
								if ($materialsRequest->format == $format->format) {
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

						if (UserAccount::userHasPermission('Manage Library Materials Requests')) {
							$interface->assign('showUserInformation', true);
							//Load user information
							$requestUser = new User();
							$requestUser->id = $materialsRequest->createdBy;
							if ($requestUser->find(true)) {
								$interface->assign('requestUser', $requestUser);

								// Get Barcode Column
								$interface->assign('barCodeColumn', 'ils_barcode');

							}
						} else {
							$interface->assign('showUserInformation', false);
						}
					} else {
						$interface->assign('error', translate([
							'text' => "Sorry, we couldn't find a materials request for that id.",
							'isPublicFacing' => true,
						]));
					}
				} else {
					$interface->assign('error', translate([
						'text' => 'Could not determine your home library.',
						'isPublicFacing' => true,
					]));
				}
			} else {
				$interface->assign('error', translate([
					'text' => 'Invalid Request ID.',
					'isPublicFacing' => true,
				]));
			}
		}
		return [
			'title' => translate([
				'text' => 'Materials Request Details',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MaterialsRequest/ajax-request-details.tpl'),
			'modalButtons' => ''
			//TODO idea: add Update Request button (for staff only?)
		];
	}

	/** @noinspection PhpUnused */
	function showSelectHoldCandidateForm() : array {
		global $interface;

		if (empty($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
			return [
				'title' => translate(['text' => 'Error', 'inAttribute' => true, 'isAdminFacing' => true]),
				'modalBody' => "<div class='alert alert-danger'>" . translate(['text' => 'No ID was provided', 'inAttribute' => true, 'isAdminFacing' => true]) . "</div>",
				'modalButtons' => '',
			];
		}else{
			$id = $_REQUEST['id'];
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidate.php';
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $id;
			if ($materialsRequest->find(true)) {
				$holdCandidates = $materialsRequest->getHoldCandidates();
				$interface->assign('requestId', $id);
				$interface->assign('holdCandidates', $holdCandidates);
				return [
					'title' => 'Select Hold Candidate',
					'modalBody' => $interface->fetch("MaterialsRequest/select-hold-candidate.tpl"),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='return AspenDiscovery.MaterialsRequest.selectHoldCandidate()'>" . translate([
							'text' => 'Use Selected',
							'isAdminFacing' => true,
							'inAttribute' => true,
						]) . "</button>",
				];
			}else{
				return [
					'title' => translate(['text' => 'Error', 'inAttribute' => true, 'isAdminFacing' => true]),
					'modalBody' => "<div class='alert alert-danger'>" . translate(['text' => 'Incorrect ID was provided', 'inAttribute' => true, 'isAdminFacing' => true]) . "</div>",
					'modalButtons' => '',
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function selectHoldCandidate() : array {
		if (empty($_REQUEST['requestId']) || !is_numeric($_REQUEST['requestId'])) {
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'inAttribute' => true, 'isAdminFacing' => true]),
				'modalBody' => "<div class='alert alert-danger'>" . translate(['text' => 'No ID was provided', 'inAttribute' => true, 'isAdminFacing' => true]) . "</div>",
				'modalButtons' => '',
			];
		}else{
			$requestId = $_REQUEST['requestId'];
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidate.php';
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->id = $requestId;
			if ($materialsRequest->find(true)) {
				if (empty($_REQUEST['holdCandidateId']) || !is_numeric($_REQUEST['holdCandidateId'])) {
					return [
						'success' => false,
						'title' => translate(['text' => 'Error', 'inAttribute' => true, 'isAdminFacing' => true]),
						'modalBody' => "<div class='alert alert-danger'>" . translate(['text' => 'No Hold Candidate was selected.', 'inAttribute' => true, 'isAdminFacing' => true]) . "</div>",
						'modalButtons' => '',
					];
				}else{
					$selectedHoldCandidateId = $_REQUEST['holdCandidateId'];
					$holdCandidate = new MaterialsRequestHoldCandidate();
					$holdCandidate->requestId = $requestId;
					$holdCandidate->id = $selectedHoldCandidateId;
					if ($holdCandidate->find(true)) {
						$materialsRequest->selectedHoldCandidateId = $selectedHoldCandidateId;
						$materialsRequest->update();

						return [
							'success' => true,
							'title' => 'Hold Candidate Selected',
							'modalBody' => translate([
								'text' => 'The selected materials request has been updated.',
								'isAdminFacing' => true,
								'inAttribute' => true,
							]),
							'modalButtons' => '',
						];
					}else{
						return [
							'success' => false,
							'title' => translate(['text' => 'Error', 'inAttribute' => true, 'isAdminFacing' => true]),
							'modalBody' => "<div class='alert alert-danger'>" . translate(['text' => 'Invalid hold candidate selected', 'inAttribute' => true, 'isAdminFacing' => true]) . "</div>",
							'modalButtons' => '',
						];
					}
				}
			}else{
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'inAttribute' => true, 'isAdminFacing' => true]),
					'modalBody' => "<div class='alert alert-danger'>" . translate(['text' => 'Incorrect ID was provided', 'inAttribute' => true, 'isAdminFacing' => true]) . "</div>",
					'modalButtons' => '',
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function checkForExistingRecord() : array {
		$result = [
			'success' => false,
			'hasExistingRecord' => false,
			'existingRecordCover' => '',
			'existingRecordLink' => '',
		];
		$format = $_REQUEST['format'] ?? '';
		$title = $_REQUEST['title'] ?? '';
		$author = $_REQUEST['author'] ?? '';
		$isbn = $_REQUEST['isbn'] ?? '';
		$issn = $_REQUEST['issn'] ?? '';
		$upc = $_REQUEST['upc'] ?? '';

		//Need the format as well as isbn, issn or title + author
		if (!empty($format)) {
			$okToProcess = false;
			if (!empty($isbn) || !empty($issn) || !empty($upc)) {
				$okToProcess = true;
			}else if (!empty($title) && !empty($author)){
				$okToProcess = true;
			}
			if ($okToProcess) {
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidateGenerator.php';
				$existingRecords = checkForExistingTitleForRequest($format, $title, $author, $isbn, $issn, $upc);
				$result['success'] = true;
				if ($existingRecords !== false && count($existingRecords) > 0) {
					$result['success'] = true;
					/** @var GroupedWorkDriver $firstRecord */
					$firstRecord = $existingRecords[0];
					$result['hasExistingRecord'] = true;
					$result['existingRecordCover'] = $firstRecord->getBookcoverUrl();
					$result['existingRecordLink'] = $firstRecord->getLinkUrl();
				}
			}else{
				$result['message'] = translate(['text' => 'Format must be provided to look for an existing record', 'isPublicFacing' => true]);
			}
		}else{
			$result['message'] = translate(['text' => 'Format must be provided to look for an existing record', 'isPublicFacing' => true]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function checkRequestForExistingRecord() : array {
		$id = $_REQUEST['id'];
		$result = [
			'success' => false,
			'existingRecordInformation' => ''
		];
		if (empty($id) || !is_numeric($id)) {
			$result['message'] = 'Invalid ID provided';
		}else{
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
			$request = new MaterialsRequest();
			$request->id = $id;
			if ($request->find(true)) {
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidateGenerator.php';
				$existingRecords = checkForExistingTitleForRequest($request->format, $request->title, $request->author, $request->isbn, $request->issn, $request->upc);
				$request->lastCheckForExistingRecord = time();

				if ($existingRecords === false || count($existingRecords) == 0) {
					/** @noinspection PhpUnusedLocalVariableInspection */
					$infoChanged = $request->hasExistingRecord != 0;
					$request->hasExistingRecord = 0;
					$existingRecordInformation = translate(['text'=>'No', 'isAdminFacing'=>true]);
				}else{
					/** @noinspection PhpUnusedLocalVariableInspection */
					$infoChanged = $request->hasExistingRecord == 0;

					$firstRecord = $existingRecords[0];
					$request->hasExistingRecord = 1;
					$request->existingRecordUrl = $firstRecord->getLinkUrl();
					$existingRecordInformation = "<a href='$request->existingRecordUrl' target='_blank'>" . translate(['text'=>'Yes', 'isAdminFacing'=>true]) . "</a>";
				}
				$existingRecordInformation .= '<br/>' . translate(['text'=>'Checked %1%', 1=> date('m/d/Y H:i:s', $request->lastCheckForExistingRecord), 'isAdminFacing'=>true]);
				$request->update();

				$result['success'] = true;
				$result['existingRecordInformation'] = $existingRecordInformation;
			}else{
				$result['message'] = 'Invalid ID provided';
			}
		}

		return $result;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}