<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadLogEntry.php';

class SideLoads_IndexingLog extends Admin_Admin
{
	function launch()
	{
		global $interface;

		$logEntries = array();
		$logEntry = new SideLoadLogEntry();
		$total = $logEntry->count();
		$logEntry = new SideLoadLogEntry();
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
		                 'perPage'    => 30,
		);
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('sideLoadLog.tpl', 'Side Load Processing Log');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'cataloging', 'libraryManager');
	}
}
