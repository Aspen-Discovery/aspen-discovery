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
require_once(ROOT_DIR . "/sys/pChart/class/pData.class.php");
require_once(ROOT_DIR . "/sys/pChart/class/pDraw.class.php");
require_once(ROOT_DIR . "/sys/pChart/class/pImage.class.php");
require_once(ROOT_DIR . "/PHPExcel.php");

class MaterialsRequest_SummaryReport extends Admin_Admin {

	function launch()
	{
		global $interface;

		$period = isset($_REQUEST['period']) ? $_REQUEST['period'] : 'week';
		if ($period == 'week'){
			$periodLength  = new DateInterval("P1W");
		}elseif ($period == 'day'){
			$periodLength = new DateInterval("P1D");
		}elseif ($period == 'month'){
			$periodLength = new DateInterval("P1M");
		}else{ //year
			$periodLength = new DateInterval("P1Y");
		}
		$interface->assign('period', $period);

		$endDate = (isset($_REQUEST['endDate']) && strlen($_REQUEST['endDate']) > 0) ? DateTime::createFromFormat('m/d/Y', $_REQUEST['endDate']) : new DateTime();
		$interface->assign('endDate', $endDate->format('m/d/Y'));

		if (isset($_REQUEST['startDate']) && strlen($_REQUEST['startDate']) > 0){
			$startDate = DateTime::createFromFormat('m/d/Y', $_REQUEST['startDate']);
		} else{
			if ($period == 'day'){
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 7 days");
			}elseif ($period == 'week'){
				//Get the sunday after this
				$endDate->setISODate($endDate->format('Y'), $endDate->format("W"), 0);
				$endDate->modify("+7 days");
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 28 days");
			}elseif ($period == 'month'){
				$endDate->modify("+1 month");
				$numDays = $endDate->format("d");
				$endDate->modify(" -$numDays days");
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 6 months");
			}else{ //year
				$endDate->modify("+1 year");
				$numDays = $endDate->format("m");
				$endDate->modify(" -$numDays months");
				$numDays = $endDate->format("d");
				$endDate->modify(" -$numDays days");
				$startDate = new DateTime($endDate->format('m/d/Y') . " - 2 years");
			}
		}

		$interface->assign('startDate', $startDate->format('m/d/Y'));

		//Set the end date to the end of the day
		$endDate->setTime(24, 0, 0);
		$startDate->setTime(0, 0, 0);

		//Create the periods that are being represented
		$periods = array();
		$periodEnd = clone $endDate;
		while ($periodEnd >= $startDate){
			array_unshift($periods, clone $periodEnd);
			$periodEnd->sub($periodLength);
		}
		//print_r($periods);

		//Load data for each period
		//this will be a two dimensional array
		//         Period 1, Period 2, Period 3
		//Status 1
		//Status 2
		//Status 3
		$periodData = array();

		$locationsToRestrictTo = '';
		$user = UserAccount::getLoggedInUser();
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
			$locationsToRestrictTo = implode(', ', $locationsForLibrary);

		}

