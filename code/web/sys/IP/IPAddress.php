<?php /** @noinspection SpellCheckingInspection */
/** @noinspection PhpMissingFieldTypeInspection */

class IPAddress extends DataObject {
	public $__table = 'ip_lookup';    //	table name
	public $id;                       //	int(25)
	public $locationid;               //	int(5)
	public $location;                 //	varchar(255)
	public $ip;                       //	varchar(255) - IPv4 or IPv6 address or range
	public $isOpac;                   //	tinyint(1)
	public $defaultLogMeOutAfterPlacingHoldOn;
	public $blockAccess;
	public $blockedForSpam;
	public $allowAPIAccess;
	public $showDebuggingInformation;
	public $logTimingInformation;
	public $logAllQueries;
	public $startIpVal;              //	varchar(255) - Numeric for IPv4, string with 'ipv6:' prefix for IPv6
	public $endIpVal;                //	varchar(255) - Numeric for IPv4, string with 'ipv6:' prefix for IPv6
	public $authenticatedForEBSCOhost;
	public $masqueradeMode;
	public $ssoLogin;

	function getNumericColumnNames(): array {
		return [
			'isOpac',
			'defaultLogMeOutAfterPlacingHoldOn',
			'blockAccess',
			'blockedForSpam',
			'allowAPIAccess',
			'showDebuggingInformation',
			'logTimingInformation',
			'logAllQueries',
			'authenticatedForEBSCOhost',
			'masqueradeMode',
			'ssoLogin'
		];
	}

