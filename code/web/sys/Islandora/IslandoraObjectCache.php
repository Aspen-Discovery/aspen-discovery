<?php

/**
 * Stores information about Islandora objects for improved response times
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/18/2016
 * Time: 10:48 AM
 */
class IslandoraObjectCache  extends DB_DataObject{
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