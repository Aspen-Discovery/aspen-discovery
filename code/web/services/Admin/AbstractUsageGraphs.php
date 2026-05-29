<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
abstract class Admin_AbstractUsageGraphs extends Admin_Admin {

	// method specific enough to be worth writing an implementation for per section
	abstract function getBreadcrumbs(): array;
	abstract function getActiveAdminSection(): string;
	abstract protected function assignGraphSpecificTitle(string $stat): void;
	abstract protected function getAndSetInterfaceDataSeries(string $stat, string $instanceName): void;

	// methods shared amongst all usagegraph classes
	protected function launchGraph(string $sectionName): void {
		global $interface;

		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		$profileName = $_REQUEST['profileName'] ?? '';

		// includes dashboard subsection name in title if relevant
		$subSectionName = $_REQUEST['subSection'] ?? '';
		$sectionTitle = $sectionName;
		if (!empty($subSectionName)) {
			$sectionTitle .= ': ' . $subSectionName;
		}
		$sectionTitle .= ' Usage Graph';

		$interface->assign('stat', $stat);
		$interface->assign('section', $sectionName);
		$interface->assign('subSection', $subSectionName);
		$interface->assign('graphTitle', $sectionTitle);
		$interface->assign('showCSVExportButton', true);
		$interface->assign('propName', 'exportToCSV');
		$interface->assign('profileName', $profileName);
		$interface->assign('instance', $instanceName);

		$this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		
		$graphTitle = $interface->getVariable('graphTitle');
		$this->display('../Admin/usage-graph.tpl', $graphTitle);
	}

	public function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	public function buildCSV(string $section): void {
		global $interface;

		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$dataSeries = $interface->getVariable('dataSeries');

		// ensures csv filename contains dashboard subsection name if relevant
		$subSectionName = str_replace(' ', '_', $_REQUEST['subSection'] ?? '');
		$filename = $section . '_';
		if (!empty($subSectionName)) {
			$filename .=  $subSectionName . '_'; 
		}
		$filename .= 'UsageData_' .  $stat . '.csv';

		// sets up the csv file
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Content-Type: text/csv; charset=utf-8');
		header("Content-Disposition: attachment;filename={$filename}");
		$fp = fopen('php://output', 'w');

		// builds the file content
		$graphTitles = array_keys($dataSeries);
		$numGraphTitles = count($dataSeries);

		for($i = 0; $i < $numGraphTitles; $i++) {
			// builds the header for each section of the table in the CSV - column headers: Dates, and the title of the graph
			$dataSerie = $dataSeries[$graphTitles[$i]];
			$numRows = count($dataSerie['data']);
			$dates = array_keys($dataSerie['data']);
			$header = ['Dates', $graphTitles[$i]];
			fputcsv($fp, $header);

				// builds each subsequent data row - aka the column value
				if (empty($numRows)) {
					fputcsv($fp, ['no data found']);
				}
				for($j = 0; $j < $numRows; $j++) {
					$date = $dates[$j];
					$value = $dataSerie['data'][$date];
					$row = [$date, $value];
					fputcsv($fp, $row);
				}
		}
		exit();
	}
}