	public function getUniquenessFields(): array {
		return ['ip'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		//Look lookup information for display in the user interface
		$location = new Location();
		$location->selectAdd();
		$location->selectAdd('displayName');
		$location->selectAdd('locationId');
		$location->orderBy('displayName');
		$location->find();
		$locationLookupList = [];
		$locationLookupList[-1] = '<No Nearby Location>';
		while ($location->fetch()) {
			$locationLookupList[$location->locationId] = $location->displayName;
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'ip' => [
				'property' => 'ip',
				'type' => 'text',
				'label' => 'IP Address',
				'description' => 'The IP Address to map to a location.',
				'serverValidation' => 'validateIPAddress',
				'note' => 'Supported formats: IPv4 (xxx.xxx.xxx.xxx), IPv6 (8 groups of 4 hexadecimal digits or shorthand xxxx:xxxx::xxxx), or ranges with dash notation (xxx.xxx.xxx.xxx-xxx.xxx.xxx.xxx or xxxx:xxxx::-xxxx:xxxx::). Subnet masks are only supported with IPv4 addresses (xxx.xxx.xxx.xxx/xx); IPv6 subnets are not supported.'
			],
			'location' => [
				'property' => 'location',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'Descriptive information for the IP Address for internal use',
			],
			'locationid' => [
				'property' => 'locationid',
				'type' => 'enum',
				'values' => $locationLookupList,
				'label' => 'Location',
				'description' => 'The Location which this IP address maps to',
			],
			'isOpac' => [
				'property' => 'isOpac',
				'type' => 'checkbox',
				'label' => 'Treat as a Public OPAC',
				'description' => 'This IP address will be treated as a public OPAC with autologout features turned on.',
				'default' => true,
			],
			'defaultLogMeOutAfterPlacingHoldOn' => [
				'property' => 'defaultLogMeOutAfterPlacingHoldOn',
				'type' => 'checkbox',
				'label' => 'Default "Log me out" checkbox on when placing a hold',
				'description' => 'Whether or not the log me out checkbox is defaulted on or off. Turning this off is useful for minimizing patron disruption, but you should be sure to have a way to automatically logout patron sessions on shared computers.',
				'default' => true,
			],
			'blockAccess' => [
				'property' => 'blockAccess',
				'type' => 'checkbox',
				'label' => 'Block Access from this IP',
				'description' => 'Traffic from this IP will not be allowed to use Aspen.',
				'default' => false,
			],
			'blockedForSpam' => [
				'property' => 'blockedForSpam',
				'type' => 'checkbox',
				'label' => 'IP Blocked due to SPAM',
				'description' => 'Traffic from this IP has been blocked from using Aspen due to SPAM traffic.',
				'default' => false,
			],
			'allowAPIAccess' => [
				'property' => 'allowAPIAccess',
				'type' => 'checkbox',
				'label' => 'Allow API Access',
				'description' => 'Traffic from this IP will be allowed to use Aspen APIs.',
				'default' => false,
			],
			'showDebuggingInformation' => [
				'property' => 'showDebuggingInformation',
				'type' => 'checkbox',
				'label' => 'Show Debugging Information',
				'description' => 'Traffic from this IP will have debugging information emitted for it.',
				'default' => false,
			],
			'logTimingInformation' => [
				'property' => 'logTimingInformation',
				'type' => 'checkbox',
				'label' => 'Log Timing Information',
				'description' => 'Traffic from this IP will have timing information logged for it.',
				'default' => false,
			],
			'logAllQueries' => [
				'property' => 'logAllQueries',
				'type' => 'checkbox',
				'label' => 'Log Database Queries',
				'description' => 'Traffic from this IP will have database query information logged for it.',
				'default' => false,
			],
			'authenticatedForEBSCOhost' => [
				'property' => 'authenticatedForEBSCOhost',
				'type' => 'checkbox',
				'label' => 'Authenticated For EBSCOhost',
				'description' => 'Traffic from this IP will be automaticatlly authenticated in EBSCOhost.',
				'default' => false,
			],
			'masqueradeMode' => [
				'property' => 'masqueradeMode',
				'type' => 'checkbox',
				'label' => 'Allow Masquerade',
				'description' => 'Traffic from this IP will be allowed to use Masquerade Mode.',
				'default' => false,
			],
			'ssoLogin' => [
				'property' => 'ssoLogin',
				'type' => 'checkbox',
				'label' => 'Allow Single Sign-on (SSO)',
				'description' => 'Traffic from this IP will be allowed to use single sign-on.',
				'default' => false,
			]
		];

		global $enabledModules;
		if (!array_key_exists('EBSCOhost', $enabledModules)) {
			unset ($structure['authenticatedForEBSCOhost']);
		}

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	function label() {
		return $this->location;
	}

	public function insert(string $context = '') : int|bool {
		$this->calcIpRange();
		/** @var $memCache Memcache */
		global $memCache;
		$memCache->deleteStartingWith('ipId_for_ip_');
		$memCache->deleteStartingWith('location_for_ip_');
		IPAddress::$ipAddressesForIP = [];
		return parent::insert();
	}

	public function update(string $context = '') : int|bool {
		$this->calcIpRange();
		/** @var $memCache Memcache */
		global $memCache;
		$memCache->deleteStartingWith('ipId_for_ip_');
		$memCache->deleteStartingWith('location_for_ip_');
		IPAddress::$ipAddressesForIP = [];
		return parent::update();
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		/** @var $memCache Memcache */
		global $memCache;
		$memCache->deleteStartingWith('ipId_for_ip_');
		$memCache->deleteStartingWith('location_for_ip_');
		IPAddress::$ipAddressesForIP = [];
		return parent::delete($useWhere, $hardDelete);
	}


	/** @noinspection PhpUnused This is used in validation when editing the object */
	function validateIPAddress(): array {
		$ipAddress = $this->ip;
		$errors = [];

		// Check if it's a valid IPv4 address.
		$isIPv4 = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

		// Check if it's a valid IPv6 address.
		$isIPv6 = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

		// Check for CIDR notation.
		if (str_contains($ipAddress, '/')) {
			[$subnet, $mask] = explode('/', $ipAddress);
			$isIPv4Subnet = filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && is_numeric($mask) && $mask >= 0 && $mask <= 32;
			$isIPv6Subnet = filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

			if ($isIPv6Subnet) {
				$errors[] = 'IPv6 subnets are not supported. Please use individual IPv6 addresses or IPv6 ranges with dash notation instead.';
			} else if (!$isIPv4Subnet) {
				$errors[] = 'The subnet mask is not valid. For IPv4, it should be between 0-32.';
			}
		}

		// Check for range notation with dash.
		elseif (str_contains($ipAddress, '-')) {
			[$startIP, $endIP] = explode('-', $ipAddress);
			$startIP = trim($startIP);
			$endIP = trim($endIP);

			$isValidIPv4Range = filter_var($startIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
				filter_var($endIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

			$isValidIPv6Range = filter_var($startIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) &&
				filter_var($endIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

			if (!$isValidIPv4Range && !$isValidIPv6Range) {
				$errors[] = 'The IP range is not valid. Both start and end IPs must be valid and of the same type (IPv4 or IPv6).';
			}
		}

		// Single IP address.
		else if (!$isIPv4 && !$isIPv6) {
			$errors[] = 'The IP address entered is not a valid IPv4 or IPv6 address. Examples: xxx.xxx.xxx.xxx (IPv4), xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx (IPv6).';
		}

		$calcIpResult = $this->calcIpRange();
		if (!$calcIpResult) {
			$errors[] = 'Failed to calculate the IP range.';
		}

		return [
			'validatedOk' => empty($errors),
			'errors' => $errors,
		];
	}

	function calcIpRange(): bool {
		$ipAddress = $this->ip;
		$objectStructure = IPAddress::getObjectStructure();

		// Handle IPv6 addresses
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$ipv6Binary = 'ipv6:' . bin2hex(inet_pton($ipAddress));
			$this->setProperty('startIpVal', $ipv6Binary, $objectStructure);
			$this->setProperty('endIpVal', $ipv6Binary, $objectStructure);
			return true;
		}

		// Check if it's a CIDR notation for IPv6 (currently not supported in Aspen).
		// TODO: To be supported, it would require testing IPv6 subnets.
		$subnet_and_mask = explode('/', $ipAddress);
		if (count($subnet_and_mask) == 2 && filter_var($subnet_and_mask[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			global $logger;
			$logger->log("IPv6 subnets (CIDR notation) are not supported: $ipAddress.", Logger::LOG_ERROR);
			return false;
		}

		// Check for IPv6 range with dash notation.
		if (str_contains($ipAddress, '-')) {
			[$startVal, $endVal] = explode('-', $ipAddress);
			$startVal = trim($startVal);
			$endVal = trim($endVal);

			if (filter_var($startVal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) &&
				filter_var($endVal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

				$startHex = 'ipv6:' . bin2hex(inet_pton($startVal));
				$endHex = 'ipv6:' . bin2hex(inet_pton($endVal));

				$this->setProperty('startIpVal', $startHex, $objectStructure);
				$this->setProperty('endIpVal', $endHex, $objectStructure);

				return true;
			}
		}

		// Original IPv4 handling.
		if (count($subnet_and_mask) == 2) {
			$ipRange = $this->getIpRange($ipAddress);
			$startIp = $ipRange[0];
			$endIp = $ipRange[1];
		} else {
			if (strpos($ipAddress, '-')) {
				[
					$startVal,
					$endVal,
				] = explode('-', $ipAddress);
				$startIp = $this->convertIpToLong(trim($startVal));
				$endIp = $this->convertIpToLong(trim($endVal));
			} else {
				$startIp = $this->convertIpToLong($ipAddress);
				$endIp = $startIp;
			}
		}
		//echo("\r\n<br/>$ipAddress: " . sprintf('%u', $startIp) . " - " .  sprintf('%u', $endIp));
		$this->setProperty('startIpVal', $startIp, null);
		$this->setProperty('endIpVal', $endIp, null);
		if (!$startIp || !$endIp) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param $cidr
	 * @return int[]
	 */
	private function getIpRange($cidr): array {

		[
			$ip,
			$mask,
		] = explode('/', $cidr);

		$maskBinStr = str_repeat("1", $mask) . str_repeat("0", 32 - $mask);      //net mask binary string
		$inverseMaskBinStr = str_repeat("0", $mask) . str_repeat("1", 32 - $mask); //inverse mask

		$ipLong = ip2long($ip);
		$ipMaskLong = bindec($maskBinStr);
		$inverseIpMaskLong = bindec($inverseMaskBinStr);
		$netWork = $ipLong & $ipMaskLong;

		//$start = $netWork+1;//ignore network ID(eg: 192.168.1.0)
		$start = $netWork; //MDN, start at the network id

		$end = ($netWork | $inverseIpMaskLong) - 1; //ignore broadcast IP(eg: 192.168.1.255)
		return [
			$start,
			$end,
		];
	}

	function convertIpToLong($ipAddress): bool|int|string
	{
		$ipAddress = trim($ipAddress);

		// Check if it's an IPv6 address.
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			// For IPv6, use inet_pton and store the result as a binary string.
			// Use a prefix to identify IPv6 addresses in internal storage.
			return 'ipv6:' . bin2hex(inet_pton($ipAddress));
		}

		// Original IPv4 handling
		$ipAsLong = ip2long($ipAddress);
		if ($ipAsLong !== false) {
			return $ipAsLong;
		} else {
			//Check if we have formatting issues, an IP entered with leading 0's in one of the octets messes up ipAsLong
			$ipOctets = explode('.', $ipAddress);
			if (count($ipOctets) != 4) {
				return false;
			} else {
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
	static function getIPAddressForIP($activeIP): IPAddress|bool
	{
		if (empty($activeIP)) {
			return false;
		}

		// Handle IPv6 addresses
		if (filter_var($activeIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$ipVal = 'ipv6:' . bin2hex(inet_pton($activeIP));

			if (array_key_exists($ipVal, IPAddress::$ipAddressesForIP)) {
				return IPAddress::$ipAddressesForIP[$ipVal];
			}

			disableErrorHandler();

			// First, check exact matches.
			$ipObject = new IPAddress();
			$ipObject->ip = $activeIP;
			if ($ipObject->find(true)) {
				enableErrorHandler();
				IPAddress::$ipAddressesForIP[$ipVal] = $ipObject;
				return $ipObject;
			}

			// Then, check for IPv6 stored in startIpVal/endIpVal.
			$ipObject = new IPAddress();
			$ipObject->startIpVal = $ipVal;
			$ipObject->endIpVal = $ipVal;
			if ($ipObject->find(true)) {
				enableErrorHandler();
				IPAddress::$ipAddressesForIP[$ipVal] = $ipObject;
				return $ipObject;
			}

			// Check IPv6 ranges (start-end).
			$ipObject = new IPAddress();
			$ipObject->whereAdd('startIpVal LIKE \'ipv6:%\'');
			$ipObject->whereAdd('startIpVal != endIpVal');
			$numRows = $ipObject->find();

			for ($i = 0; $i < $numRows; $i++) {
				$ipObject->fetch();
				// Extract hex values from stored format.
				$startHex = substr($ipObject->startIpVal, 5); // Remove 'ipv6:' prefix.
				$endHex = substr($ipObject->endIpVal, 5);     // Remove 'ipv6:' prefix.

				// Current IP as hex without prefix.
				$currentHex = substr($ipVal, 5);

				// Check if the IP is within the range.
				if (IPAddress::ipv6HexInRange($currentHex, $startHex, $endHex)) {
					enableErrorHandler();
					IPAddress::$ipAddressesForIP[$ipVal] = $ipObject;
					return $ipObject;
				}
			}

			enableErrorHandler();
			IPAddress::$ipAddressesForIP[$ipVal] = false;
			$ipObject->__destruct();
			$ipObject = null;
			return false;
		}

		// Original IPv4 handling
		$ipVal = ip2long($activeIP);
		if (is_numeric($ipVal)) {
			if (array_key_exists($ipVal, IPAddress::$ipAddressesForIP)) {
				return IPAddress::$ipAddressesForIP[$ipVal];
			}
			disableErrorHandler();
			$subnet = new IPAddress();
			$subnet->whereAdd('startIpVal <= ' . $ipVal);
			$subnet->whereAdd('endIpVal >= ' . $ipVal);
			$subnet->orderBy('(endIpVal - startIpVal)');
			/** @noinspection PhpIfWithCommonPartsInspection Needs to be done after the find since that is what we are avoiding */
			if ($subnet->find(true)) {
				enableErrorHandler();
				IPAddress::$ipAddressesForIP[$ipVal] = $subnet;
				return $subnet;
			} else {
				enableErrorHandler();
				IPAddress::$ipAddressesForIP[$ipVal] = false;
				$subnet->__destruct();
				$subnet = null;
				return false;
			}
		} else {
			IPAddress::$ipAddressesForIP[$ipVal] = false;
			return false;
		}
	}

	public static $activeIp = null;

	public static function getActiveIp() : string {
		if (!is_null(IPAddress::$activeIp)) {
			return IPAddress::$activeIp;
		}
		global $timer;
		//Make sure gets and cookies are processed in the correct order.
		if (isset($_GET['test_ip'])) {
			$ip = $_GET['test_ip'];
			//Set a cookie, so we don't have to transfer the ip from page to page.
			setcookie('test_ip', $ip, 0, '/');
		} elseif (!empty($_COOKIE['test_ip']) && $_COOKIE['test_ip'] != '127.0.0.1') {
			$ip = $_COOKIE['test_ip'];
		} else {
			$ip = IPAddress::getClientIP();
			setcookie('test_ip', null, time() - 3600, '/');
			unset($_COOKIE['test_ip']);
		}
		IPAddress::$activeIp = $ip;
		$timer->logTime("getActiveIp");
		return IPAddress::$activeIp;
	}

	/**
	 * @return mixed|string
	 */
	public static function getClientIP(): mixed
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

		// Handle X-Forwarded-For with multiple IPs.
		if (str_contains($ip, ',')) {
			// X-Forwarded-For may contain multiple IPs; take the first one (client).
			$ipList = explode(',', $ip);
			$ip = trim($ipList[0]);
		}

		// Convert IPv6 localhost to IPv4 localhost for backward compatibility.
		if ($ip == '::1') {
			$ip = '127.0.0.1';
		}

		return $ip;
	}

	public static function isClientIpBlocked(): bool {
		$clientIP = IPAddress::getClientIP();
		$ipInfo = IPAddress::getIPAddressForIP($clientIP);
		if (!empty($ipInfo)) {
			return $ipInfo->blockAccess || $ipInfo->blockedForSpam;
		} else {
			return false;
		}
	}

	public static function allowAPIAccessForClientIP(): bool {
		$clientIP = IPAddress::getClientIP();
		$ipInfo = IPAddress::getIPAddressForIP($clientIP);

		global $logger;
		if ($ipInfo) {
			$logger->log("IP rule found with allowAPIAccess: " . ($ipInfo->allowAPIAccess ? 'true.' : 'false.'), Logger::LOG_DEBUG);
		} else {
			$logger->log("No IP rule found for $clientIP, denying API access.", Logger::LOG_DEBUG);
		}

		if (!empty($ipInfo)) {
			return $ipInfo->allowAPIAccess;
		} else {
			return false;
		}
	}

	public static function allowSSOAccessForClientIP() : bool {
		global $library;
		$isSSORestricted = $library->getSSORestrictionStatus();
		if(!$isSSORestricted) {
			return true;
		} else {
			$clientIP = IPAddress::getClientIP();
			$ipInfo = IPAddress::getIPAddressForIP($clientIP);
			if (!empty($ipInfo)) {
				return $ipInfo->ssoLogin;
			} else {
				return false;
			}
		}
	}

	static $_showDebuggingInformation = null;

	public static function showDebuggingInformation(): bool {
		if (IPAddress::$_showDebuggingInformation === null) {
			$clientIP = IPAddress::getClientIP();
			$ipInfo = IPAddress::getIPAddressForIP($clientIP);
			if (!empty($ipInfo)) {
				IPAddress::$_showDebuggingInformation = $ipInfo->showDebuggingInformation;
			} else {
				IPAddress::$_showDebuggingInformation = false;
			}
			if (isset($_REQUEST['noDebug'])) {
				IPAddress::$_showDebuggingInformation = false;
			}
		}
		return IPAddress::$_showDebuggingInformation;
	}

	static $_logTimingInformation = null;

	public static function logTimingInformation() : bool {
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

	public static function logAllQueries(): ?bool {
		if (IPAddress::$_logAllQueries === null) {
			if (!isset($_REQUEST['logQueries'])) {
				IPAddress::$_loadingLogQueryInfo = false;
			} else {
				//There is a potential recursion here that we need to avoid
				if (IPAddress::$_loadingLogQueryInfo) {
					return false;
				} else {
					IPAddress::$_loadingLogQueryInfo = true;
					IPAddress::$_logAllQueries = false;
					$clientIP = IPAddress::getClientIP();
					$ipInfo = IPAddress::getIPAddressForIP($clientIP);
					if (!empty($ipInfo)) {
						IPAddress::$_logAllQueries = empty($ipInfo->logAllQueries) ? false : $ipInfo->logAllQueries;
					} else {
						IPAddress::$_logAllQueries = false;
					}
					IPAddress::$_loadingLogQueryInfo = false;
				}
			}
		}
		return IPAddress::$_logAllQueries;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['locationid']);
		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$allLocations = Location::getLocationListAsObjects(false);
		if (array_key_exists($this->locationid, $allLocations)) {
			$location = $allLocations[$this->locationid];
			$links['locationCode'] = $location->code;
		} else {
			$links['locationCode'] = '';
		}
		return $links;
	}

	public function okToExport(array $selectedFilters): bool {
		$result = parent::okToExport($selectedFilters);
		if ($this->locationid == -1 || in_array($this->locationid, $selectedFilters['locations'])) {
			return true;
		}
		return $result;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting') : void {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (empty($jsonData['locationCode'])) {
			$this->locationid = -1;
		} else {
			$allLocations = Location::getLocationListAsObjects(false);
			$ilsCode = $jsonData['locationCode'];
			if (array_key_exists($ilsCode, $mappings['locations'])) {
				$ilsCode = $mappings['locations'][$ilsCode];
			}
			foreach ($allLocations as $tmpLocation) {
				if ($tmpLocation->code == $ilsCode) {
					$this->locationid = $tmpLocation->locationId;
					break;
				}
			}
		}
	}

	/**
	 * Check if an IPv6 address in hex format is within a range.
	 *
	 * @param string $ipHex The IPv6 address as hex.
	 * @param string $startHex The start of the range as hex.
	 * @param string $endHex The end of the range as hex.
	 * @return bool True if the IP is in the range, false otherwise.
	 */
	public static function ipv6HexInRange(string $ipHex, string $startHex, string $endHex): bool
	{
		// Simple lexicographical comparison works for hex strings of equal length.
		return ($ipHex >= $startHex && $ipHex <= $endHex);
	}
}