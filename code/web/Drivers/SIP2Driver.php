<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/20/2015
 * Time: 10:09 PM
 */

abstract class SIP2Driver implements DriverInterface{
	/** @var sip2 $sipConnection  */
	private $sipConnection = null;

	private function _loadItemSIP2Data($barcode, $itemStatus){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;
		$itemSip2Data = $memCache->get("item_sip2_data_{$barcode}");
		if ($itemSip2Data == false || isset($_REQUEST['reload'])){
			//Check to see if the SIP2 information is already cached
			//TODO: Add Host and Port
			// set in config .in SIP section
			if ($this->initSipConnection()){
				$in = $this->sipConnection->msgItemInformation($barcode);
				$msg_result = $this->sipConnection->get_message($in);
				// Make sure the response is 18 as expected
				if (preg_match("/^18/", $msg_result)) {
					$result = $this->sipConnection->parseItemInfoResponse( $msg_result );
					if (isset($result['variable']['AH']) && $itemStatus != 'i'){
						$itemSip2Data['dueDate'] = $result['variable']['AH'][0];
					}else{
						$itemSip2Data['dueDate'] = '';
					}
					if (isset($result['variable']['CF'][0])){
						$itemSip2Data['holdQueueLength'] = intval($result['variable']['CF'][0]);
					}else{
						$itemSip2Data['holdQueueLength'] = 0;
					}
					$currentLocationSIPField = isset($configArray['Catalog']['currentLocationSIPField']) ? $configArray['Catalog']['currentLocationSIPField'] : 'AP';
					if ($configArray['Catalog']['realtimeLocations'] == true && isset($result['variable'][$currentLocationSIPField][0])){
						//Looks like horizon is returning these backwards via SIP.
						//AQ should be current, but is always returning the same code.
						//AP should be permanent, but is returning the current location
						//global $logger;
						//$logger->log("Permanent location " . $result['variable']['AQ'][0] . " current location " . $result['variable']['AP'][0], PEAR_LOG_INFO);
						$itemSip2Data['locationCode'] = $result['variable'][$currentLocationSIPField][0];
						$itemSip2Data['location'] = mapValue('shelf_location', $itemSip2Data['locationCode']);
					}
					//Override circulation status based on SIP
					if (isset($result['fixed']['CirculationStatus'])){
						$itemSip2Data['status'] = $result['fixed']['CirculationStatus'];
						$itemSip2Data['status_full'] = mapValue('item_status', $result['fixed']['CirculationStatus']);
						$itemSip2Data['availability'] = $result['fixed']['CirculationStatus'] == 3;
					}
				}
				$memCache->set("item_sip2_data_{$barcode}", $itemSip2Data, 0, $configArray['Caching']['item_sip2_data']);
				$timer->logTime("Got due date and hold queue length from SIP 2 for barcode $barcode");
			}else{
				$itemSip2Data = false;
			}
		}
		return $itemSip2Data;
	}
	public function patronLogin($username, $password, $validatedViaSSO) {
		//TODO: Implement $validatedViaSSO
		//Koha uses SIP2 authentication for login.  See
		//The catalog is offline, check the database to see if the user is valid
		global $timer;
		$user = new User();
		$user->cat_username = $username;
		if ($user->find(true)){
			$userValid = false;
			if ($user->cat_username){
				$userValid = true;
			}
			if ($userValid){
				$returnVal = array(
					'id'        => $password,
					'username'  => $user->username,
					'firstname' => $user->firstname,
					'lastname'  => $user->lastname,
					'fullname'  => $user->firstname . ' ' . $user->lastname,     //Added to array for possible display later.
					'cat_username' => $username, //Should this be $Fullname or $patronDump['PATRN_NAME']
					'cat_password' => $password,
					'email' => $user->email,
					'major' => null,
					'college' => null,
					'patronType' => $user->patronType,
					'web_note' => translate('The catalog is currently down.  You will have limited access to circulation information.'));
				$timer->logTime("patron logged in successfully");
				return $returnVal;
			} else {
				$timer->logTime("patron login failed");
				return null;
			}
		} else {
			$timer->logTime("patron login failed");
			return null;
		}
	}
	protected function initSipConnection($host, $post) {
		if ($this->sipConnection == null){
			require_once ROOT_DIR . '/sys/SIP2.php';
			$this->sipConnection = new sip2();
			$this->sipConnection->hostname = $host;
			$this->sipConnection->port = $post;
			if ($this->sipConnection->connect()) {
				//send selfcheck status message
				$in = $this->sipConnection->msgSCStatus();
				$msg_result = $this->sipConnection->get_message($in);
				// Make sure the response is 98 as expected
				if (preg_match("/^98/", $msg_result)) {
					$result = $this->sipConnection->parseACSStatusResponse($msg_result);
					//  Use result to populate SIP2 settings
					$this->sipConnection->AO = $result['variable']['AO'][0]; /* set AO to value returned */
					if (isset($result['variable']['AN'])){
						$this->sipConnection->AN = $result['variable']['AN'][0]; /* set AN to value returned */
					}
					return true;
				}
				$this->sipConnection->disconnect();
			}
			$this->sipConnection = null;
			return false;
		}else{
			return true;
		}
	}
	function __destruct(){
		//Cleanup any connections we have to other systems
		if ($this->sipConnection != null){
			$this->sipConnection->disconnect();
			$this->sipConnection = null;
		}
	}
}