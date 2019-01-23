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

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequestStatus.php');

class MaterialsRequest_ManageRequests extends Admin_Admin {

	/**
	 *
	 */
	function launch()
	{
		global $configArray;
		global $interface;

		//Load status information
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
		$homeLibrary = Library::getPatronHomeLibrary();
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('library_material_requests')){
			$materialsRequestStatus->libraryId = $homeLibrary->libraryId;
		}else{
			$libraryList[-1] = 'Default';
		}
		$materialsRequestStatus->find();

		$allStatuses = array();
		$availableStatuses = array();
		$defaultStatusesToShow = array();
		while ($materialsRequestStatus->fetch()){
			$availableStatuses[$materialsRequestStatus->id] = $materialsRequestStatus->description;
			$allStatuses[$materialsRequestStatus->id] = clone $materialsRequestStatus;
			if ($materialsRequestStatus->isOpen == 1 || $materialsRequestStatus->isDefault == 1){
				$defaultStatusesToShow[] = $materialsRequestStatus->id;
			}
		}
		$interface->assign('availableStatuses', $availableStatuses);

		if (isset($_REQUEST['statusFilter'])){
			$statusesToShow = $_REQUEST['statusFilter'];
			$_SESSION['materialsRequestStatusFilter'] = $statusesToShow;
		}elseif (isset($_SESSION['materialsRequestStatusFilter'])){
			$statusesToShow = $_SESSION['materialsRequestStatusFilter'];
		}else{
			$statusesToShow = $defaultStatusesToShow;
		}
		$interface->assign('statusFilter', $statusesToShow);

		$assigneesToShow = array();
		if (isset($_REQUEST['assigneesFilter'])) {
			$assigneesToShow = $_REQUEST['assigneesFilter'];
//			$_SESSION['materialsRequestAssigneesFilter'] = $assigneesToShow;
//		} elseif (!empty($_SESSION['materialsRequestAssigneesFilter'])) {
//			$assigneesToShow = $_SESSION['materialsRequestAssigneesFilter'];
		}
		$interface->assign('assigneesFilter', $assigneesToShow);
		$showUnassigned = !empty($_REQUEST['showUnassigned']) && $_REQUEST['showUnassigned'] == 'on';
		$interface->assign('showUnassigned', $showUnassigned);

		//Process status change if needed
		if (isset($_REQUEST['newStatus']) && isset($_REQUEST['select']) && $_REQUEST['newStatus'] != 'unselected'){
			//Look for which titles should be modified
			$selectedRequests = $_REQUEST['select'];
			$statusToSet = $_REQUEST['newStatus'];
			require_once ROOT_DIR . '/sys/Mailer.php';
			$mail = new VuFindMailer();
			foreach ($selectedRequests as $requestId => $selected){
				$materialRequest = new MaterialsRequest();
				$materialRequest->id = $requestId;
				if ($materialRequest->find(true)){
					$materialRequest->status = $statusToSet;
					$materialRequest->dateUpdated = time();
					$materialRequest->update();

					if ($allStatuses[$statusToSet]->sendEmailToPatron == 1 && $materialRequest->email){
						$replyToAddress = $emailSignature = '';
						if (!empty($materialRequest->assignedTo)) {
							require_once ROOT_DIR . '/sys/Account/UserStaffSettings.php';
							$staffSettings = new UserStaffSettings();
							$staffSettings->get('userId', $materialRequest->assignedTo);
							if (!empty($staffSettings->materialsRequestReplyToAddress)) {
								$replyToAddress = $staffSettings->materialsRequestReplyToAddress;
							}
							if (!empty($staffSettings->materialsRequestEmailSignature)) {
								$emailSignature = $staffSettings->materialsRequestEmailSignature;
							}
						}

						$body = '*****This is an auto-generated email response. Please do not reply.*****';
						$body .= "\r\n\r\n" . $allStatuses[$statusToSet]->emailTemplate;

						if (!empty($emailSignature)) {
							$body .= "\r\n\r\n" .$emailSignature;
						}

						//Replace tags with appropriate values
						$materialsRequestUser = new User();
						$materialsRequestUser->id = $materialRequest->createdBy;
						$materialsRequestUser->find(true);
						foreach ($materialsRequestUser as $fieldName => $fieldValue){
							if (!is_array($fieldValue)){
								$body = str_replace('{' . $fieldName . '}', $fieldValue, $body);
							}
						}
						foreach ($materialRequest as $fieldName => $fieldValue){
							if (!is_array($fieldValue)){
								$body = str_replace('{' . $fieldName . '}', $fieldValue, $body);
							}
						}
						$error = $mail->send($materialRequest->email, $configArray['Site']['email'], "Your Materials Request Update", $body, $replyToAddress);
						if (PEAR_Singleton::isError($error)) {
							$interface->assign('error', $error->message);
						}
					}
				}
			}
		}


