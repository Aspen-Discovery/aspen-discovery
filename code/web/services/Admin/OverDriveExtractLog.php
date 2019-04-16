<?php

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
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('overdriveExtractLog.tpl', 'OverDrive Extract Log');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}
}
