<?php


class AspenSiteCheck extends DataObject
{
	public $__table = 'aspen_site_checks';
	public $id;
	public $siteId;
	public $checkName;
	public $currentStatus;
	public $currentNote;
	public $lastOkTime;
	public $lastWarningTime;
	public $lastErrorTime;

	public function getNumericColumnNames(): array
	{
		return ['currentStatus', 'lastOkTime', 'lastWarningTime', 'lastErrorTime'];
	}

	/**
	 * @param AspenSite|null $site
	 *
	 * @return string
	 */
	public function getUrl($site) :string {
		if (substr($site->baseUrl, -1) == '/'){
			$site->baseUrl = substr($site->baseUrl, 0, -1);
		}
		$checkType = str_replace(' ', '_', strtolower($this->checkName));
		if ($checkType == 'overdrive'){
			return $site->baseUrl . "/OverDrive/IndexingLog";
		}elseif ($checkType == 'koha' || $checkType == 'carl.x' || $checkType == 'symphony' || $checkType == 'sierra' || $checkType == 'polaris'){
			return $site->baseUrl . "/ILS/IndexingLog";
		}elseif ($checkType == 'axis_360'){
			return $site->baseUrl . "/Axis360/IndexingLog";
		}elseif ( $checkType == 'hoopla'){
			return $site->baseUrl . "/Hoopla/IndexingLog";
		}elseif ($checkType == 'cloud_library'){
			return $site->baseUrl . "/CloudLibrary/IndexingLog";
		}elseif ($checkType == 'web_indexer' || $checkType == 'web_builder'){
			return $site->baseUrl . "/Websites/IndexingLog";
		}elseif ($checkType == 'cron'){
			return $site->baseUrl . "/Admin/CronLog";
		}elseif ($checkType == 'nightly_index'){
			return $site->baseUrl . "/Admin/ReindexLog";
		}elseif ($checkType == 'side_loads'){
			return $site->baseUrl . "/SideLoads/IndexingLog";
		}elseif ($checkType == 'open_archives'){
			return $site->baseUrl . "/OpenArchives/IndexingLog";
		}elseif ($checkType == 'nyt_lists'){
			return $site->baseUrl . "/UserLists/NYTUpdatesLog";
		}
		return "";
	}
}