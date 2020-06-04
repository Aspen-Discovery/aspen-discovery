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
	public $blockAccess;
	public $allowAPIAccess;
	public $startIpVal;
	public $endIpVal;

	function keys() {
		return array('id', 'locationid', 'ip');
	}

	function getNumericColumnNames()
	{
		return ['isOpac', 'blockAccess', 'allowAPIAccess', 'startIpVal', 'endIpVal'];
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
			'blockAccess' => array('property' => 'blockAccess', 'type' => 'checkbox', 'label' => 'Block Access from this IP', 'description' => 'Traffic from this IP will not be allowed to use Aspen.', 'default' => false),
			'allowAPIAccess' => array('property' => 'allowAPIAccess', 'type' => 'checkbox', 'label' => 'Allow API Access', 'description' => 'Traffic from this IP will be allowed to use Aspen APIs.', 'default' => false),
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

	public static $activeIp = null;
	public static function getActiveIp()
	{
		if (!is_null(IPAddress::$activeIp)) return IPAddress::$activeIp;
		global $timer;
		//Make sure gets and cookies are processed in the correct order.
		if (isset($_GET['test_ip'])) {
			$ip = $_GET['test_ip'];
			//Set a cookie so we don't have to transfer the ip from page to page.
			setcookie('test_ip', $ip, 0, '/');
//		}elseif (isset($_COOKIE['test_ip']) && $_COOKIE['test_ip'] != '127.0.0.1' && strlen($_COOKIE['test_ip']) > 0){
		} elseif (!empty($_COOKIE['test_ip']) && $_COOKIE['test_ip'] != '127.0.0.1') {
			$ip = $_COOKIE['test_ip'];
		} else {
			$ip = IPAddress::getClientIP();
		}
		IPAddress::$activeIp = $ip;
		$timer->logTime("getActiveIp");
		return IPAddress::$activeIp;
	}

	/**
	 * @return mixed|string
	 */
	public static function getClientIP()
	{
		if (isset($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} elseif (isset($_SERVER["HTTP_X_FORWARDED"])) {
			$ip = $_SERVER["HTTP_X_FORWARDED"];
		} elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
			$ip = $_SERVER["HTTP_FORWARDED_FOR"];
		} elseif (isset($_SERVER["HTTP_FORWARDED"])) {
			$ip = $_SERVER["HTTP_FORWARDED"];
		} elseif (isset($_SERVER['REMOTE_HOST']) && strlen($_SERVER['REMOTE_HOST']) > 0) {
			$ip = $_SERVER['REMOTE_HOST'];
		} elseif (isset($_SERVER['REMOTE_ADDR']) && strlen($_SERVER['REMOTE_ADDR']) > 0) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '';
		}
		return $ip;
	}

	public static function isClientIpBlocked()
	{
		$clientIP = IPAddress::getClientIP();
		$ipInfo = IPAddress::getIPAddressForIP($clientIP);
		if (!empty($ipInfo)) {
			return $ipInfo->blockAccess;
		}else{
			return false;
		}
	}

	public static function allowAPIAccessForClientIP(){
		$clientIP = IPAddress::getClientIP();
		$ipInfo = IPAddress::getIPAddressForIP($clientIP);
		if (!empty($ipInfo)) {
			return $ipInfo->allowAPIAccess;
		}else{
			return false;
		}
	}
}