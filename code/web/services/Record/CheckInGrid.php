<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

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
}