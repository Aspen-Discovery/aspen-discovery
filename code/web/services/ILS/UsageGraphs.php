<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';

class ILS_UsageGraphs extends Admin_Admin {
	function launch() {
		global $interface;
		global $enabledModules;
		global $library;
		$title = 'ILS Usage Graph';
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}

		$dataSeries = [];
		$columnLabels = [];
				
		switch ($stat) {
			case 'userLogins':
				$title .= ' - User Logins';
				break;
			case 'selfRegistrations':
				$title .= ' - Self Registrations';
				break;
			case 'usersWithHolds':
				$title .= ' - Users Who Placed At Least One Hold';
				break;
			case 'recordsHeld':
				$title .= ' - Records Held';
				break;
			case 'totalHolds':
				$title .= ' - Total Holds';
				break;
			case 'usersWithPdfDownloads': 
				$title .= ' - Users Who Downloaded At Least One PDF';
				break;
			case 'usersWithPdfViews':
				$title .= ' - Users Who Viewed At Least One PDF';
				break;
			case 'pdfsDownloaded':
				$title .= ' - PDFs Downloaded';
				break;
			case 'pdfsViewed':
				$title .= ' - PDFs Viewed';
				break;
			case 'usersWithSupplementalFileDownloads':
				$title .= ' - Users Who Downloaded At Least One Supplemental File';
				break;
			case 'supplementalFilesDownloaded':
				$title .= ' - Supplemental Files Downloaded';
				break;
		}
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);

		$interface->assign('graphTitle', $title);
		$this->display('usage-graph.tpl', $title);
	}
}