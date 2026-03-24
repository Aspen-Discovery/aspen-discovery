<?php
require_once ROOT_DIR . '/JSON_Action.php';
class ILS_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	public function exportUsageData() : void {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['View Dashboards', 'View System Reports']);

		require_once ROOT_DIR . '/services/ILS/UsageGraphs.php';
		$ILSUsageGraph = new ILS_UsageGraphs(); 
		$ILSUsageGraph->buildCSV('ILS');
	}
}