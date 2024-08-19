<?php
require_once ROOT_DIR . '/JSON_Action.php';
class Summon_AJAX extends JSON_Action {
public function exportUsageData() {
		require_once ROOT_DIR . '/services/Summon/UsageGraphs.php';
		$summonUsageGraph = new Summon_UsageGraphs(); 
		$summonUsageGraph->buildCSV();
	}
}