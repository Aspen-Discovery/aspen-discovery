<?php


class UsageByIPAddress extends DataObject
{
	public $__table = 'usage_by_ip_address';
	public $id;
	public $instance;
	public $ipAddress;
	public $year;
	public $month;
	public $numRequests;
	public $numBlockedRequests;
	public $numBlockedApiRequests;
	public $lastRequest;
	public $numLoginAttempts;
	public $numFailedLoginAttempts;
}