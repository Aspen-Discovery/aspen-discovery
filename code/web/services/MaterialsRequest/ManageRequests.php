<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php');
require_once(ROOT_DIR . '/sys/Administration/StickyFilter.php');
require_once ROOT_DIR . '/sys/User/PageDefaults.php';

class MaterialsRequest_ManageRequests extends Admin_Admin {

	function launch() : void {
		global $interface;

		//Load status information
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, isActive DESC, description ASC');
		$homeLibrary = Library::getPatronHomeLibrary();
		$user = UserAccount::getLoggedInUser();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}

		$materialsRequestStatus->libraryId = $homeLibrary->libraryId;
		$materialsRequestStatus->find();

		$availableStatuses = [];
		$defaultStatusesToShow = [];
		$statusesToShow = [];
		while ($materialsRequestStatus->fetch()) {
			$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
			if ($materialsRequestStatus->isOpen == 1 || $materialsRequestStatus->isActive == 1 || $materialsRequestStatus->isDefault == 1) {
				$defaultStatusesToShow[] = $materialsRequestStatus->id;
			}
		}
		$interface->assign('availableStatuses', $availableStatuses);

		if (isset($_REQUEST['statusFilter'])) {
			$statusesToShow = $_REQUEST['statusFilter'];
			//add filters that have been checked to default filters table for sticky filters
			foreach ($statusesToShow as $status) {
				$stickyFilter = new StickyFilter();
				$stickyFilter->userId = $user->id;
				$stickyFilter->filterValue = $status;
				$stickyFilter->filterFor = "MaterialsRequest_Status";
				if (!$stickyFilter->find(true)) {
					$stickyFilter->insert();
				}
			}
			//remove filters that have been unchecked
			$stickyFilterDeletions = new StickyFilter();
			$stickyFilterDeletions->userId = $user->id;
			$stickyFilterDeletions->filterFor = "MaterialsRequest_Status";
			$stickyFilterDeletions->find();
			while ($stickyFilterDeletions->fetch()) {
				if (!in_array($stickyFilterDeletions->filterValue, $statusesToShow)) {
					$stickyFilterDeletions->delete();
				}
			}
		}
		//check for sticky filter values saved for admin account
		$adminStickyFilter = new StickyFilter();
		$adminStickyFilter->userId = $user->id;
		$adminStickyFilter->filterFor = "MaterialsRequest_Status";
		if ($adminStickyFilter->find()) {
			while ($adminStickyFilter->fetch()) {
				$statusesToShow[] = $adminStickyFilter->filterValue;
			}
		} else { //if there are no set or saved filters use default
			$statusesToShow = $defaultStatusesToShow;
		}
		$interface->assign('statusFilter', $statusesToShow);

		$assigneesToShow = [];
		if (isset($_REQUEST['assigneesFilter'])) {
			$assigneesToShow = $_REQUEST['assigneesFilter'];
			//add filters that have been checked to default filters table for sticky filters
			foreach ($assigneesToShow as $assignee) {
				$stickyFilter = new StickyFilter();
				$stickyFilter->userId = $user->id;
				$stickyFilter->filterValue = $assignee;
				$stickyFilter->filterFor = "MaterialsRequest_Assignee";
				if (!$stickyFilter->find(true)) {
					$stickyFilter->insert();
				}
			}
			//remove filters that have been unchecked
			$stickyFilterDeletions = new StickyFilter();
			$stickyFilterDeletions->userId = $user->id;
			$stickyFilterDeletions->filterFor = "MaterialsRequest_Assignee";
			$stickyFilterDeletions->find();
			while ($stickyFilterDeletions->fetch()) {
				if (!in_array($stickyFilterDeletions->filterValue, $assigneesToShow)) {
					$stickyFilterDeletions->delete();
				}
			}
		}

