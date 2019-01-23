<?php
/**
 * Created by PhpStorm.
 * User: mnoble
 * Date: 10/24/2017
 * Time: 9:36 PM
 */

class IslandoraSamePikaCache extends DB_DataObject
{
	public $__table = 'islandora_samepika_cache';
	public $id;
	public $groupedWorkId;
	public $pid;
	public $archiveLink;
}