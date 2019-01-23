<?php
require_once 'DB/DataObject.php';

class Analytics_Search extends DB_DataObject{
	public $__table = 'analytics_search';                        // table name
	public $id;
	public $sessionId;
	public $searchType;
	public $scope;
	public $lookfor;
	public $isAdvanced;
	public $facetsApplied;
	public $numResults;
	public $searchTime;

	//Dynamically created variable during queries.
	public $numSearches;

	public function addDateFilters(){
		if (isset($_REQUEST['startDate'])){
			$startDate = DateTime::createFromFormat('m-d-Y', $_REQUEST['startDate']);
			$startDate->setTime(0, 0, 0);
			$this->whereAdd("searchTime  >= " . $startDate->getTimestamp());
		}
		if (isset($_REQUEST['endDate'])){
			$endDate = DateTime::createFromFormat('m-d-Y', $_REQUEST['endDate']);
			$startDate->setTime(24, 0, 0);
			$this->whereAdd("searchTime < " . $endDate->getTimestamp());
		}
	}
}