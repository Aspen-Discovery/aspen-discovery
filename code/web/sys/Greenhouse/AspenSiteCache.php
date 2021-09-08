<?php


class AspenSiteCache extends DataObject
{
	public $__table = 'greenhouse_cache';
	public $id;
	public $name;
	public $locationId;
	public $libraryId;
	public $solrScope;
	public $latitude;
	public $longitude;
	public $unit;
	public $baseUrl;
	public $lastUpdated;
}