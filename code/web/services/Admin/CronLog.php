<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once(ROOT_DIR . "/PHPExcel.php");

class CronLog extends Admin_Admin
{
	function launch()
	{
		global $interface,
		       $configArray;

		$logEntries = array();
		$cronLogEntry = new CronLogEntry();
		$total = $cronLogEntry->count();
		$cronLogEntry = new CronLogEntry();
		$cronLogEntry->orderBy('startTime DESC');
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$interface->assign('page', $page);
		$cronLogEntry->limit(($page - 1) * 30, 30);
		$cronLogEntry->find();
		while ($cronLogEntry->fetch()){
			$logEntries[] = clone($cronLogEntry);
		}
		$interface->assign('logEntries', $logEntries);

		$options = array('totalItems' => $total,
		                 'fileName'   => $configArray['Site']['path'].'/Admin/CronLog?page=%d',
		                 'perPage'    => 30,
		);
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('cronLog.tpl', 'Cron Log');
	}

	function getAllowableRoles(){
		return array('opacAdmin');
	}
}
