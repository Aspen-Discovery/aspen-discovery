<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';

class OverDriveExtractLog extends Admin_Admin
{
	function launch()
	{
		global $interface,
		       $configArray;

		//Get the number of changes that are outstanding
		$overdriveProduct = new OverDriveAPIProduct();
		$overdriveProduct->needsUpdate = 1;
		$overdriveProduct->deleted = 0;
		$overdriveProduct->find();
		$numOutstandingChanges = $overdriveProduct->N;
		$interface->assign('numOutstandingChanges', $numOutstandingChanges);

		$logEntries = array();
		$logEntry = new OverDriveExtractLogEntry();
		$total = $logEntry->count();
		$logEntry = new OverDriveExtractLogEntry();
		$logEntry->orderBy('startTime DESC');
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $page);
		$logEntry->limit(($page - 1) * 30, 30);
		$logEntry->find();
		while ($logEntry->fetch()){
			$logEntries[] = clone($logEntry);
		}
		$interface->assign('logEntries', $logEntries);

		$options = array('totalItems' => $total,
		                 'fileName'   => $configArray['Site']['path'].'/Admin/OverDriveExtractLog?page=%d',
		                 'perPage'    => 30,
		);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('overdriveExtractLog.tpl', 'OverDrive Extract Log');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}
}
