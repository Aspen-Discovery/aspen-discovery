<?php


class AspenSiteStat extends DataObject {
	public $__table = 'aspen_site_stats';
	protected $id;
	protected $aspenSiteId;
	protected $year;
	protected $month;
	protected $day;
	protected $minDataDiskSpace;
	protected $minUsrDiskSpace;
	protected $minAvailableMemory;
	protected $maxAvailableMemory;
	protected $minLoadPerCPU;
	protected $maxLoadPerCPU;
	protected $maxWaitTime;
}