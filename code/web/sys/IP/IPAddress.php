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
	public $showDebuggingInformation;
	public $logTimingInformation;
	public $logAllQueries;
	public $startIpVal;
	public $endIpVal;

	function getNumericColumnNames() : array
	{
		return ['isOpac', 'blockAccess', 'allowAPIAccess', 'startIpVal', 'endIpVal'];
	}

	static function getObjectStructure() : array{
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
			'ip' => array('property'=>'ip', 'type'=>'text', 'label'=>'IP Address', 'description'=>'The IP Address to map to a location formatted as xxx.xxx.xxx.xxx/mask', 'serverValidation' => 'validateIPAddress'),
			'location' => array('property'=>'location', 'type'=>'text', 'label'=>'Display Name', 'description'=>'Descriptive information for the IP Address for internal use'),
			'locationid' => array('property'=>'locationid', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Location', 'description'=>'The Location which this IP address maps to'),
			'isOpac' => array('property' => 'isOpac', 'type' => 'checkbox', 'label' => 'Treat as a Public OPAC', 'description' => 'This IP address will be treated as a public OPAC with autologout features turned on.', 'default' => true),
			'blockAccess' => array('property' => 'blockAccess', 'type' => 'checkbox', 'label' => 'Block Access from this IP', 'description' => 'Traffic from this IP will not be allowed to use Aspen.', 'default' => false),
			'allowAPIAccess' => array('property' => 'allowAPIAccess', 'type' => 'checkbox', 'label' => 'Allow API Access', 'description' => 'Traffic from this IP will be allowed to use Aspen APIs.', 'default' => false),
			'showDebuggingInformation' => array('property' => 'showDebuggingInformation', 'type' => 'checkbox', 'label' => 'Show Debugging Information', 'description' => 'Traffic from this IP will have debugging information emitted for it.', 'default' => false),
			'logTimingInformation' => array('property' => 'logTimingInformation', 'type' => 'checkbox', 'label' => 'Log Timing Information', 'description' => 'Traffic from this IP will have timing information logged for it.', 'default' => false),
			'logAllQueries' => array('property' => 'logAllQueries', 'type' => 'checkbox', 'label' => 'Log Database Queries', 'description' => 'Traffic from this IP will have database query information logged for it.', 'default' => false),
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
	function validateIPAddress(){
		$calcIpResult = $this->calcIpRange();
		$errors = [];
		if (!$calcIpResult) {
			$errors[] = 'The IP address entered is not valid';
		}
		return [
			'validatedOk' => $calcIpResult,
			'errors' => $errors
		];
	}
	function calcIpRange(){
		$ipAddress = $this->ip;
		$subnet_and_mask = explode('/', $ipAddress);
		if (count($subnet_and_mask) == 2){
			$ipRange = $this->getIpRange($ipAddress);
			$startIp = $ipRange[0];
			$endIp = $ipRange[1];
		}else{
			if (strpos($ipAddress, '-')){
				list($startVal, $endVal) = explode('-', $ipAddress);
				$startIp = $this->convertIpToLong(trim($startVal));
				$endIp = $this->convertIpToLong(trim($endVal));
			}else {
				$startIp = $this->convertIpToLong($ipAddress);
				$endIp = $startIp;
			}
		}
		//echo("\r\n<br/>$ipAddress: " . sprintf('%u', $startIp) . " - " .  sprintf('%u', $endIp));
		$this->startIpVal = $startIp;
		$this->endIpVal = $endIp;
		if ($startIp == false || $endIp == false){
			return false;
		}else{
			return true;
		}
	}

	private function getIpRange(  $cidr) {

		list($ip, $mask) = explode('/', $cidr);

		$maskBinStr =str_repeat("1", $mask ) . str_repeat("0", 32-$mask );      //net mask binary string
		$inverseMaskBinStr = str_repeat("0", $mask ) . str_repeat("1",  32-$mask ); //inverse mask

		$ipLong = ip2long( $ip );
		$ipMaskLong = bindec( $maskBinStr );
		$inverseIpMaskLong = bindec( $inverseMaskBinStr );
		$netWork = $ipLong & $ipMaskLong;

		//$start = $netWork+1;//ignore network ID(eg: 192.168.1.0)
		$start = $netWork; //MDN, start at the network id

		$end = ($netWork | $inverseIpMaskLong) -1 ; //ignore broadcast IP(eg: 192.168.1.255)
		return array( $start, $end );
	}

	function convertIpToLong($ipAddress){
		$ipAddress = trim($ipAddress);
		$ipAsLong = ip2long($ipAddress);
		if ($ipAsLong !== false){
			return $ipAsLong;
		}else{
			//Check if we have formatting issues, an IP entered with leading 0's in one of the octets messes up ipAsLong
			$ipOctets = explode('.', $ipAddress);
			if (count($ipOctets) != 4){
				return false;
			}else{
				$ipAddress = '';
				foreach ($ipOctets as $octetNum => $ipOctet) {
					if ($octetNum != 0) {
						$ipAddress .= '.';
					}
					$ipAddress .= (int)$ipOctet;
				}
				return ip2long($ipAddress);
			}
		}
	}

	static $ipAddressesForIP = [];
	/**
	 * @param $activeIP
	 * @return bool|IPAddress
	 */
	static function getIPAddressForIP($activeIP){
		if (empty($activeIP)){
			return false;
		}
		$ipVal = ip2long($activeIP);
		if (is_numeric($ipVal)) {
			if (array_key_exists($ipVal, IPAddress::$ipAddressesForIP)){
				return IPAddress::$ipAddressesForIP[$ipVal];
			}
			disableErrorHandler();
			$subnet = new IPAddress();
			$subnet->whereAdd('startIpVal <= ' . $ipVal);
			$subnet->whereAdd('endIpVal >= ' . $ipVal);
			$subnet->orderBy('(endIpVal - startIpVal)');
			if ($subnet->find(true)) {
				enableErrorHandler();
				IPAddress::$ipAddressesForIP[$ipVal] = $subnet;
				return $subnet;
			}else{
				enableErrorHandler();
				IPAddress::$ipAddressesForIP[$ipVal] = false;
				return false;
			}
		}else{
			IPAddress::$ipAddressesForIP[$ipVal] = false;
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
		if ($ip == '::1'){
			$ip = '127.0.0.1';
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

	static $_showDebuggingInformation = null;
	public static function showDebuggingInformation(){
		if (IPAddress::$_showDebuggingInformation === null) {
			$clientIP = IPAddress::getClientIP();
			$ipInfo = IPAddress::getIPAddressForIP($clientIP);
			if (!empty($ipInfo)) {
				IPAddress::$_showDebuggingInformation = $ipInfo->showDebuggingInformation;
			} else {
				IPAddress::$_showDebuggingInformation = false;
			}
		}
		return IPAddress::$_showDebuggingInformation;
	}

	static $_logTimingInformation = null;
	public static function logTimingInformation(){
		if (IPAddress::$_logTimingInformation === null) {
			$clientIP = IPAddress::getClientIP();
			$ipInfo = IPAddress::getIPAddressForIP($clientIP);
			if (!empty($ipInfo)) {
				IPAddress::$_logTimingInformation = $ipInfo->logTimingInformation;
			} else {
				IPAddress::$_logTimingInformation = false;
			}
		}
		return IPAddress::$_logTimingInformation;
	}

	static $_logAllQueries = null;
	static $_loadingLogQueryInfo = false;
	public static function logAllQueries(){
		if (IPAddress::$_logAllQueries === null) {
			if (!isset($_REQUEST['logQueries'])){
				IPAddress::$_loadingLogQueryInfo = false;
			}else {
				//There is a potential recursion here that we need to avoid
				if (IPAddress::$_loadingLogQueryInfo) {
					return false;
				} else {
					IPAddress::$_loadingLogQueryInfo = true;
					IPAddress::$_logAllQueries = false;
					$clientIP = IPAddress::getClientIP();
					$ipInfo = IPAddress::getIPAddressForIP($clientIP);
					if (!empty($ipInfo)) {
						IPAddress::$_logAllQueries = $ipInfo->logAllQueries;
					} else {
						IPAddress::$_logAllQueries = false;
					}
					IPAddress::$_loadingLogQueryInfo = false;
				}
			}
		}
		return IPAddress::$_logAllQueries;
	}
}