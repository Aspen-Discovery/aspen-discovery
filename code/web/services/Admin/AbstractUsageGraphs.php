<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
abstract class UsageGraphs_UsageGraphs extends Admin_Admin {

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
		$sectionTitle = $sectionName . ' ' . 'Usage Graph';

		$interface->assign('stat', $stat);
		$interface->assign('section', $sectionName);
		$interface->assign('graphTitle', $sectionTitle);
		$interface->assign('showCSVExportButton', true);
		$interface->assign('propName', 'exportToCSV');

		$this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		
		$graphTitle = $interface->getVariable('graphTitle');
		$this->display('usage-graph.tpl', $graphTitle);
	}

}