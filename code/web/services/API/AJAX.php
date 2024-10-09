<?php
require_once ROOT_DIR . '/JSON_Action.php';

class API_AJAX extends JSON_Action {
	public function exportUsageData() {
		require_once ROOT_DIR . '/services/API/UsageGraphs.php';
		$aspenUsageGraph = new API_UsageGraphs();
		$aspenUsageGraph->buildCSV();
	}
}