<?php

class FailedLoginsByIPAddress extends DataObject {
	public $__table = 'failed_logins_by_ip_address';
	public $id;
	public $ipAddress;
	public $timestamp;

	public static function addFailedLogin() {
		try {
			$newLogin = new FailedLoginsByIPAddress();
			$newLogin->ipAddress = IPAddress::getClientIP();
			$newLogin->timestamp = time();
			$newLogin->insert();
		}catch (Exception $e) {
			//This fails when the table isn't created, ignore it
		}
	}
}