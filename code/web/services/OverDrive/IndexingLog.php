<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveExtractLogEntry.php';

class OverDrive_IndexingLog extends Admin_Admin
{
	function launch()
	{
		global $interface,
		       $configArray;

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
		                 'fileName'   => $configArray['Site']['path'].'/OverDrive/IndexingLog?page=%d',
		                 'perPage'    => 30,
		);
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('overdriveExtractLog.tpl', 'OverDrive Indexing Log');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'cataloging', 'libraryManager');
	}
}
