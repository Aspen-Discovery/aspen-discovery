<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class WebPageUsage extends DataObject
{
	public $__table = 'website_page_usage';
	public $id;
	public $instance;
	public $webPageId;
	public $year;
	public $month;
	public $timesViewedInSearch;
	public $timesUsed;
}