		//handle unassigned value and passing no filter values
		//if no filter values are passed for status/assignee Aspen defaults are used
		if (!empty($_REQUEST['submit']) && $_REQUEST['submit'] == 'Update Filters' ) {
			$showUnassigned = isset($_REQUEST['showUnassigned']) ? 1 : 0;
			$stickyFilter = new StickyFilter();
			$stickyFilter->userId = $user->id;
			$stickyFilter->filterFor = "MaterialsRequest_ShowUnassigned";
			if (!$stickyFilter->find(true)) {
				$stickyFilter->filterValue = $showUnassigned;
				$stickyFilter->insert();
			} else {
				$stickyFilter->filterValue = $showUnassigned;
				$stickyFilter->update();
			}

			if (!isset($_REQUEST['statusFilter'])) {
				$stickyFilterDeletions = new StickyFilter();
				$stickyFilterDeletions->userId = $user->id;
				$stickyFilterDeletions->filterFor = "MaterialsRequest_Status";
				$stickyFilterDeletions->find();
				while ($stickyFilterDeletions->fetch()) {
					$stickyFilterDeletions->delete();
				}
			}
			if (!isset($_REQUEST['assigneesFilter'])) {
				$stickyFilterDeletions = new StickyFilter();
				$stickyFilterDeletions->userId = $user->id;
				$stickyFilterDeletions->filterFor = "MaterialsRequest_Assignee";
				$stickyFilterDeletions->find();
				while ($stickyFilterDeletions->fetch()) {
					$stickyFilterDeletions->delete();
				}
			}

			$startDateFilter = new StickyFilter();
			$startDateFilter->userId = $user->id;
			$startDateFilter->filterFor = "MaterialsRequest_StartDate";
			if (!$startDateFilter->find(true)) {
				if (!empty($_REQUEST['startDate'])) {
					$startDateFilter->filterValue = $_REQUEST['startDate'];
					$startDateFilter->insert();
				}
			} else {
				if (!empty($_REQUEST['startDate'])) {
					$startDateFilter->filterValue = $_REQUEST['startDate'];
					$startDateFilter->update();
				} else {
					$startDateFilter->delete();
				}
			}

			$endDateFilter = new StickyFilter();
			$endDateFilter->userId = $user->id;
			$endDateFilter->filterFor = "MaterialsRequest_EndDate";
			if (!$endDateFilter->find(true)) {
				if (!empty($_REQUEST['endDate'])) {
					$endDateFilter->filterValue = $_REQUEST['endDate'];
					$endDateFilter->insert();
				}
			} else {
				if (!empty($_REQUEST['endDate'])) {
					$endDateFilter->filterValue = $_REQUEST['endDate'];
					$endDateFilter->update();
				} else {
					$endDateFilter->delete();
				}
			}
		}


		//check for sticky filter values saved for admin account
		$adminStickyFilter = new StickyFilter();
		$adminStickyFilter->userId = $user->id;
		$adminStickyFilter->filterFor = "MaterialsRequest_Assignee";
		if ($adminStickyFilter->find()) {
			while ($adminStickyFilter->fetch()) {
				$assigneesToShow[] = $adminStickyFilter->filterValue;
			}
		}
		$adminStickyFilter = new StickyFilter();
		$adminStickyFilter->userId = $user->id;
		$adminStickyFilter->filterFor = "MaterialsRequest_ShowUnassigned";
		if ($adminStickyFilter->find()) {
			while ($adminStickyFilter->fetch()) {
				$showUnassigned = $adminStickyFilter->filterValue;
			}
		} else {
			$showUnassigned = !empty($_REQUEST['showUnassigned']) && $_REQUEST['showUnassigned'] == 'on';
		}


		$interface->assign('assigneesFilter', $assigneesToShow);
		$interface->assign('showUnassigned', $showUnassigned);

		$interface->assign('showExistingTitleInformation', $homeLibrary->checkRequestsForExistingTitles);

		//Process status change if needed
		if (isset($_REQUEST['newStatus']) && isset($_REQUEST['select']) && $_REQUEST['newStatus'] != 'unselected') {
			//Look for which titles should be modified
			$selectedRequests = $_REQUEST['select'];
			$statusToSet = $_REQUEST['newStatus'];
			foreach ($selectedRequests as $requestId => $selected) {
				$materialRequest = new MaterialsRequest();
				$materialRequest->id = $requestId;
				if ($materialRequest->find(true)) {
					if ($materialRequest->status != $statusToSet) {
						$materialRequest->status = $statusToSet;
						$materialRequest->dateUpdated = time();
						$materialRequest->update();

						$materialRequest->sendStatusChangeEmail();
						require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestUsage.php';
						MaterialsRequestUsage::incrementStat($materialRequest->status, $materialRequest->libraryId);
					}
				}
			}
		}


