<?php


class AspenSiteStat
{
	public $__table = 'aspen_site_stats';
	public $id;
	public $siteId;
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