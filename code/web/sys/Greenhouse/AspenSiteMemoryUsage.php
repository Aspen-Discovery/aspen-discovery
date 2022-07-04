<?php

class AspenSiteMemoryUsage extends DataObject
{
	public $__table = 'aspen_site_memory_usage';
	public $id;
	public $aspenSiteId;
	public $percentMemoryUsage;
	public $totalMemory;
	public $availableMemory;
	public $timestamp;
}