<?php

require_once ROOT_DIR . '/Action.php';

global $configArray;

class CheckInGrid extends Action {
	function launch() {
		global $interface;

		/** @var Sierra $driver */
		require_once(ROOT_DIR . '/Drivers/Sierra.php');
		$driver = CatalogFactory::getCatalogConnectionInstance();
		$checkInGrid = $driver->getCheckInGrid(strip_tags($_REQUEST['id']), strip_tags($_REQUEST['lookfor']));
		$interface->assign('checkInGrid', $checkInGrid);

		$results = [
			'title' => 'Check-In Grid',
			'modalBody' => $interface->fetch('Record/checkInGrid.tpl'),
			'modalButtons' => "",
		];
		echo json_encode($results);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}