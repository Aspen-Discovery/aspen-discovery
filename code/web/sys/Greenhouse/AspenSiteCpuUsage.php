<?php

class AspenSiteCpuUsage extends DataObject
{
	public $__table = 'aspen_site_cpu_usage';
	public $id;
	public $aspenSiteId;
	public $loadPerCpu;
	public $timestamp;
}