		// Assign Requests
		if (isset($_REQUEST['newAssignee']) && isset($_REQUEST['select']) && $_REQUEST['newAssignee'] != 'unselected'){
			//Look for which material requests should be modified
			$selectedRequests = $_REQUEST['select'];
			$assignee = $_REQUEST['newAssignee'];
			if (ctype_digit($assignee) || $assignee == 'unassign') {
				foreach ($selectedRequests as $requestId => $selected){
					$materialRequest = new MaterialsRequest();
					$materialRequest->id = $requestId;
					if ($materialRequest->find(true)){
						$materialRequest->assignedTo = $assignee == 'unassign' ? 'null' : $assignee;
						$materialRequest->dateUpdated = time();
						$materialRequest->update();

						//TODO: Email Assignee of the request?

					}
				}
			} else {
				$interface->assign('error', 'User to assign the request to was not valid.');
			}
		}

		$availableFormats = MaterialsRequest::getFormats();
		$interface->assign('availableFormats', $availableFormats);
		$defaultFormatsToShow = array_keys($availableFormats);
		if (isset($_REQUEST['formatFilter'])){
			$formatsToShow = $_REQUEST['formatFilter'];
			$_SESSION['materialsRequestFormatFilter'] = $formatsToShow;
		}elseif (isset($_SESSION['materialsRequestFormatFilter'])){
			$formatsToShow = $_SESSION['materialsRequestFormatFilter'];
		}else{
			$formatsToShow = $defaultFormatsToShow;
		}
		$interface->assign('formatFilter', $formatsToShow);

