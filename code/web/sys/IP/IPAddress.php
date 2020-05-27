<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class IPAddress extends DataObject
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

	static function getObjectStructure(){
		//Look lookup information for display in the user interface
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = array();
		$locationLookupList = array();
		$locationLookupList[-1] = '<No Nearby Location>';
		while ($location->fetch()){
			$locationLookupList[$location->locationId] = $location->displayName;
			$locationList[$location->locationId] = clone $location;
		}
		return array(
			'ip' => array('property'=>'ip', 'type'=>'text', 'label'=>'IP Address', 'description'=>'The IP Address to map to a location formatted as xxx.xxx.xxx.xxx/mask'),
			'location' => array('property'=>'location', 'type'=>'text', 'label'=>'Display Name', 'description'=>'Descriptive information for the IP Address for internal use'),
			'locationid' => array('property'=>'locationid', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Location', 'description'=>'The Location which this IP address maps to'),
			'isOpac' => array('property' => 'isOpac', 'type' => 'checkbox', 'label' => 'Treat as a Public OPAC', 'description' => 'This IP address will be treated as a public OPAC with autologout features turned on.', 'default' => true),
		);
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
			require_once ROOT_DIR . '/sys/IP/ipcalc.php';
			$ipRange = getIpRange($ipAddress);
			$startIp = $ipRange[0];
			$endIp = $ipRange[1];
		}else{
			if (strpos($ipAddress, '-')){
				list($startVal, $endVal) = explode('-', $ipAddress);
				$startIp = ip2long(trim($startVal));
				$endIp = ip2long(trim($endVal));
			}else {
				$startIp = ip2long($ipAddress);
				$endIp = $startIp;
			}
		}
		//echo("\r\n<br/>$ipAddress: " . sprintf('%u', $startIp) . " - " .  sprintf('%u', $endIp));
		$this->startIpVal = $startIp;
		$this->endIpVal = $endIp;
	}

	/**
	 * @param $activeIP
	 * @return bool|IPAddress
	 */
	static function getIPAddressForIP($activeIP){
		$ipVal = ip2long($activeIP);

		if (is_numeric($ipVal)) {
			disableErrorHandler();
			$subnet = new IPAddress();
			$subnet->whereAdd('startIpVal <= ' . $ipVal);
			$subnet->whereAdd('endIpVal >= ' . $ipVal);
			$subnet->orderBy('(endIpVal - startIpVal)');
			if ($subnet->find(true)) {
				enableErrorHandler();
				return $subnet;
			}else{
				enableErrorHandler();
				return false;
			}
		}else{
			return false;
		}
	}
}