		// Assign Requests
		if (isset($_REQUEST['newAssignee']) && isset($_REQUEST['select']) && $_REQUEST['newAssignee'] != 'unselected') {
			//Look for which material requests should be modified
			$selectedRequests = $_REQUEST['select'];
			$assignee = $_REQUEST['newAssignee'];
			if (ctype_digit($assignee) || $assignee == 'unassign') {
				$numRequests = count($selectedRequests);
				foreach ($selectedRequests as $requestId => $selected) {
					$materialRequest = new MaterialsRequest();
					$materialRequest->id = $requestId;
					if ($materialRequest->find(true)) {
						$materialRequest->assignedTo = $assignee == 'unassign' ? 'null' : $assignee;
						$materialRequest->dateUpdated = time();
						$materialRequest->update();

						if($numRequests == 1 && $assignee != 'unassign') {
							$materialRequest->sendStaffNewMaterialsRequestAssignedEmail();
						}

					}
				}

				if($numRequests > 1 && $assignee != 'unassign') {
					MaterialsRequest::sendStaffNewMaterialsRequestAssignedEmailBulk($numRequests, $assignee);
				}
			} else {
				$interface->assign('error', 'User to assign the request to was not valid.');
			}
		}

		$availableFormats = MaterialsRequest::getFormats(false);
		$interface->assign('availableFormats', $availableFormats);
		$defaultFormatsToShow = array_keys($availableFormats);
		if (isset($_REQUEST['formatFilter'])) {
			$formatsToShow = $_REQUEST['formatFilter'];
			$_SESSION['materialsRequestFormatFilter'] = $formatsToShow;
		} elseif (isset($_SESSION['materialsRequestFormatFilter'])) {
			$formatsToShow = $_SESSION['materialsRequestFormatFilter'];
		} else {
			$formatsToShow = $defaultFormatsToShow;
		}
		$interface->assign('formatFilter', $formatsToShow);

		if (empty($_REQUEST['startDate'])) {
			$savedStartDate = new StickyFilter();
			$savedStartDate->userId = $user->id;
			$savedStartDate->filterFor = "MaterialsRequest_StartDate";
			if ($savedStartDate->find(true)) {
				$_REQUEST['startDate'] = $savedStartDate->filterValue;
				$interface->assign('startDate', $savedStartDate->filterValue);
			}
		}

		if (empty($_REQUEST['endDate'])) {
			$savedEndDate = new StickyFilter();
			$savedEndDate->userId = $user->id;
			$savedEndDate->filterFor = "MaterialsRequest_EndDate";
			if ($savedEndDate->find(true)) {
				$_REQUEST['endDate'] = $savedEndDate->filterValue;
				$interface->assign('endDate', $savedEndDate->filterValue);
			}
		}

