<?php
require_once ROOT_DIR . '/JSON_Action.php';
class ILS_AJAX extends JSON_Action {
public function exportUsageData() {
		require_once ROOT_DIR . '/services/ILS/UsageGraphs.php';
		$ILSUsageGraph = new ILS_UsageGraphs(); 
		$ILSUsageGraph->buildCSV();
	}
}