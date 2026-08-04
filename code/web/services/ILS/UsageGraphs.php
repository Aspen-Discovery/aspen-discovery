<?php

require_once ROOT_DIR . '/services/Admin/AbstractUsageGraphs.php';
require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';
require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

class ILS_UsageGraphs extends Admin_AbstractUsageGraphs {
	function launch(): void {
		$this->launchGraph('ILS');
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

	protected function getAndSetInterfaceDataSeries($stat, $instanceName, $timeframes = ['year', 'month'], $custom = false): void {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];
		$groupByTimeframe = implode(',', $timeframes);

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
			$userILSUsage->selectAdd();
			if (!empty($instanceName)) {
				$userILSUsage->instance = $instanceName;
			}
		
			if (is_array($custom)) {
				$userILSUsage->buildCustomPeriodQuery($custom);
			} else {
				$userILSUsage->groupBy($groupByTimeframe);
				foreach ($timeframes as $timeframe) {
					$userILSUsage->selectAdd($timeframe);
				}
				$userILSUsage->orderBy($groupByTimeframe);
			}
			
			if ($stat == 'userLogins') {
				$dataSeries['User Logins'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userILSUsage->selectAdd('SUM(usageCount) as sumUserLogins');
			}
			if ($stat == 'selfRegistrations') {
				$dataSeries['Self Registrations'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userILSUsage->selectAdd('SUM(selfRegistrationCount) as sumSelfRegistrations');
			}
			if ($stat == 'usersWithPdfDownloads') {
				$dataSeries['Users Who Downloaded At Least One PDF'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userILSUsage->selectAdd('SUM(IF(pdfDownloadCount>0,1,0)) as usersWithPdfDownloads');
			}
			if ($stat == 'usersWithPdfViews') {
				$dataSeries['Users Who Viewed At Least One PDF'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userILSUsage->selectAdd('SUM(IF(pdfViewCount>0,1,0)) as usersWithPdfViews');
			}
			if ($stat == 'usersWithSupplementalFileDownloads') {
				$dataSeries['Users Who Downloaded At Least One Supplemental File'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userILSUsage->selectAdd('SUM(IF(supplementalFileDownloadCount>0,1,0)) as usersWithSupplementalFileDownloads');
			}
			if ($stat == 'usersWithHolds') {
				$dataSeries['Users Who Placed At Least One Hold'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$userILSUsage->selectAdd('SUM(IF(usageCount>0,1,0)) as usersWithHolds');
			}

			//Collect results
			$userILSUsage->find();
			
			while ($userILSUsage->fetch()) {
				$curPeriod = $custom ? $userILSUsage->getCustomPeriod() : $userILSUsage->getCurPeriod($timeframes);
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
			$recordILSUsage->selectAdd();
			if (!empty($instanceName)) {
				$recordILSUsage->instance = $instanceName;
			}
		
			if (is_array($custom)) {
				$recordILSUsage->buildCustomPeriodQuery($custom);
			} else {
				$recordILSUsage->groupBy($groupByTimeframe);
				foreach ($timeframes as $timeframe) {
					$recordILSUsage->selectAdd($timeframe);
				}
				$recordILSUsage->orderBy($groupByTimeframe);
			}

			if ($stat == 'pdfsDownloaded') {
				$dataSeries['PDFs Downloaded'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordILSUsage->selectAdd('SUM(pdfDownloadCount) as sumPdfsDownloaded');
			}
			if ($stat == 'pdfsViewed') {
				$dataSeries['PDFs Viewed'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordILSUsage->selectAdd('SUM(pdfViewCount) as sumPdfsViewed');
			}
			if ($stat == 'supplementalFilesDownloaded') {
				$dataSeries['Supplemental Files Downloaded'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordILSUsage->selectAdd('SUM(supplementalFileDownloadCount) as sumSupplementalFilesDownloaded');
			}
			if ($stat == 'recordsHeld') {
				$dataSeries['Records Held'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordILSUsage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
			}
			if ($stat == 'totalHolds') {
				$dataSeries['Total Holds'] =  GraphingUtils::getDataSeriesArray(count($dataSeries));
				$recordILSUsage->selectAdd('SUM(timesUsed) as totalHolds');
			}
			
			//Collect results
			$recordILSUsage->find();
			while ($recordILSUsage->fetch()) {
				$curPeriod = $custom ? $recordILSUsage->getCustomPeriod() : $recordILSUsage->getCurPeriod($timeframes);
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

	protected function assignGraphSpecificTitle($stat): void {
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