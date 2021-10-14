<?php

require_once ROOT_DIR . '/Action.php';

global $configArray;

class CheckInGrid extends Action {
	function launch()
	{
		global $interface;

		require_once(ROOT_DIR . '/Drivers/Marmot.php');
		$driver = CatalogFactory::getCatalogConnectionInstance();
		$checkInGrid = $driver->getCheckInGrid(strip_tags($_REQUEST['id']), strip_tags($_REQUEST['lookfor']));
		$interface->assign('checkInGrid', $checkInGrid);

		$results = array(
				'title' => 'Check-In Grid',
				'modalBody' => $interface->fetch('Record/checkInGrid.tpl'),
				'modalButtons' => ""
		);
		echo json_encode($results);
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}