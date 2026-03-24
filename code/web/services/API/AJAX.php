<?php
require_once ROOT_DIR . '/JSON_Action.php';

class API_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	public function exportUsageData(): void {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['View Dashboards', 'View System Reports']);
		require_once ROOT_DIR . '/services/API/UsageGraphs.php';
		$aspenUsageGraph = new API_UsageGraphs();
		$aspenUsageGraph->buildCSV('API');
	}
}