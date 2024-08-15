<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';

class ILS_UsageGraphs extends Admin_Admin {
	function launch() {
		global $interface;
		$title = 'ILS Usage Graph';
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}

		$interface->assign('graphTitle', $title);
		$this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$interface->assign('stat', $stat);
		$this->display('usage-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/Dashboard', 'Usage Dashboard');
		$breadcrumbs[] = new Breadcrumb('', 'Usage Graph');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ils_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View Dashboards',
			'View System Reports',
		]);
	}

	// note that this will only handle tables with one stat (as is needed for Summon usage data)
	// to see a version that handle multpile stats, see the Admin/UsageGraphs.php implementation
	public function buildCSV() {
		global $interface;
		$stat = $_REQUEST['stat'];
		if (!empty($_REQUEST['instance'])) {
			$instanceName = $_REQUEST['instance'];
		} else {
			$instanceName = '';
		}
		$this->getAndSetInterfaceDataSeries($stat, $instanceName);
		$dataSeries = $interface->getVariable('dataSeries');

		$filename = "ILSUsageData_{$stat}.csv";
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Content-Type: text/csv; charset=utf-8');
		header("Content-Disposition: attachment;filename={$filename}");
		$fp = fopen('php://output', 'w');
		
		// builds the first row of the table in the CSV - column headers: Dates, and the title of the graph
		fputcsv($fp, ['Dates', $stat]);

		// builds each subsequent data row - aka the column value
		foreach ($dataSeries as $dataSerie) {
			$data = $dataSerie['data'];
			$numRows = count($data);
			$dates = array_keys($data);

			for($i = 0; $i < $numRows; $i++) {
				$date = $dates[$i];
				$value = $data[$date];
				$row = [$date, $value];
				fputcsv($fp, $row);
			}
		}
		exit();
	}

	private function getAndSetInterfaceDataSeries($stat, $instanceName) {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];

		// for graphs displaying data retrieved from the user_ils_usage table
		if (
			$stat == 'userLogins' ||
			$stat == 'selfRegistrations' ||
			$stat == 'usersWithPdfDownloads' ||
			$stat == 'usersWithPdfViews' ||
			$stat == 'usersWithSupplementalFileDownloads' ||
			$stat == 'usersWithHolds'
		) {
			$userILSUsage = new UserILSUsage();
			$userILSUsage->groupBy('year, month');
			if (!empty($instanceName)) {
				$userILSUsage->instance = $instanceName;
			}
			$userILSUsage->selectAdd();
			$userILSUsage->selectAdd('year');
			$userILSUsage->selectAdd('month');
			$userILSUsage->orderBy('year, month');
			
			if ($stat == 'userLogins') {
				$dataSeries['User Logins'] = [
					'borderColor' => 'rgba(255, 99, 132, 1)',
					'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
					'data' => [],
				];
				$userILSUsage->selectAdd('SUM(usageCount) as sumUserLogins');
			}
			if ($stat == 'selfRegistrations') {
				$dataSeries['Self Registrations'] = [
					'borderColor' => 'rgba(255, 159, 64, 1)',
					'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
					'data' => [],
				];
				$userILSUsage->selectAdd('SUM(selfRegistrationCount) as sumSelfRegistrations');
			}
			if ($stat == 'usersWithPdfDownloads') {
				$dataSeries['Users Who Downloaded At Least One PDF'] = [
					'borderColor' => 'rgba(255, 206, 86, 1)',
					'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
					'data' => [],
				];
				$userILSUsage->selectAdd('SUM(IF(pdfDownloadCount>0,1,0)) as usersWithPdfDownloads');
			}
			if ($stat == 'usersWithPdfViews') {
				$dataSeries['Users Who Viewed At Least One PDF'] = [
					'borderColor' => 'rgba(255, 206, 86, 1)',
					'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
					'data' => [],
				];
				$userILSUsage->selectAdd('SUM(IF(pdfViewCount>0,1,0)) as usersWithPdfViews');
			}
			if ($stat == 'usersWithSupplementalFileDownloads') {
				$dataSeries['Users Who Downloaded At Least One Supplemental File'] = [
					'borderColor' => 'rgba(255, 206, 86, 1)',
					'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
					'data' => [],
				];
				$userILSUsage->selectAdd('SUM(IF(supplementalFileDownloadCount>0,1,0)) as usersWithSupplementalFileDownloads');
			}
			if ($stat == 'usersWithHolds') {
				$dataSeries['Users Who Placed At Least One Hold'] = [
					'borderColor' => 'rgba(0, 255, 55, 1)',
					'backgroundColor' => 'rgba(0, 255, 55, 0.2)',
					'data' => [],
				];
				$userILSUsage->selectAdd('SUM(IF(usageCount>0,1,0)) as usersWithHolds');
			}

			//Collect results
			$userILSUsage->find();
	
			while ($userILSUsage->fetch()) {
				$curPeriod = "{$userILSUsage->month}-{$userILSUsage->year}";
				$columnLabels[] = $curPeriod;
				if ($stat == 'userLogins' ) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['User Logins']['data'][$curPeriod] = $userILSUsage->sumUserLogins;
				}
				if ($stat == 'selfRegistrations' ) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Self Registrations']['data'][$curPeriod] = $userILSUsage->sumSelfRegistrations;
				}
				if ($stat == 'usersWithPdfDownloads' ) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Users Who Downloaded At Least One PDF']['data'][$curPeriod] = $userILSUsage->usersWithPdfDownloads;
				}
				if ($stat == 'usersWithPdfViews') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Users Who Viewed At Least One PDF']['data'][$curPeriod] = $userILSUsage->usersWithPdfViews;	
				}
				if ($stat == 'usersWithHolds') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Users Who Placed At Least One Hold']['data'][$curPeriod] = $userILSUsage->usersWithHolds;	
				}
				if ($stat == 'usersWithSupplementalFileDownloads') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Users Who Downloaded At Least One Supplemental File']['data'][$curPeriod] = $userILSUsage->usersWithSupplementalFileDownloads;	
				}
			}
		}

		// for graphs displaying data retrieved from the ils_record_usage table
		if (
			$stat == 'pdfsDownloaded' ||
			$stat == 'pdfsViewed' ||
			$stat == 'supplementalFilesDownloaded' ||
			$stat == 'recordsHeld' ||
			$stat == 'totalHolds'
		) {
			$recordILSUsage = new ILSRecordUsage();
			$recordILSUsage->groupBy('year, month');
			if (!empty($instanceName)) {
				$recordILSUsage->instance = $instanceName;
			}
			$recordILSUsage->selectAdd();
			$recordILSUsage->selectAdd('year');
			$recordILSUsage->selectAdd('month');
			$recordILSUsage->orderBy('year, month');

			if ($stat == 'pdfsDownloaded') {
				$dataSeries['PDFs Downloaded'] = [
					'borderColor' => 'rgba(255, 206, 86, 1)',
					'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
					'data' => [],
				];
				$recordILSUsage->selectAdd('SUM(pdfDownloadCount) as sumPdfsDownloaded');
			}
			if ($stat == 'pdfsViewed') {
				$dataSeries['PDFs Viewed'] = [
					'borderColor' => 'rgba(255, 206, 86, 1)',
					'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
					'data' => [],
				];
				$recordILSUsage->selectAdd('SUM(pdfViewCount) as sumPdfsViewed');
			}
			if ($stat == 'supplementalFilesDownloaded') {
				$dataSeries['Supplemental Files Downloaded'] = [
					'borderColor' => 'rgba(255, 206, 86, 1)',
					'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
					'data' => [],
				];
				$recordILSUsage->selectAdd('SUM(supplementalFileDownloadCount) as sumSupplementalFilesDownloaded');
			}
			if ($stat == 'recordsHeld') {
				$dataSeries['Records Held'] = [
					'borderColor' => 'rgba(154, 75, 244, 1)',
					'backgroundColor' => 'rgba(154, 75, 244, 0.2)',
					'data' => [],
				];
				$recordILSUsage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
			}
			if ($stat == 'totalHolds') {
				$dataSeries['Total Holds'] = [
					'borderColor' => 'rgba(54, 162, 235, 1)',
					'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
					'data' => [],
				];
				$recordILSUsage->selectAdd('SUM(timesUsed) as totalHolds');
			}
			
			//Collect results
			$recordILSUsage->find();
			while ($recordILSUsage->fetch()) {
				$curPeriod = "{$recordILSUsage->month}-{$recordILSUsage->year}";
				$columnLabels[] = $curPeriod;
				if ($stat == 'pdfsDownloaded' ) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['PDFs Downloaded']['data'][$curPeriod] = $recordILSUsage->sumPdfsDownloaded;
				}
				if ($stat == 'pdfsViewed' ) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['PDFs Viewed']['data'][$curPeriod] = $recordILSUsage->sumPdfsViewed;
				}
				if ($stat == 'supplementalFilesDownloaded' ) {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Supplemental Files Downloaded']['data'][$curPeriod] = $recordILSUsage->sumSupplementalFilesDownloaded;
				}
				if ($stat == 'recordsHeld') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Records Held']['data'][$curPeriod] = $recordILSUsage->numRecordsUsed;
				}	
				if ($stat == 'totalHolds') {
					/** @noinspection PhpUndefinedFieldInspection */
					$dataSeries['Total Holds']['data'][$curPeriod] = $recordILSUsage->totalHolds;
				}	
			}
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);	
		$interface->assign('translateColumnLabels', false);
	}

	private function assignGraphSpecificTitle($stat) {
		global $interface;
		$title = $interface->getVariable('graphTitle'); 
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
		$interface->assign('graphTitle', $title);
	}
}