		//Get a list of all material requests for the user
		$allRequests = [];
		if ($user) {

			$materialsRequests = new MaterialsRequest();
			$materialsRequests->joinAdd(new Location(), "LEFT", 'location', 'holdPickupLocation', 'locationId');
			$materialsRequests->joinAdd(new MaterialsRequestStatus(), 'INNER', 'status', 'status', 'id');
			$materialsRequests->joinAdd(new User(), 'INNER', 'user', 'createdBy', 'id');
			$materialsRequests->joinAdd(new User(), 'LEFT', 'assignee', 'assignedTo', 'id');
			$materialsRequests->selectAdd();
			$materialsRequests->selectAdd('materials_request.*, status.description as statusLabel, location.displayName as location');

			//Need to limit to only requests submitted for the user's home location
			$locations = new Location();
			$locations->libraryId = $homeLibrary->libraryId;
			$locations->find();
			$locationsForLibrary = [];
			while ($locations->fetch()) {
				$locationsForLibrary[] = $locations->locationId;
			}

			if (count($locationsForLibrary) > 0) {
				$materialsRequests->whereAdd('user.homeLocationId IN (' . implode(', ', $locationsForLibrary) . ')');
			}

			if (count($availableStatuses) > count($statusesToShow)) {
				$materialsRequests->whereAddIn('status', $statusesToShow, false);
			}

			if (count($availableFormats) > count($formatsToShow)) {
				//At least one format is disabled
				$materialsRequests->whereAddIn('format', $formatsToShow, true);
			}

			if (!empty($assigneesToShow) || $showUnassigned) {
				$condition = $assigneesSql = '';
				if (!empty($assigneesToShow)) {
					foreach ($assigneesToShow as $assignee) {
						$assignee = trim($assignee);
						if (is_numeric($assignee)) {
							if (strlen($assigneesSql) > 0) {
								$assigneesSql .= ',';
							}
							$assigneesSql .= $assignee;
						}
					}
					$assigneesSql = "assignedTo IN ($assigneesSql)";
				}
				if ($assigneesSql && $showUnassigned) {
					$condition = "($assigneesSql OR assignedTo IS NULL OR assignedTo = 0)";
				} elseif ($assigneesSql) {
					$condition = $assigneesSql;
				} elseif ($showUnassigned) {
					$condition = '(assignedTo IS NULL OR assignedTo = 0)';
				}
				$materialsRequests->whereAdd($condition);
			}

			//Add filtering by date as needed
			if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0) {
				$startDate = strtotime($_REQUEST['startDate']);
				$materialsRequests->whereAdd("dateCreated >= $startDate");
				$interface->assign('startDate', $_REQUEST['startDate']);
			}
			if (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) {
				$endDate = strtotime($_REQUEST['endDate']);
				$materialsRequests->whereAdd("dateCreated <= $endDate");
				$interface->assign('endDate', $_REQUEST['endDate']);
			}

			if (isset($_REQUEST['idsToShow']) && strlen($_REQUEST['idsToShow']) > 0) {
				$idsToShow = $_REQUEST['idsToShow'];
				$ids = explode(',', $idsToShow);
				$formattedIds = '';
				foreach ($ids as $id) {
					$id = trim($id);
					if (is_numeric($id)) {
						if (strlen($formattedIds) > 0) {
							$formattedIds .= ',';
						}
						$formattedIds .= $id;
					}
				}
				$materialsRequests->whereAdd("materials_request.id IN ($formattedIds)");
				$interface->assign('idsToShow', $idsToShow);
			}

			if (isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize']) || $_REQUEST['pageSize'] == 'all')) {
				$materialsRequestsPerPage =  $_REQUEST['pageSize'];
				PageDefaults::updatePageDefaultsForUser($user->id, 'MaterialsRequest', 'ManageRequests',null, $materialsRequestsPerPage, null);
			} else {
				$pageDefaults = PageDefaults::getPageDefaultsForUser($user->id, 'MaterialsRequest', 'ManageRequests',null);
				if ($pageDefaults !== null && !empty($pageDefaults->pageSize)) {
					$materialsRequestsPerPage =  $pageDefaults->pageSize;
				}else{
					$materialsRequestsPerPage = 30;
				}
			}
			if($materialsRequestsPerPage == 'all') {
				$materialsRequestsPerPage = $materialsRequests->count();
				$interface->assign('showingAllRequests', true);
			} else {
				$interface->assign('showingAllRequests', false);
			}
			$interface->assign('materialsRequestsPerPage', $materialsRequestsPerPage);
			$page = $_REQUEST['page'] ?? 1;
			if (!isset($_REQUEST['exportAll'])) {
				$materialsRequests->limit(($page - 1) * $materialsRequestsPerPage, $materialsRequestsPerPage);
			}
			$materialsRequestCount = $materialsRequests->count();

			if ($materialsRequests->find()) {
				$allRequests = $materialsRequests->fetchAll();
			}

			$options = [
				'totalItems' => $materialsRequestCount,
				'perPage' => $materialsRequestsPerPage,
			];

			$pager = new Pager($options);

			$interface->assign('pageLinks', $pager->getLinks());