		//Get a list of all materials requests for the user
		$allRequests = array();
		if ($user){

			$materialsRequests = new MaterialsRequest();
			$materialsRequests->joinAdd(new Location(), "LEFT");
			$materialsRequests->joinAdd(new MaterialsRequestStatus());
			$materialsRequests->joinAdd(new User(), 'INNER', 'user', 'createdBy');
			$materialsRequests->joinAdd(new User(), 'LEFT', 'assignee', 'assignedTo');
			$materialsRequests->selectAdd();
			$materialsRequests->selectAdd('materials_request.*, description as statusLabel, location.displayName as location, user.firstname, user.lastname, user.' . $configArray['Catalog']['barcodeProperty'] . ' as barcode, assignee.displayName as assignedTo');
			if (UserAccount::userHasRole('library_material_requests')){
				//Need to limit to only requests submitted for the user's home location
				$userHomeLibrary = Library::getPatronHomeLibrary();
				$locations = new Location();
				$locations->libraryId = $userHomeLibrary->libraryId;
				$locations->find();
				$locationsForLibrary = array();
				while ($locations->fetch()){
					$locationsForLibrary[] = $locations->locationId;
				}

				$materialsRequests->whereAdd('user.homeLocationId IN (' . implode(', ', $locationsForLibrary) . ')');
			}

			if (count($availableStatuses) > count($statusesToShow)){
				$statusSql = "";
				foreach ($statusesToShow as $status){
					if (strlen($statusSql) > 0) $statusSql .= ",";
					$statusSql .= "'" . $materialsRequests->escape($status) . "'";
				}
				$materialsRequests->whereAdd("status in ($statusSql)");
			}

			if (count($availableFormats) > count($formatsToShow)){
				//At least one format is disabled
				$formatSql = "";
				foreach ($formatsToShow as $format){
					if (strlen($formatSql) > 0) $formatSql .= ",";
					$formatSql .= "'" . $materialsRequests->escape($format) . "'";
				}
				$materialsRequests->whereAdd("format in ($formatSql)");
			}

			if (!empty($assigneesToShow) || $showUnassigned) {
				$condition = $assigneesSql = '';
				if (!empty($assigneesToShow)) {
					foreach ($assigneesToShow as $assignee) {
						if (strlen($assigneesSql) > 0) $assigneesSql .= ',';
						$assigneesSql .= "'{$materialsRequests->escape($assignee)}'";
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
			if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0){
				$startDate = strtotime($_REQUEST['startDate']);
				$materialsRequests->whereAdd("dateCreated >= $startDate");
				$interface->assign('startDate', $_REQUEST['startDate']);
			}
			if (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0){
				$endDate = strtotime($_REQUEST['endDate']);
				$materialsRequests->whereAdd("dateCreated <= $endDate");
				$interface->assign('endDate', $_REQUEST['endDate']);
			}

			if (isset($_REQUEST['idsToShow']) && strlen($_REQUEST['idsToShow']) > 0){
				$idsToShow = $_REQUEST['idsToShow'];
				$ids = explode(',', $idsToShow);
				$formattedIds = '';
				foreach ($ids as $id){
					if (strlen($formattedIds) > 0) $formattedIds .= ',';
					$formattedIds .= "'" . trim($id) . "'";
				}
				$materialsRequests->whereAdd("materials_request.id IN ($formattedIds)");
				$interface->assign('idsToShow', $idsToShow);
			}

			if ($materialsRequests->find()) {
				$allRequests = $materialsRequests->fetchAll();
			}

			// $assignees used for both set assignee dropdown and filter by assigned To checkboxes
			// TODO: determine if There is a case where an non-materials request manager can filter.
			// opac_admins w/o materials_request role would expect to filter by assignee
			if (UserAccount::userHasRole('library_material_requests')) {
				$role = new Role();
				if ($role->get('name', 'library_material_requests')) {
					// Get Available Assignees
					$materialsRequestManagers = new User();

					require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
					$userRole         = new UserRoles();
					$userRole->roleId = $role->roleId;

					$materialsRequestManagers->joinAdd($userRole);
					$materialsRequestManagers->whereAdd('user.homeLocationId IN (' . implode(', ', $locationsForLibrary) . ')');
					$assignees = array();
					if ($materialsRequestManagers->find()) {
						$assignees = $materialsRequestManagers->fetchAll('id', 'displayName');
					}
					$interface->assign('assignees', $assignees);
				}
			}
		}else{
			$interface->assign('error', "You must be logged in to manage requests.");
		}
		$interface->assign('allRequests', $allRequests);

		$materialsRequestFieldsToDisplay = new MaterialsRequestFieldsToDisplay();
		$materialsRequestFieldsToDisplay->libraryId = $homeLibrary->libraryId;
		$materialsRequestFieldsToDisplay->orderBy('weight');
		if ($materialsRequestFieldsToDisplay->find() && $materialsRequestFieldsToDisplay->N > 0) {
			$columnsToDisplay = $materialsRequestFieldsToDisplay->fetchAll('columnNameToDisplay', 'labelForColumnToDisplay');
		} else {
			$columnsToDisplay = $this->defaultColumnsToShow();
		}
		$interface->assign('columnsToDisplay', $columnsToDisplay);

		// Find Date Columns for Javascript Table sorter
		$dateColumns = array();
		foreach (array_keys($columnsToDisplay) as $index => $column) {
			if (in_array($column, array('dateCreated', 'dateUpdated'))) {
				$dateColumns[] = $index;
			}
		}
		$interface->assign('dateColumns', $dateColumns); //data gets added within template

		if (isset($_REQUEST['exportSelected'])){
			$this->exportToExcel($_REQUEST['select'], $allRequests);
		}else{
			$this->display('manageRequests.tpl', 'Manage Materials Requests');
		}
	}

	function defaultColumnsToShow() {
		return array(
			'id'           => 'Id',
			'title'        => 'Title',
			'author'       => 'Author',
			'format'       => 'Format',
			'createdBy'    => 'Patron',
			'placeHoldWhenAvailable' => 'Place a Hold',
			'illItem'      => 'Inter-Library Loan',
			'assignedTo'   => 'Assigned To',
			'status'       => 'Status',
			'dateCreated'  => 'Created On',
			'dateUpdated'  => 'Updated On',
		);
	}

	function exportToExcel($selectedRequestIds, $allRequests){
		global $configArray;
		//May need more time to export all records
		set_time_limit(600);
		//PHPEXCEL
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("VuFind")
		->setLastModifiedBy("VuFind")
		->setTitle("Office 2007 XLSX Document")
		->setSubject("Office 2007 XLSX Document")
		->setDescription("Office 2007 XLSX, generated using PHP.")
		->setKeywords("office 2007 openxml php")
		->setCategory("Itemless eContent Report");

		// Add some data
		$activeSheet = $objPHPExcel->setActiveSheetIndex(0);
		$activeSheet->setCellValueByColumnAndRow(0, 1, 'Materials Requests');

		//Define table headers
		$curRow = 3;
		$curCol = 0;

		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ID');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Title');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Season');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Magazine');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Author');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Format');
		if ($configArray['MaterialsRequest']['showEbookFormatField']/* || $configArray['MaterialsRequest']['showEaudioFormatField']*/){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Sub Format');
		}
		if ($configArray['MaterialsRequest']['showBookTypeField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Type');
		}
		if ($configArray['MaterialsRequest']['showAgeField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Age Level');
		}
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ISBN');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'UPC');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ISSN');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'OCLC Number');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Publisher');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Publication Year');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Abridged');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'How did you hear about this?');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Comments');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Name');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Barcode');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Email');

		if ($configArray['MaterialsRequest']['showPlaceHoldField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Hold');
		}
		if ($configArray['MaterialsRequest']['showIllField']){
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ILL');
		}
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Status');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Date Created');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Assigned To');

		$numCols = $curCol;
		//Loop Through The Report Data
		/** @var MaterialsRequest $request */
		foreach ($allRequests as $request) {
			if (array_key_exists($request->id, $selectedRequestIds)){
				$curRow++;
				$curCol = 0;

				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->id);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->title);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->season);
				$magazineInfo = '';
				if ($request->magazineTitle){
					$magazineInfo .= $request->magazineTitle . ' ';
				}
				if ($request->magazineDate){
					$magazineInfo .= $request->magazineDate . ' ';
				}
				if ($request->magazineVolume){
					$magazineInfo .= 'volume ' . $request->magazineVolume . ' ';
				}
				if ($request->magazineNumber){
					$magazineInfo .= 'number ' . $request->magazineNumber . ' ';
				}
				if ($request->magazinePageNumbers){
					$magazineInfo .= 'p. ' . $request->magazinePageNumbers . ' ';
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, trim($magazineInfo));
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->author);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->format);
				if ($configArray['MaterialsRequest']['showEbookFormatField']/* || $configArray['MaterialsRequest']['showEaudioFormatField']*/){
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->subFormat);
				}
				if ($configArray['MaterialsRequest']['showBookTypeField']){
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->bookType);
				}
				if ($configArray['MaterialsRequest']['showAgeField']){
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->ageLevel);
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->isbn);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->upc);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->issn);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->oclcNumber);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->publisher);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->publicationYear);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->abridged == 0 ? 'Unabridged' : ($request->abridged == 1 ? 'Abridged' : 'Not Applicable'));
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->about);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->comments);
				$requestUser = new User();
				$requestUser->get($request->createdBy);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $requestUser->lastname . ', ' . $requestUser->firstname);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $requestUser->$configArray['Catalog']['barcodeProperty']);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $requestUser->email);
				if ($configArray['MaterialsRequest']['showPlaceHoldField']){
					if ($request->placeHoldWhenAvailable == 1){
						$value = 'Yes ' . $request->holdPickupLocation;
						if ($request->bookmobileStop){
							$value .= ' ' . $request->bookmobileStop;
						}
					}else{
						$value = 'No';
					}
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $value);
				}
				if ($configArray['MaterialsRequest']['showIllField']){
					if ($request->illItem == 1){
						$value = 'Yes';
					}else{
						$value = 'No';
					}
					$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $value);
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, translate($request->status));
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, date('m/d/Y', $request->dateCreated));
				$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, $request->assignedTo);
			}
		}

		for ($i = 0; $i < $numCols; $i++){
			$activeSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}

		// Rename sheet
		$activeSheet->setTitle('Materials Requests');

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename=MaterialsRequests.xls');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;
	}

	function getAllowableRoles(){
		return array('library_material_requests');
	}
}
