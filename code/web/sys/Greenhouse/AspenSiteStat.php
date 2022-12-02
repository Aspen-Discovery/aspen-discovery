<?php


class AspenSiteStat extends DataObject {
	public $__table = 'aspen_site_stats';
	public $id;
	public $aspenSiteId;
	public $year;
	public $month;
	public $day;
	public $minDataDiskSpace;
	public $minUsrDiskSpace;
	public $minAvailableMemory;
	public $maxAvailableMemory;
	public $minLoadPerCPU;
	public $maxLoadPerCPU;
	public $maxWaitTime;
}