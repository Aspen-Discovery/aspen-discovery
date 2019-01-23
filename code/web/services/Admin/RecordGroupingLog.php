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

class RecordGroupingLog extends Admin_Admin
{
	function launch()
	{
		global $interface,
		       $configArray;

		$logEntries = array();
		$logEntry = new RecordGroupingLogEntry();
		$total = $logEntry->count();
		$logEntry = new RecordGroupingLogEntry();
		$logEntry->orderBy('startTime DESC');
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$pagesize = isset($_REQUEST['pagesize']) ? $_REQUEST['pagesize'] : 30; // to adjust number of items listed on a page
		$interface->assign('recordsPerPage', $pagesize);
		$interface->assign('page', $page);
		if (!empty($_REQUEST['worksLimit']) && ctype_digit($_REQUEST['worksLimit'])) {
			$logEntry->whereAdd('numWorksProcessed > '.$_REQUEST['worksLimit']);
		}
		$logEntry->limit(($page - 1) * $pagesize, $pagesize);
		$logEntry->find();
		while ($logEntry->fetch()){
			$logEntries[] = clone($logEntry);
		}
		$interface->assign('logEntries', $logEntries);

		$options = array('totalItems' => $total,
		                 'fileName'   => $configArray['Site']['path'].'/Admin/RecordGroupingLog?page=%d'. (empty($_REQUEST['worksLimit']) ? '' : '&worksLimit=' . $_REQUEST['worksLimit']). (empty($_REQUEST['pagesize']) ? '' : '&pagesize=' . $_REQUEST['pagesize']),
		                 'perPage'    => $pagesize,
		);
		$pager = new VuFindPager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$this->display('recordGroupingLog.tpl', 'Record Grouping Log');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'cataloging');
	}
}