		for ($i = 0; $i < count($periods) - 1; $i++){
			/** @var DateTime $periodStart */
			$periodStart = clone $periods[$i];
			/** @var DateTime $periodEnd */
			$periodEnd = clone $periods[$i+1];

			$periodData[$periodStart->getTimestamp()] = array();
			//Determine how many requests were created
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->joinAdd(new User(), 'INNER', 'user', 'createdBy');
			$materialsRequest->selectAdd();
			$materialsRequest->selectAdd('COUNT(materials_request.id) as numRequests');
			$materialsRequest->whereAdd('dateCreated >= ' . $periodStart->getTimestamp() . ' AND dateCreated < ' . $periodEnd->getTimestamp());
			if ($locationsToRestrictTo != ''){
				$materialsRequest->whereAdd('user.homeLocationId IN (' . $locationsToRestrictTo . ')');
			}

			$materialsRequest->find();
			while ($materialsRequest->fetch()){
				$periodData[$periodStart->getTimestamp()]['Created'] = $materialsRequest->numRequests;
			}

			//Get a list of all requests by the status of the request
			$materialsRequest = new MaterialsRequest();
			$materialsRequest->joinAdd(new MaterialsRequestStatus());
			$materialsRequest->joinAdd(new User(), 'INNER', 'user', 'createdBy');
			$materialsRequest->selectAdd();
			$materialsRequest->selectAdd('COUNT(materials_request.id) as numRequests,description');
			$materialsRequest->whereAdd('dateUpdated >= ' . $periodStart->getTimestamp() . ' AND dateUpdated < ' . $periodEnd->getTimestamp());
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

				$materialsRequest->whereAdd('user.homeLocationId IN (' . implode(', ', $locationsForLibrary) . ')');
			}
			$materialsRequest->groupBy('status');
			$materialsRequest->orderBy('status');
			$materialsRequest->find();
			while ($materialsRequest->fetch()){
				$periodData[$periodStart->getTimestamp()][$materialsRequest->description] = $materialsRequest->numRequests;
			}
		}

		$interface->assign('periodData', $periodData, $periods);

		//Get a list of all of the statuses that will be shown
		$statuses = array();
		foreach ($periodData as $periodInfo){
			foreach ($periodInfo as $status => $numRequests){
				$statuses[$status] = translate($status);
			}
		}
		$interface->assign('statuses', $statuses);

		//Check to see if we are exporting to Excel
		if (isset($_REQUEST['exportToExcel'])){
			$this->exportToExcel($periodData, $statuses);
		}else{
			//Generate the graph
			$this->generateGraph($periodData, $statuses);
		}

		$interface->setTemplate('summaryReport.tpl');
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setPageTitle('Materials Request Summary Report');
		$interface->display('layout.tpl');
	}

	function exportToExcel($periodData, $statuses){
		global $configArray;
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator($configArray['Site']['title'])
				->setLastModifiedBy($configArray['Site']['title'])
				->setTitle("Materials Request Summary Report")
				->setSubject("Materials Request")
				->setCategory("Materials Request Summary Report");

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setCellValue('A1', 'Materials Request Summary Report');
		$activeSheet->setCellValue('A3', 'Date');
		$column = 1;
		foreach ($statuses as $statusLabel){
			$activeSheet->setCellValueByColumnAndRow($column++, 3, $statusLabel);
		}

		$row = 4;
		$column = 0;
		//Loop Through The Report Data
		foreach ($periodData as $date => $periodInfo) {
			$activeSheet->setCellValueByColumnAndRow($column++, $row, date('M-d-Y', $date));
			foreach ($statuses as $status => $statusLabel){
				$activeSheet->setCellValueByColumnAndRow($column++, $row, isset($periodInfo[$status]) ? $periodInfo[$status] : 0);
			}
			$row++;
			$column = 0;
		}
		for ($i = 0; $i < count($statuses) + 1; $i++){
			$activeSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}

		// Rename sheet
		$activeSheet->setTitle('Summary Report');

		// Redirect output to a client's web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="MaterialsRequestSummaryReport.xls"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit;

	}

	function generateGraph($periodData, $statuses){
		global $configArray;
		global $interface;
		$reportData = new pData();

		//Add points for each status
		$periodsFormatted = array();
		foreach ($statuses as $status => $statusLabel){
			$statusData = array();
			foreach ($periodData as $date => $periodInfo){
				$periodsFormatted[$date] = date('M-d-Y', $date);
				$statusData[$date] = isset($periodInfo[$status]) ? $periodInfo[$status] : 0;
			}
			$reportData->addPoints($statusData, $status);
		}

		$reportData->setAxisName(0,"Requests");

		$reportData->addPoints($periodsFormatted, "Dates");
		$reportData->setAbscissa("Dates");

		/* Create the pChart object */
		$myPicture = new pImage(700,290,$reportData);

		/* Draw the background */
		$Settings = array("R"=>225, "G"=>225, "B"=>225);
		$myPicture->drawFilledRectangle(0,0,700,290,$Settings);

		/* Add a border to the picture */
		$myPicture->drawRectangle(0,0,699,289,array("R"=>0,"G"=>0,"B"=>0));

		$myPicture->setFontProperties(array("FontName"=> "sys/pChart/Fonts/verdana.ttf","FontSize"=>9));
		$myPicture->setGraphArea(50,30,670,190);
		//$myPicture->drawFilledRectangle(30,30,670,150,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10));
		$myPicture->drawScale(array("DrawSubTicks"=>TRUE, "LabelRotation"=>90));
		$myPicture->setFontProperties(array("FontName"=> "sys/pChart/Fonts/verdana.ttf","FontSize"=>9));
		$myPicture->drawLineChart(array("DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO));

		/* Write the chart legend */
		$myPicture->drawLegend(80,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

		/* Render the picture (choose the best way) */
		$chartHref = "/images/charts/materialsRequestSummary". time() . ".png";
		$chartPath = $configArray['Site']['local'] . $chartHref;
		$myPicture->render($chartPath);
		$interface->assign('chartPath', $chartHref);
	}

	function getAllowableRoles(){
		return array('library_material_requests');
	}
}
