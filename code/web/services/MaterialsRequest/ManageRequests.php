<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/MaterialsRequest.php');
require_once(ROOT_DIR . '/sys/MaterialsRequestStatus.php');

class MaterialsRequest_ManageRequests extends Admin_Admin {

	function launch()
	{
		global $interface;

		//Load status information
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->orderBy('isDefault DESC, isOpen DESC, description ASC');
		$homeLibrary = Library::getPatronHomeLibrary();
		$user = UserAccount::getLoggedInUser();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}

		$materialsRequestStatus->libraryId = $homeLibrary->libraryId;
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
		}
		$interface->assign('assigneesFilter', $assigneesToShow);
		$showUnassigned = !empty($_REQUEST['showUnassigned']) && $_REQUEST['showUnassigned'] == 'on';
		$interface->assign('showUnassigned', $showUnassigned);

		//Process status change if needed
		if (isset($_REQUEST['newStatus']) && isset($_REQUEST['select']) && $_REQUEST['newStatus'] != 'unselected'){
			//Look for which titles should be modified
			$selectedRequests = $_REQUEST['select'];
			$statusToSet = $_REQUEST['newStatus'];
			foreach ($selectedRequests as $requestId => $selected){
				$materialRequest = new MaterialsRequest();
				$materialRequest->id = $requestId;
				if ($materialRequest->find(true)){
					if ($materialRequest->status != $statusToSet) {
						$materialRequest->status = $statusToSet;
						$materialRequest->dateUpdated = time();
						$materialRequest->update();

						$materialRequest->sendStatusChangeEmail();
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
			$locationsForLibrary = array();
			while ($locations->fetch()){
				$locationsForLibrary[] = $locations->locationId;
			}

			$materialsRequests->whereAdd('user.homeLocationId IN (' . implode(', ', $locationsForLibrary) . ')');

			if (count($availableStatuses) > count($statusesToShow)){
				$statusSql = "";
				foreach ($statusesToShow as $status){
					if (strlen($statusSql) > 0) $statusSql .= ",";
					$statusSql .=  $materialsRequests->escape($status);
				}
				$materialsRequests->whereAdd("status in ($statusSql)");
			}

			if (count($availableFormats) > count($formatsToShow)){
				//At least one format is disabled
				$formatSql = "";
				foreach ($formatsToShow as $format){
					if (strlen($formatSql) > 0) $formatSql .= ",";
					$formatSql .= $materialsRequests->escape($format);
				}
				$materialsRequests->whereAdd("format in ($formatSql)");
			}

			if (!empty($assigneesToShow) || $showUnassigned) {
				$condition = $assigneesSql = '';
				if (!empty($assigneesToShow)) {
					foreach ($assigneesToShow as $assignee) {
						$assignee = trim($assignee);
						if (is_numeric($assignee)) {
							if (strlen($assigneesSql) > 0) $assigneesSql .= ',';
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
					$id = trim($id);
					if (is_numeric($id)) {
						if (strlen($formattedIds) > 0) $formattedIds .= ',';
						$formattedIds .= $id;
					}
				}
				$materialsRequests->whereAdd("materials_request.id IN ($formattedIds)");
				$interface->assign('idsToShow', $idsToShow);
			}

			if ($materialsRequests->find()) {
				$allRequests = $materialsRequests->fetchAll();
			}

			//Get a list of other users that are materials request users for this library
			$permission = new Permission();
			$permission->name = 'Manage Library Materials Requests';
			if ($permission->find(true)){
				//Get roles for the user
				$rolePermissions = new RolePermissions();
				$rolePermissions->permissionId = $permission->id;
				$rolePermissions->find();
				$assignees = array();
				while ($rolePermissions->fetch()){
					// Get Available Assignees
					$materialsRequestManagers = new User();

					if ($materialsRequestManagers->query("SELECT id, displayName from user WHERE id IN (SELECT userId FROM user_roles WHERE roleId = {$rolePermissions->roleId}) AND homeLocationId IN (" . implode(', ', $locationsForLibrary) . ")")){

						while ($materialsRequestManagers->fetch()){
							$assignees[$materialsRequestManagers->id] = $materialsRequestManagers->displayName;
						}
					}
				}
				$interface->assign('assignees', $assignees);
			}
		}else{
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
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Sub Format');

		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Type');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Age Level');
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

		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Hold');
		$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'ILL');

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
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->subFormat);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->bookType);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->ageLevel);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->isbn);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->upc);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->issn);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->oclcNumber);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->publisher);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->publicationYear);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->abridged == 0 ? 'Unabridged' : ($request->abridged == 1 ? 'Abridged' : 'Not Applicable'));
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->about);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->comments);
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->getCreatedByLastName() . ', ' . $request->getCreatedByFirstName());
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->getCreatedByUser()->getBarcode());
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $request->getCreatedByUser()->email);
				if ($request->placeHoldWhenAvailable == 1){
					$value = 'Yes ' . $request->holdPickupLocation;
					if ($request->bookmobileStop){
						$value .= ' ' . $request->bookmobileStop;
					}
				}else{
					$value = 'No';
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $value);
				if ($request->illItem == 1){
					$value = 'Yes';
				}else{
					$value = 'No';
				}
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, $value);

				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, translate(['text'=>$request->status,'isPublicFacing'=>true,'isMetadata'=>true]));
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

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'materials_request';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Manage Library Materials Requests');
	}
}