			//Get a list of other users that are materials request users for this library
			$permission = new Permission();
			$permission->name = 'Manage Library Materials Requests';
			if ($permission->find(true)) {
				//Get roles for the user
				$rolePermissions = new RolePermissions();
				$rolePermissions->permissionId = $permission->id;
				$rolePermissions->find();
				$assignees = [];
				while ($rolePermissions->fetch()) {
					// Get Available Assignees
					$materialsRequestManagers = new User();
					if (count($locationsForLibrary) > 0) {
						//Get all user that can manage material requests based on their home library as well ad additional locations to manage
						if ($materialsRequestManagers->query("SELECT * from user WHERE id IN (SELECT userId FROM user_roles WHERE roleId = $rolePermissions->roleId) AND ((id IN (SELECT userId from user_administration_locations WHERE locationId IN (" . implode(', ', $locationsForLibrary) . "))) OR homeLocationId IN (" . implode(', ', $locationsForLibrary) . "))")) {
							while ($materialsRequestManagers->fetch()) {
								if (empty($materialsRequestManagers->displayName)) {
									$assignees[$materialsRequestManagers->id] = $materialsRequestManagers->firstname . ' ' . $materialsRequestManagers->lastname;
								} else {
									$assignees[$materialsRequestManagers->id] = $materialsRequestManagers->getDisplayName();
								}
							}
						}
					}
				}
				$interface->assign('assignees', $assignees);
			}

