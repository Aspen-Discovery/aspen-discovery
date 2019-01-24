<?php
require_once ROOT_DIR .'/sys/DB/DataObject.php';

class Analytics_PageView extends DataObject
{
	public $__table = 'analytics_page_view';                        // table name
	public $id;
	public $sessionId;
	public $pageStartTime;
	public $pageEndTime;
	public $loadTime;
	public $language;
	public $module;
	public $action;
	public $method;
	public $objectId;
	public $fullUrl;

	//Dynamic based on queries
	public $numViews;

	public function addDateFilters(){
		if (isset($_REQUEST['startDate'])){
			$startDate = DateTime::createFromFormat('m-d-Y', $_REQUEST['startDate']);
			$startDate->setTime(0, 0, 0);
			$this->whereAdd("pageStartTime  >= " . $startDate->getTimestamp());
		}
		if (isset($_REQUEST['endDate'])){
			$endDate = DateTime::createFromFormat('m-d-Y', $_REQUEST['endDate']);
			$startDate->setTime(24, 0, 0);
			$this->whereAdd("pageEndTime < " . $endDate->getTimestamp());
		}
	}

}