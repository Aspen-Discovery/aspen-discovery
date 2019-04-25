<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class subnet extends DataObject
{
	public $__table = 'ip_lookup';   // table name
	public $id;                      //int(25)
	public $locationid;              //int(5)
	public $location;                //varchar(255)
	public $ip;                      //varchar(255)
	public $isOpac;                   //tinyint(1)
	public $startIpVal;
	public $endIpVal;

	function keys() {
		return array('id', 'locationid', 'ip');
	}

	function label(){
		return $this->location;
	}

	function insert(){
		$this->calcIpRange();
		return parent::insert();
	}
	function update(){
		$this->calcIpRange();
		return parent::update();
	}
	function calcIpRange(){
		$ipAddress = $this->ip;
		$subnet_and_mask = explode('/', $ipAddress);
		if (count($subnet_and_mask) == 2){
			require_once ROOT_DIR . '/Drivers/marmot_inc/ipcalc.php';
			$ipRange = getIpRange($ipAddress);
			$startIp = $ipRange[0];
			$endIp = $ipRange[1];
		}else{
			$startIp = ip2long($ipAddress);
			$endIp = $startIp;
		}
		//echo("\r\n<br/>$ipAddress: " . sprintf('%u', $startIp) . " - " .  sprintf('%u', $endIp));
		$this->startIpVal = $startIp;
		$this->endIpVal = $endIp;
	}
}