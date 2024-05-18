<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UsageByUserAgent extends DataObject {
	public $__table = 'usage_by_user_agent';
	public $id;
	public $userAgentId;
	public $instance;
	public $year;
	public $month;
	public $numRequests;
	public $numBlockedRequests;

	function objectHistoryEnabled() : bool {
		return false;
	}
}