			$updateMessage = '';
			$updateMessageIsError = false;
			if (!empty($user->updateMessage)) {
				$updateMessage = $user->updateMessage;
				$updateMessageIsError = $user->updateMessageIsError;
				$user->updateMessage = '';
				$user->updateMessageIsError = 0;
				$user->update();
			}
			$interface->assign('updateMessage', $updateMessage);
			$interface->assign('updateMessageIsError', $updateMessageIsError);
		} else {
			$interface->assign('error', "You must be logged in to manage requests.");
		}
		$interface->assign('allRequests', $allRequests);

		$materialsRequestFieldsToDisplay = new MaterialsRequestFieldsToDisplay();
		$materialsRequestFieldsToDisplay->libraryId = $homeLibrary->libraryId;
		$materialsRequestFieldsToDisplay->orderBy('weight');
		if ($materialsRequestFieldsToDisplay->find() && $materialsRequestFieldsToDisplay->getNumResults() > 0) {
			$columnsToDisplay = $materialsRequestFieldsToDisplay->fetchAll('columnNameToDisplay', 'labelForColumnToDisplay');
		} else {
			$columnsToDisplay = $this->defaultColumnsToShow();
		}
		$interface->assign('columnsToDisplay', $columnsToDisplay);

		// Find Date Columns for Javascript Table sorter
		$dateColumns = [];
		foreach (array_keys($columnsToDisplay) as $index => $column) {
			if (in_array($column, [
				'dateCreated',
				'dateUpdated',
			])) {
				$dateColumns[] = $index;
			}
		}
		$interface->assign('dateColumns', $dateColumns); //data gets added within template

		if (isset($_REQUEST['exportSelected']) || isset($_REQUEST['exportAll'])) {
			$selectedRequests = $_REQUEST['select'] ?? [];
			$this->exportToExcel($selectedRequests, $allRequests, isset($_REQUEST['exportAll']));
		} else {
			$this->display('manageRequests.tpl', 'Manage Materials Requests');
		}
	}

	function defaultColumnsToShow() : array {
		return [
			'id' => 'Id',
			'source' => 'Source',
			'title' => 'Title',
			'author' => 'Author',
			'format' => 'Format',
			'createdBy' => 'Patron',
			'placeHoldWhenAvailable' => 'Place a Hold',
			'illItem' => 'Inter-Library Loan',
			'assignedTo' => 'Assigned To',
			'status' => 'Status',
			'dateCreated' => 'Created On',
			'dateUpdated' => 'Updated On',
		];
	}

	function exportToExcel(array $selectedRequestIds, array $allRequests, bool $exportAllRequests) : void {
		try {
			//May need more time to export all records
			set_time_limit(600);

			//Output to the browser
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment;filename="MaterialRequests.csv"');
			$fp = fopen('php://output', 'w');

			$fields = array('ID', 'Title', 'Season', 'Magazine', 'Author', 'Format', 'Sub Format', 'Type', 'Age Level', 'ISBN', 'UPC',
				'ISSN', 'OCLC Number', 'Publisher', 'Publication Year', 'Abridged', 'How did you hear about this?', 'Comments', 'Name',
				'Barcode', 'Email', 'Hold', 'Hold Pickup Location', 'ILL', 'Status', 'Staff Comments', 'Email Sent', 'Date Created', 'Assigned To');
			fputcsv($fp, $fields);

			//Loop Through The Report Data
			/** @var MaterialsRequest $request */
			foreach ($allRequests as $request) {
				if ($exportAllRequests || array_key_exists($request->id, $selectedRequestIds)) {

					$id = $request->id;
					$title = $request->title;
					$season = $request->season;

					$magazineInfo = '';
					if ($request->magazineTitle) {
						$magazineInfo .= $request->magazineTitle . ' ';
					}
					if ($request->magazineDate) {
						$magazineInfo .= $request->magazineDate . ' ';
					}
					if ($request->magazineVolume) {
						$magazineInfo .= 'volume ' . $request->magazineVolume . ' ';
					}
					if ($request->magazineNumber) {
						$magazineInfo .= 'number ' . $request->magazineNumber . ' ';
					}
					if ($request->magazinePageNumbers) {
						$magazineInfo .= 'p. ' . $request->magazinePageNumbers . ' ';
					}
					$magazine = trim($magazineInfo);

					$author = $request->author;
					$format = $request->format;
					$subFormat = $request->subFormat;
					$type = $request->bookType;
					$ageLevel = $request->ageLevel;
					$isbn = $request->isbn;
					$upc = $request->upc;
					$issn = $request->issn;
					$oclcNum = $request->oclcNumber;
					$publisher = $request->publisher;
					$pubYear = $request->publicationYear;
					$abridged = $request->abridged == 0 ? 'Unabridged' : ($request->abridged == 1 ? 'Abridged' : 'Not Applicable');
					$about = $request->about;
					$comments = $request->comments;
					$name = $request->getCreatedByLastName() . ', ' . $request->getCreatedByFirstName();
					$barcode = $request->getCreatedByUser()->getBarcode();
					$email = $request->getCreatedByUser()->email;

					// Place hold?
					if ($request->placeHoldWhenAvailable == 1) {
						$value = 'Yes';
					} else {
						$value = 'No';
					}
					$hold = $value;

					// Hold pickup location, including bookmobile stop if appropriate
					if ($request->holdPickupLocation) {
						$value = $request->getHoldLocationName($request->holdPickupLocation);
						if ($request->bookmobileStop) {
							$value .= ' ' . $request->bookmobileStop;
						}
					} else {
						$value = '';
					}
					$holdPULoc = $value;

					// Place ILL request?
					if ($request->illItem == 1) {
						$value = 'Yes';
					} else {
						$value = 'No';
					}
					$ill = $value;

					// Status
					$materialsRequestStatus = new MaterialsRequestStatus();
					$materialsRequestStatus->libraryId = $request->libraryId;
					$materialsRequestStatus->id = $request->status;
					if ($materialsRequestStatus->find(true)) {
						$status = translate([
							'text' => $materialsRequestStatus->description,
							'isPublicFacing' => true,
							'isMetadata' => true,
						]);
					} else {
						$status = translate([
							'text' => 'Request Status ID ' . $request->status . ' [description not found]',
							'isPublicFacing' => true,
							'isMetadata' => true,
						]);
					}

					// Staff Comments
					$staffComm = $request->staffComments;

					// Email sent?
					if ($request->emailSent == 1) {
						$value = 'Yes';
					} else {
						$value = 'No';
					}
					$emailSent = $value;

					// Date Created
					$dateCreated = date('m/d/Y', $request->dateCreated);

					// Assigned to
					if ($request->getAssigneeUser() !== false) {
						$assigned = $request->getAssigneeUser()->displayName;
					} else {
						$assigned = translate([
							'text' => 'Unassigned',
							'isAdminFacing' => true,
						]);
					}
					$row = array ($id, $title, $season, $magazine, $author, $format, $subFormat, $type, $ageLevel, $isbn, $upc, $issn, $oclcNum, $publisher,
						$pubYear, $abridged, $about, $comments, $name, $barcode, $email, $hold, $holdPULoc, $ill, $status, $staffComm, $emailSent, $dateCreated, $assigned);
					fputcsv($fp, $row);
				}
			}
			exit();
		} catch (Exception $e) {
			global $logger;
			$logger->log("Unable to create csv file " . $e, Logger::LOG_ERROR);
			return;
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Manage Library Materials Requests');
	}
}
