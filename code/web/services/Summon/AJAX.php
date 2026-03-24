<?php
require_once ROOT_DIR . '/JSON_Action.php';
class Summon_AJAX extends JSON_Action {
	function launch($method = null): void {
		$this->checkRequiredModule('Summon');
		parent::launch($method);
	}

	/** @noinspection PhpUnused */
	public function exportUsageData() : void {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['View Dashboards', 'View System Reports']);

		require_once ROOT_DIR . '/services/Summon/UsageGraphs.php';
		$summonUsageGraph = new Summon_UsageGraphs(); 
		$summonUsageGraph->buildCSV('Summon');
	}
}