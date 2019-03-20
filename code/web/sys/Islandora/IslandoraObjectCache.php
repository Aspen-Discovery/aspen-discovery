<?php

class IslandoraObjectCache  extends DataObject{
	public $__table = 'islandora_object_cache';
	public $id;
	public $pid;
	public $driverName;
	public $driverPath;
	public $title;
	public $hasLatLong;
	public $latitude;
	public $longitude;
	public $lastUpdate;

	public $smallCoverUrl;
	public $mediumCoverUrl;
	public $largeCoverUrl;
}