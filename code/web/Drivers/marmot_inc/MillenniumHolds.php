<?php
/**
 * Loads and processes holds for Millennium
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/20/13
 * Time: 11:33 AM
 */
class MillenniumHolds{
	/** @var  Millennium $driver */
	private $driver;
	private $holds = array();

	public function __construct($driver){
		$this->driver = $driver;
	}
	protected function _getHoldResult($holdResultPage){
		$hold_result = array();
		//Get rid of header and footer information and just get the main content
		$matches = array();

		$numMatches = preg_match('/<td.*?class="pageMainArea">(.*)?<\/td>/s', $holdResultPage, $matches);
		//For Encore theme, try with some divs
		if ($numMatches == 0){
			$numMatches = preg_match('/<div class="requestResult">(.*?)<\/div>/s', $holdResultPage, $matches);
			if ($numMatches == 0){
				$numMatches = preg_match('/<div class="srchhelpText">(.*?)<\/div>/s', $holdResultPage, $matches);
			}
		}
		$itemMatches = preg_match('/Choose one item from the list below/', $holdResultPage);

		if ($numMatches > 0 && $itemMatches == 0){
			//$logger->log('Place Hold Body Text\n' . $matches[1], PEAR_LOG_INFO);
			$cleanResponse = preg_replace("^\n|\r|&nbsp;^", "", $matches[1]);
			$cleanResponse = preg_replace("^<br\s*/>^", "\n", $cleanResponse);
			$cleanResponse = trim(strip_tags($cleanResponse));

			if (strpos($cleanResponse, "\n") > 0){
				list($book,$reason)= explode("\n",$cleanResponse);
			}else{
				$book = $cleanResponse;
				$reason = '';
			}

			$hold_result['title'] = $book;
			if (preg_match('/success/', $cleanResponse) && preg_match('/request denied/', $cleanResponse) == 0){
				//Hold was successful
				$hold_result['success'] = true;
				if (!isset($reason) || strlen($reason) == 0){
					$hold_result['message'] = 'Your hold was placed successfully.  It may take up to 45 seconds for the hold to appear on your account.';
				}else{
					$hold_result['message'] = $reason;
				}
			}else if (!isset($reason) || strlen($reason) == 0){
				//Didn't get a reason back.  This really shouldn't happen.
				$hold_result['success'] = false;
				$hold_result['message'] = 'Did not receive a response from the circulation system.  Please try again in a few minutes.';

				//global $logger;
				//$logger->log("Did not receive a response from the circulation system.  Please try again in a few minutes.", PEAR_LOG_WARNING);
				//$logger->log($holdResultPage, PEAR_LOG_WARNING);
			}else{
				//Got an error message back.
				$hold_result['success'] = false;
				$hold_result['message'] = $reason;
			}
		}else{
			if ($itemMatches > 0){
				//Get information about the items that are available for holds
				preg_match_all('/<tr\\s+class="bibItemsEntry">.*?<input type="radio" name="radio" value="(.*?)".*?>.*?<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<td.*?>(.*?)<\/td>.*?<\/tr>/s', $holdResultPage, $itemInfo, PREG_PATTERN_ORDER);
				$items = array();
				for ($i = 0; $i < count($itemInfo[0]); $i++) {
					$items[] = array(
						'itemNumber' => $itemInfo[1][$i],
						'location' => trim(str_replace('&nbsp;', '', $itemInfo[2][$i])),
						'callNumber' => trim(str_replace('&nbsp;', '', $itemInfo[3][$i])),
						'status' => trim(str_replace('&nbsp;', '', $itemInfo[4][$i])),
					);
				}
				$hold_result['items'] = $items;
				if (count($items) > 0){
					$message = 'This title requires item level holds, please select an item to place a hold on.';
				}else{
					$message = 'There are no holdable items for this title.';
				}
			}else{
				$message = 'Unable to contact the circulation system.  Please try again in a few minutes.';
			}
			$hold_result['success'] = false;
			$hold_result['message'] = $message;

			global $logger;
			$logger->log('Place Hold Full HTML\n' . $holdResultPage, PEAR_LOG_INFO);
		}
		return $hold_result;
	}

	public function updateHold($requestId, $patronId, $type){
		$xnum = "x" . $_REQUEST['x'];
		//Strip the . off the front of the bib and the last char from the bib
		if (isset($_REQUEST['cancelId'])){
			$cancelId = $_REQUEST['cancelId'];
		}else{
			$cancelId = substr($requestId, 1, -1);
		}
		$locationId = $_REQUEST['location'];
		$freezeValue = isset($_REQUEST['freeze']) ? 'on' : 'off';
		return $this->updateHoldDetailed($patronId, $type, $xnum, $cancelId, $locationId, $freezeValue);
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($patron, $type, $xNum, $cancelId, $locationId='', $freezeValue='off')
	{
		global $logger;

		// Millennium has a "quirk" where you can still freeze and thaw a hold even if it is in the wrong status.
		// therefore we need to check the current status before we freeze or unfreeze.
		$scope = $this->driver->getDefaultScope();

		if (!isset($xNum)) {
			$xNum = is_array($cancelId) ? $cancelId : array($cancelId);

			// below requests variables should be deprecated as of now. plb 2-9-2015
			// PLB commented out 9-29-2015, starting in 2015.11.6
//			if (isset($_REQUEST['waitingholdselected']) || isset($_REQUEST['availableholdselected'])) {
//				$waitingHolds   = isset($_REQUEST['waitingholdselected']) ? $_REQUEST['waitingholdselected'] : array();
//				$availableHolds = isset($_REQUEST['availableholdselected']) ? $_REQUEST['availableholdselected'] : array();
//				$xNum           = array_merge($waitingHolds, $availableHolds);
//			} else {
//				$xNum = is_array($cancelId) ? $cancelId : array($cancelId);
//			}
		}

		$location = new Location();
		if (isset($locationId) && is_numeric($locationId)) {
			$location->whereAdd("locationId = '$locationId'");
			$location->find();
			if ($location->N == 1) {
				$location->fetch();
				$paddedLocation = str_pad(trim($location->code), 5, " ");
			}
		} else {
			$paddedLocation = isset($locationId) ? $locationId : null;
			$paddedLocation = str_pad(trim($paddedLocation), 5, " ");
		}

		$cancelValue = ($type == 'cancel' || $type == 'recall') ? 'on' : 'off';

			$holds = $this->getMyHolds($patron);
			$combined_holds = array_merge($holds['unavailable'], $holds['available']);

		$POSTVariables = array(
			'updateholdssome' => 'YES',
			'currentsortorder' => 'current_pickup',
		);

		foreach ($xNum as $tmpXnumInfo) {
			if (strpos($tmpXnumInfo, '~') !== false){
				list($tmpBib, $tmpXnum) = explode('~', $tmpXnumInfo);
			}else{
				$tmpBib = $tmpXnumInfo;
				$tmpXnum = '';
			}

			if ($type == 'cancel') {
				$POSTVariables['cancel' . $tmpBib . 'x' . $tmpXnum] = $cancelValue;
			}
			elseif ($type == 'update') {
				$holdForXNum = $this->getHoldByXNum($holds, $tmpXnum);
				$canUpdate   = false;
				if ($holdForXNum != null) {
					if ($freezeValue == 'off') {
						if ($holdForXNum['frozen']) {
							$canUpdate = true;
						}
					} elseif ($freezeValue == 'on') {
						if ($holdForXNum['frozen'] == false && $holdForXNum['freezeable'] == true) {
							$canUpdate = true;
						}
					} elseif ($freezeValue == '') {
						if (isset($paddedLocation) && $holdForXNum['locationUpdateable']) {
							$canUpdate = true;
						}
					}
				}
				if ($canUpdate) {
					if (isset($paddedLocation)) {
						$POSTVariables['loc' . $tmpBib . 'x' . $tmpXnum] = $paddedLocation;
					}
					if (!empty($freezeValue)) {
						$POSTVariables['freeze' . $tmpBib . 'x' . $tmpXnum] = $freezeValue;
					}
				} else {
					$logger->log('Call to update a hold when the update is not needed.', PEAR_LOG_WARNING);
				}
			}

				$tmp_title = '';
				foreach ($combined_holds as $hold) {
					if ($hold['shortId'] == $tmpBib) {
						$tmp_title = $hold['title'];
						break;
					}
				}
				$titles[$tmpBib] = $tmp_title;
		} // End of foreach loop

		$success = false;

		//Login to the patron's account
		$this->driver->_curl_login($patron);

//		//Pause briefly between logging in and posting the actual renewal
//		usleep(150000);
		// moved to millenium driver _curl_login()

		//Issue a post request with the information about what to do with the holds
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username . "/holds";
		$sResult = $this->driver->_curlPostPage($curl_url, $POSTVariables);
		$hold_original_results = $this->parseHoldsPage($sResult, $patron);
			// Note:  Only $hold_original_results above will capture freeze hold errors

		//Go back to the hold page to check make sure our hold was cancelled
		// Don't believe the page reload is necessary. same output as above. plb 2-3-2015
		$sResult = $this->driver->_curlGetPage($curl_url);
		$holds   = $this->parseHoldsPage($sResult, $patron);

		if ($hold_original_results != $holds) { // test if they are the same
			$logger->log('Original Hold Results are different from the second Round!', PEAR_LOG_WARNING);

			$holds = $hold_original_results; // sets to first page-load to get freeze error results
			//TODO : Figure out cases where the page reload of holds is necessary. Because the original results are
			//       necessary for extracting a freeze error/determining that freeze failed.

			//    cancel holds? is it needed to verify when a hold has been canceled successfully
		}

		$combined_holds = array_merge($holds['unavailable'], $holds['available']);
//		$numHoldsEnd = count($combined_holds);
		//Finally, check to see if the update was successful.
		if ($type == 'cancel' || $type == 'recall'){
			$failure_messages = array();
			foreach ($xNum as $tmpXnumInfo){
				list($tmpBib) = explode('~', $tmpXnumInfo);
				foreach ($combined_holds as $hold) {
					$tmpCancelId = strstr($hold['cancelId'], '~', true); // get the cancel id without the position component
					if ($tmpBib == $hold['shortId'] || $tmpBib == $tmpCancelId) { // this hold failed (item still listed as on hold)
						// $tmpBib may be an item id instead of a bib id. in that case, need to check against cancel ids as well.
						if (!empty($hold['title'])) {
							$title = $hold['title'];
						}
						else {
//							$title = (array_key_exists($tmpBib, $titles) && $titles[$tmpBib] != '') ? $titles[$tmpBib] : 'an item';
							$title = (!empty($titles[$tmpBib])) ? $titles[$tmpBib] : 'an item';
						}
						$failure_messages[$tmpXnumInfo] = "The hold for $title could not be cancelled.  Please try again later or see your librarian.";
							// use original id as index so that javascript functions can pick out failed cancels
						break;
					}
				}
			}
			$success = empty($failure_messages);
			if ($success) $logger->log('Cancelled ok', PEAR_LOG_INFO);

		}
		elseif ($type == 'update') {
			// Thaw Hold
			if ($freezeValue == 'off') {
				// TODO Collect errors here.
				$success = true;
//				$success = empty($failure_messages);
//				if ($success) $logger->log('Thawed ok', PEAR_LOG_INFO);
			}
			// Freeze Hold
			elseif ($freezeValue == 'on') {
				$failure_messages = array();
				foreach ($xNum as $tmpXnumInfo) {
					list($tmpBib) = explode('~', $tmpXnumInfo);
					foreach ($combined_holds as $hold) {
						if ($tmpBib == $hold['shortId']) { // this hold failed (item still on hold)
							$title = (array_key_exists($tmpBib, $titles) && $titles[$tmpBib] != '') ? $titles[$tmpBib] : 'an item';

							if (isset($hold['freezeError'])) {
								$failure_messages[$tmpXnumInfo] = "The hold for $title could not be ". translate('frozen') .'.  Please try again later or see your librarian.';
								// use original id as index so that javascript functions can pick out failed cancels
							}

							break;
						}
					}
				}
				$success = empty($failure_messages);
				if ($success) $logger->log('Froze Hold ok', PEAR_LOG_INFO);
			}
			// Change Pick-up Location
			elseif ($freezeValue == '') {
				// TODO Collect errors here.
				$success = true;
//				$success = empty($failure_messages);
//				if ($success) $logger->log('Change Pick-up Location ok', PEAR_LOG_INFO);
			}
		}

		//Make sure to clear any cached data
		/** @var Memcache $memCache */
		global $memCache;
		$memCache->delete("patron_dump_{$this->driver->_getBarcode()}");
		usleep(250); // Pause for Hold Cancels, so that sierra will have updated the canceled hold.

		// Return Results
		$isPlural = count($xNum) > 1;
		global $analytics;
		$title_list = is_array($titles) ? implode(', ', $titles) : $titles;
		if ($type == 'cancel' || $type == 'recall'){
			if ($success){ // All were successful
				$analytics->addEvent('ILS Integration', 'Hold Cancelled', $title_list);
				return array(
					'title' => $titles,
					'success' => true,
					'message' => 'Your hold'.($isPlural ? 's were' : ' was' ).' cancelled successfully.');
			} else { // at least one failure
				$analytics->addEvent('ILS Integration', 'Hold Not Cancelled', $title_list);
				return array(
					'title' => $titles,
					'success' => false,
					'message' => $failure_messages
				);
			}
		}
		elseif ($type == 'update') {
			// Thaw Hold
			if ($freezeValue == 'off') {
				return array(
					'title' => $titles,
					'success' => true,
					'message' => 'Your hold'.($isPlural ? 's were' : ' was' ).' '. translate('thawed') .' successfully.');
			}
			// Freeze Hold
			elseif ($freezeValue == 'on') {
				//TODO check for error messages
				if ($success) { // All were successful
					$analytics->addEvent('ILS Integration', 'Hold Frozen', $title_list);
					return array(
						'title' => $titles,
						'success' => true,
						'message' => 'Your hold' . ($isPlural ? 's were' : ' was') . ' '. translate('frozen') .' successfully.');
				} else { // at least one failure
					$analytics->addEvent('ILS Integration', 'Hold Not Frozen', $title_list);
					return array(
						'title' => $titles,
						'success' => false,
						'message' => $failure_messages
					);
				}
			}
			// Change Pick-up Location
			elseif ($freezeValue == '') {
				//TODO check for error messages
				return array(
					'title' => $titles,
					'success' => true,
					'message' => 'Your hold'.($isPlural ? 's were' : ' was' ).' updated successfully.');
			}

		}

		else{
			$analytics->addEvent('ILS Integration', 'Hold(s) Updated', $title_list);
			return array(
				'title' => $titles,
				'success' => true,
				'message' => 'Your hold'.($isPlural ? 's were' : ' was' ).' updated successfully.');
		}
	}

	/**
	 * @param $pageContents string  Tbe raw HTML to be parsed
	 * @param $patron       User    The user who owns the holds
	 * @return array
	 */
	public function parseHoldsPage($pageContents, $patron){
		$userLabel = $patron->getNameAndLibraryLabel();

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'=> $availableHolds,
			'unavailable' => $unavailableHolds
		);

		//Fix presentation error for notice when freezing a hold has failed
		$pageContents = str_replace('<em>This hold can not be frozen.</em></tr>', '<em>This hold can not be frozen.</em></td></tr>', $pageContents);

		//Get the headers from the table
		preg_match_all('/<th\\s+class="patFuncHeaders">\\s*([\\w\\s]*?)\\s*<\/th>/si', $pageContents, $result, PREG_SET_ORDER);
		$sKeys = array();
		for ($matchi = 0; $matchi < count($result); $matchi++) {
			$sKeys[] = $result[$matchi][1];
		}

		//Get the rows for the table
		preg_match_all('/<tr\\s+class="patFuncEntry(?: on_ice)?">(.*?)<\/tr>/si', $pageContents, $result, PREG_SET_ORDER);
		$sRows = array();
		for ($matchi = 0; $matchi < count($result); $matchi++) {
			$sRows[] = $result[$matchi][1];
		}

		$sCount = 0;

		foreach ($sRows as $sRow) {
			preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $sRow, $result, PREG_SET_ORDER);
			$sCols = array();
			for ($matchi = 0; $matchi < count($result); $matchi++) {
				$sCols[] = $result[$matchi][1];
			}


			//$sCols = preg_split("/<t(h|d)([^>]*)>/",$sRow);
			$curHold= array();
			$curHold['create'] = null;
			$curHold['reqnum'] = null;
			$curHold['holdSource'] = 'ILS';
			$curHold['userId'] = $patron->id;
			$curHold['user'] = $userLabel;

			//Holds page occasionally has a header with number of items checked out.
			for ($i=0; $i < sizeof($sCols); $i++) {
				$sCols[$i] = str_replace("&nbsp;"," ",$sCols[$i]);
				$sCols[$i] = preg_replace ("/<br+?>/"," ", $sCols[$i]);
				$sCols[$i] = html_entity_decode(trim($sCols[$i]));
				//print_r($scols[$i]);
				/*if ($sCount <= 1) {
					$sKeys[$i] = $sCols[$i];
				} else if ($sCount > 1) {*/

					if ($sKeys[$i] == "CANCEL") { //Only check Cancel key, not Cancel if not filled by
						//Extract the id from the checkbox
						$matches = array();
						$numMatches = preg_match_all('/.*?cancel(.*?)x(\\d\\d).*/s', $sCols[$i], $matches);
						if ($numMatches > 0){
							$curHold['renew'] = "BOX";
							$curHold['cancelable'] = true;
							$curHold['itemId'] = $matches[1][0];
							$curHold['xnum'] = $matches[2][0];
							$curHold['cancelId'] = $matches[1][0] . '~' . $matches[2][0];
						}else{
							$curHold['cancelable'] = false;
						}
					}

					elseif (stripos($sKeys[$i],"TITLE") > -1) {
						if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $sCols[$i], $matches)) {
							$shortId = $matches[1];
							$bibid = '.' . $matches[1] . $this->driver->getCheckDigit($shortId);
							$title = strip_tags($matches[2]);
						}elseif (preg_match('/.*<a href=".*?\/record\/C__R(.*?)\\?.*?">(.*?)<\/a>.*/si', $sCols[$i], $matches)){
							$shortId = $matches[1];
							$bibid = '.' . $matches[1] . $this->driver->getCheckDigit($shortId);
							$title = strip_tags($matches[2]);
						}else{
							//This happens for prospector titles
							$bibid = '';
							$shortId = '';
							$title = trim($sCols[$i]);
							/*global $configArray;
							if ($configArray['System']['debug']){
								echo("Unexpected format in title column.  Got " . htmlentities($sCols[$i]) . "<br/>");
							}*/
						}
						if (preg_match('/.*<span class="patFuncVol">(.*?)<\/span>.*/si', $sCols[$i], $matches)){
							$curHold['volume'] = $matches[1];
						}

						$curHold['id'] = $bibid;
						$curHold['recordId'] = $bibid;
						$curHold['shortId'] = $shortId;
						$curHold['title'] = $title;
					}
					elseif (stripos($sKeys[$i],"Ratings") > -1) {
						$curHold['request'] = "STARS";
					}

					elseif (stripos($sKeys[$i],"PICKUP LOCATION") > -1) {

						//Extract the current location for the hold if possible
						$matches = array();
//						if (preg_match('/<select\\s+name=loc(.*?)x(\\d\\d).*?<option\\s+value="([a-z0-9+]{1,5})"\\s+selected="selected">.*/s', $sCols[$i], $matches)) {
						if (preg_match('/<select\\s+name=loc(.*?)x(\\d\\d).*?<option\\s+value="([a-z0-9+ ]{1,5})"\\s+selected="selected">.*/s', $sCols[$i], $matches)) {
									// new regex above includes a space in the value capture now. This seems to be the result of our setting of the pickup location on the place hold side.
									// plb 8-12-2015
							$curHold['locationId'] = $matches[1];
							$curHold['locationXnum'] = $matches[2];
							$curPickupBranch = new Location();
							$curPickupBranch->whereAdd("code = '{$matches[3]}'");
							$curPickupBranch->find(1);
							if ($curPickupBranch->N > 0) {
								$curPickupBranch->fetch();
								$curHold['currentPickupId'] = $curPickupBranch->locationId;
								$curHold['currentPickupName'] = $curPickupBranch->displayName;
								$curHold['location'] = $curPickupBranch->displayName;
							}
							$curHold['locationUpdateable'] = true;

							//Return the full select box for reference.
							$curHold['locationSelect'] = $sCols[$i];
						}elseif (preg_match('/<select.*?>/', $sCols[$i])){
							//Updateable, but no location set
							$curHold['locationUpdateable'] = true;
							$curHold['location'] = 'Not Set';
						}else{
							$curHold['location'] = trim(strip_tags($sCols[$i], '<select><option>'));
							//Trim the carrier code if any
							if (preg_match('/.*\s[\w\d]{4}$/', $curHold['location'])){
								$curHold['location'] = substr($curHold['location'], 0, strlen($curHold['location']) - 5);
							}
							$curHold['currentPickupName'] = $curHold['location'];
							$curHold['locationUpdateable'] = false;
						}
					}

					elseif (stripos($sKeys[$i],"STATUS") > -1) {
						$status = trim(strip_tags($sCols[$i]));
						$status = str_replace('You will be notified when your request/hold is ready for pickup.', '', $status); // strip out explainer text for LION
						$status = strtolower($status);
						$status = ucwords($status);
						if ($status !="&nbsp"){
							$curHold['status'] = $status;
							if (preg_match('/READY.*(\d{2}-\d{2}-\d{2})/i', $status, $matches)){
								$curHold['status'] = 'Ready';
								//Get expiration date
								$exipirationDate = $matches[1];
								$expireDate = DateTime::createFromFormat('m-d-y', $exipirationDate);
								$curHold['expire'] = $expireDate->getTimestamp();

							}elseif (preg_match('/READY\sFOR\sPICKUP/i', $status, $matches)){
								$curHold['status'] = 'Ready';
							}elseif (preg_match('/in\stransit/i', $status, $matches)){
								$curHold['status'] = 'In Transit';
							}elseif (preg_match('/\d+\sof\s\d+\sholds/i', $status, $matches)){
								$curHold['status'] = $status;
							}elseif (preg_match('/Hold Being Shelved/i', $status, $matches)){
								$curHold['status'] = $status;
							}elseif ($status == 'Available ') {
								$curHold['status'] = 'Ready';
							}else{
								#PK-778 - Don't attempt to show status for anything other than ready for pickup since Millennium/Sierra statuses are confusing
								$curHold['status'] = 'Pending';
							}
						}else{
							#PK-778 - Don't attempt to show status for anything other than ready for pickup since Millennium/Sierra statuses are confusing
							$curHold['status'] = "Pending";
						}
						$matches = array();
						$curHold['renewError'] = false;
						if (preg_match('/.*DUE\\s(\\d{2}-\\d{2}-\\d{2}).*(?:<font color="red">\\s*(.*)<\/font>).*/s', $sCols[$i], $matches)){
							//Renew error
							$curHold['renewError'] = $matches[2];
							$curHold['statusMessage'] = $matches[2];
						}else{
							if (preg_match('/.*DUE\\s(\\d{2}-\\d{2}-\\d{2})\\s(.*)?/s', $sCols[$i], $matches)){
								$curHold['statusMessage'] = $matches[2];
							}
						}
						//$logger->log('Status for item ' . $curHold['id'] . '=' . $sCols[$i], PEAR_LOG_INFO);
					}
					elseif (stripos($sKeys[$i],"CANCEL IF NOT FILLED BY") > -1) {
						$extractedDate = strip_tags($sCols[$i]);
						$extractedDate = date_create_from_format('m-j-y', $extractedDate);
						$curHold['automaticCancellation'] = $extractedDate ? $extractedDate->getTimestamp() : null;
					}
					elseif (stripos($sKeys[$i],"FREEZE") > -1) {
						$matches = array();
						$curHold['frozen'] = false;
						if (preg_match('/<input.*name="freeze(.*?)"\\s*(\\w*)\\s*\/>/', $sCols[$i], $matches)){
							$curHold['freezeable'] = true;
							if (strlen($matches[2]) > 0){
								$curHold['frozen'] = true;
								if ($curHold['status'] == 'Pending'){
									$curHold['status'] = 'Frozen';
								}else{
									$curHold['status'] = 'Frozen (' . $curHold['status'] . ')';
								}
							}
						} elseif (preg_match('/This hold can\s?not be frozen/i', $sCols[$i], $matches)){
							//If we detect an error Freezing the hold, save it so we can report the error to the user later.
							$shortId = str_replace('.b', 'b', $curHold['id']);
							//TODO: Return as part of results. Using the Session variable no longer workable path to user feedback.
							$_SESSION['freezeResult'][$shortId]['message'] = $sCols[$i];
							$_SESSION['freezeResult'][$shortId]['success'] = false;
							$curHold['freezeError'] = strip_tags($sCols[$i]);
						}else{
							$curHold['freezeable'] = false;
						}
					}
				//}
			} //End of columns

			//Check to see if this is a volume level hold
			if (substr($curHold['cancelId'], 0, 1) == 'j'){
				//This is a volume level hold
				$volumeId = '.' . substr($curHold['cancelId'], 0, strpos($curHold['cancelId'], '~'));
				$volumeId .= $this->driver->getCheckDigit($volumeId);
				require_once ROOT_DIR . '/Drivers/marmot_inc/IlsVolumeInfo.php';
				$volumeInfo = new IlsVolumeInfo();
				$volumeInfo->volumeId = $volumeId;
				if ($volumeInfo->find(true)){
					$curHold['volume'] = $volumeInfo->displayLabel;
				}
			}

			//Check to see if the patron is the only one in the hold queue
			//if so they cannot freeze the hold
			if (isset($curHold['status'])){
				if ($curHold['status'] == 'Pending'){
					$freezable = true;
					if (isset($curHold['freezeable'])){
						$freezable = $curHold['freezeable'];
					}
					$curHold['freezeable'] = $freezable && $this->driver->allowFreezingPendingHolds();
				}else if (preg_match('/\d+ Of (\d+) Holds?/i', $curHold['status'], $matches)){
					if ($matches[1] == 1){
						$curHold['freezeable'] = false;
					}
				}
			}

			//add to the appropriate array
			if (!isset($curHold['status']) || (strcasecmp($curHold['status'], "ready") != 0 && strcasecmp($curHold['status'], "hold being shelved") != 0)){
				$holds['unavailable'][$curHold['holdSource'] . $curHold['itemId'] . $curHold['cancelId'] . $userLabel] = $curHold;
			}else{
				$holds['available'][$curHold['holdSource'] . $curHold['itemId'] . $curHold['cancelId']. $userLabel] = $curHold;
			}

			$sCount++;

		}//End of the row

		return $holds;
	}


	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron    The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getMyHolds($patron) {
		global $timer;
		//Load the information from millennium using CURL
		$sResult = $this->driver->_fetchPatronInfoPage($patron, 'holds');
		$timer->logTime("Got holds page from Millennium");

		$holds = $this->parseHoldsPage($sResult, $patron);
		$timer->logTime("Parsed Holds page");

		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		foreach($holds as $section => $holdSections){
			foreach($holdSections as $key => $hold){

				disableErrorHandler();
				$recordDriver = new MarcRecord($this->driver->accountProfile->recordSource . ":" . $hold['recordId']);
				if ($recordDriver->isValid()){
					$hold['id'] = $recordDriver->getUniqueID();
					$hold['shortId'] = $recordDriver->getShortId();
					//Load title, author, and format information about the title
					$hold['title'] = $recordDriver->getTitle();
					$hold['sortTitle'] = $recordDriver->getSortableTitle();
					$hold['author'] = $recordDriver->getAuthor();
					$hold['format'] = $recordDriver->getFormat();
					$hold['isbn'] = $recordDriver->getCleanISBN();  //TODO these may not be used anywhere now that the links are built here, have to check
					$hold['upc'] = $recordDriver->getCleanUPC();    //TODO these may not be used anywhere now that the links are built here, have to check
					$hold['format_category'] = $recordDriver->getFormatCategory();

					//Load rating information
					$hold['ratingData'] = $recordDriver->getRatingData();
					$hold['link'] = $recordDriver->getRecordUrl();
					$hold['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
				}
				$holds[$section][$key] = $hold;

				enableErrorHandler();
			}
		}

		if (!isset($holds['available'])){
			$holds['available'] = array();
		}
		if (!isset($holds['unavailable'])){
			$holds['unavailable'] = array();
		}

		$timer->logTime("Processed hold pagination and sorting");
		return $holds;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User    $patron     The User to place a hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate) {
		global $logger;
		global $configArray;
		global $library;

		$bib1= $recordId;
		if (substr($bib1, 0, 1) != '.'){
			$bib1 = '.' . $bib1;
		}

		$bib = substr(str_replace('.b', 'b', $bib1), 0, -1);
		if (strlen($bib) == 0){
			return array(
				'success' => false,
				'message' => 'A valid record id was not provided. Please try again.');
		}

		//Get the title of the book.
		// Retrieve Full Marc Record
		require_once ROOT_DIR . '/RecordDrivers/Factory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->driver->accountProfile->recordSource . ':' . $bib1);
		if (!$record) {
			$logger->log('Place Hold: Failed to get Marc Record', PEAR_LOG_INFO);
			$title = null;
		}else{
			$title = $record->getTitle();
		}

		// Offline Holds
		global $offlineMode;
		if ($offlineMode){
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold = new OfflineHold();
			$offlineHold->bibId = $bib1;
			$offlineHold->patronBarcode = $patron->getBarcode();
			$offlineHold->patronId = $patron->id;
			$offlineHold->timeEntered = time();
			$offlineHold->status = 'Not Processed';
			if ($offlineHold->insert()){
				return array(
					'title' => $title,
					'bib' => $bib1,
					'success' => true,
					'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			}else{
				return array(
					'title' => $title,
					'bib' => $bib1,
					'success' => false,
					'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}


		}else{
			if (!empty($_REQUEST['canceldate'])){
				$date = $_REQUEST['canceldate'];
			}else{
				if ($library->defaultNotNeededAfterDays == 0){
					//Default to a date 6 months (half a year) in the future.
					$sixMonthsFromNow = time() + 182.5 * 24 * 60 * 60;
					$date = date('m/d/Y', $sixMonthsFromNow);
				}else{
					//Default to a date 6 months (half a year) in the future.
					$nnaDate = time() + $library->defaultNotNeededAfterDays * 24 * 60 * 60;
					$date = date('m/d/Y', $nnaDate);
				}
			}

			list($Month, $Day, $Year)=explode("/", $date);

			//Make sure to connect via the driver so cookies will be correct
			$this->driver->_curl_connect();

//			curl_setopt($curl_connection, CURLOPT_POST, true);

			$loginResult = $this->driver->_curl_login($patron);

			$curl_url = $this->driver->getVendorOpacUrl() . "/search/.$bib/.$bib/1,1,1,B/request~$bib";

			/** @var Library $librarySingleton */
			global $librarySingleton;
			$patronHomeBranch = $librarySingleton->getPatronHomeLibrary($patron);
			if ($patronHomeBranch->defaultNotNeededAfterDays != -1){
				$post_data['needby_Month']= $Month;
				$post_data['needby_Day']= $Day;
				$post_data['needby_Year']=$Year;
			}

			$post_data['submit.x']="35";
			$post_data['submit.y']="21";
			$post_data['submit']="submit";
			$post_data['locx00']= str_pad($pickupBranch, 5); // padded with spaces, which will get url-encoded into plus signs by httpd_build_query() in the _curlPostPage() method.
			if (!empty($itemId) && $itemId != -1){
				$post_data['radio']=$itemId;
			}
			$post_data['x']="48";
			$post_data['y']="15";
			//MDN 8/14 lt is apparently not required for placing a hold although it is required for login.
			/*if ($lt != null){
				$post_data['lt'] = $lt;
				$post_data['_eventId'] = 'submit';
			}*/

			$sResult = $this->driver->_curlPostPage($curl_url, $post_data);

			$logger->log("Placing hold $recordId : $title", PEAR_LOG_INFO);

			$sResult = preg_replace("/<!--([^(-->)]*)-->/","",$sResult);

			//Parse the response to get the status message
			$hold_result = $this->_getHoldResult($sResult);
			$hold_result['title']  = $title;
			$hold_result['bid'] = $bib1;
			global $analytics;
			if ($analytics){
				if ($hold_result['success'] == true){
					$analytics->addEvent('ILS Integration', 'Successful Hold', $title);
				}else{
					$analytics->addEvent('ILS Integration', 'Failed Hold', $hold_result['message'] . ' - ' . $title);
				}
			}
			return $hold_result;
		}
	}

	/**
	 * Place Volume Hold
	 *
	 * This is responsible for both placing volume level holds.
	 *
	 * @param   User    $patron       The User to place a hold for
	 * @param   string  $recordId     The id of the bib record
	 * @param   string  $volumeId     The id of the volume to hold
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch) {
		global $logger;
		global $configArray;

		$bib1= $recordId;
		if (substr($bib1, 0, 1) != '.'){
			$bib1 = '.' . $bib1;
		}

		$bib = substr(str_replace('.b', 'b', $bib1), 0, -1);
		if (strlen($bib) == 0){
			return array(
					'success' => false,
					'message' => 'A valid record id was not provided. Please try again.');
		}

		//Get the title of the book.
		// Retrieve Full Marc Record
		require_once ROOT_DIR . '/RecordDrivers/Factory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->driver->accountProfile->recordSource . ':' . $bib1);
		if (!$record) {
			$logger->log('Place Hold: Failed to get Marc Record', PEAR_LOG_INFO);
			$title = null;
		}else{
			$title = $record->getTitle();
		}

		// Offline Holds
		global $offlineMode;
		if ($offlineMode){
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold = new OfflineHold();
			$offlineHold->bibId = $bib1;
			$offlineHold->patronBarcode = $patron->getBarcode();
			$offlineHold->patronId = $patron->id;
			$offlineHold->timeEntered = time();
			$offlineHold->status = 'Not Processed';
			if ($offlineHold->insert()){
				return array(
						'title' => $title,
						'bib' => $bib1,
						'success' => true,
						'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			}else{
				return array(
						'title' => $title,
						'bib' => $bib1,
						'success' => false,
						'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}


		}else{
			if (!empty($_REQUEST['canceldate'])){
				$date = $_REQUEST['canceldate'];
			}else{
				global $library;
				if ($library->defaultNotNeededAfterDays == 0){
					//Default to a date 6 months (half a year) in the future.
					$sixMonthsFromNow = time() + 182.5 * 24 * 60 * 60;
					$date = date('m/d/Y', $sixMonthsFromNow);
				}else{
					//Default to a date 6 months (half a year) in the future.
					$nnaDate = time() + $library->defaultNotNeededAfterDays * 24 * 60 * 60;
					$date = date('m/d/Y', $nnaDate);
				}
			}

			list($Month, $Day, $Year)=explode("/", $date);

			//Make sure to connect via the driver so cookies will be correct
			$this->driver->_curl_connect();

//			curl_setopt($curl_connection, CURLOPT_POST, true);

			$loginResult = $this->driver->_curl_login($patron);

			$volumeId = substr(str_replace('.j', 'j', $volumeId), 0, -1);
			$curl_url = $this->driver->getVendorOpacUrl() . "/search/.$bib/.$bib/1,1,1,B/request~$bib&jrecnum=$volumeId";

			/** @var Library $librarySingleton */
			global $librarySingleton;
			$patronHomeBranch = $librarySingleton->getPatronHomeLibrary($patron);
			if ($patronHomeBranch->defaultNotNeededAfterDays != -1){
				$post_data['needby_Month']= $Month;
				$post_data['needby_Day']= $Day;
				$post_data['needby_Year']=$Year;
			}

			$post_data['submit.x']="35";
			$post_data['submit.y']="21";
			$post_data['submit']="submit";
			$post_data['locx00']= str_pad($pickupBranch, 5); // padded with spaces, which will get url-encoded into plus signs by httpd_build_query() in the _curlPostPage() method.
			if (!empty($itemId) && $itemId != -1){
				$post_data['radio']=$itemId;
			}
			$post_data['x']="48";
			$post_data['y']="15";
			//MDN 8/14 lt is apparently not required for placing a hold although it is required for login.
			/*if ($lt != null){
				$post_data['lt'] = $lt;
				$post_data['_eventId'] = 'submit';
			}*/

			$sResult = $this->driver->_curlPostPage($curl_url, $post_data);

			$logger->log("Placing hold $recordId : $title", PEAR_LOG_INFO);

			$sResult = preg_replace("/<!--([^(-->)]*)-->/","",$sResult);

			//Parse the response to get the status message
			$hold_result = $this->_getHoldResult($sResult);
			$hold_result['title']  = $title;
			$hold_result['bid'] = $bib1;
			global $analytics;
			if ($analytics){
				if ($hold_result['success'] == true){
					$analytics->addEvent('ILS Integration', 'Successful Hold', $title);
				}else{
					$analytics->addEvent('ILS Integration', 'Failed Hold', $hold_result['message'] . ' - ' . $title);
				}
			}
			return $hold_result;
		}
	}

	private function getHoldByXNum($holds, $tmpXnum) {
		$unavailableHolds = $holds['unavailable'];
		foreach ($unavailableHolds as $hold){
			if ($hold['xnum'] == $tmpXnum){
				return $hold;
			}
		}
		return null;
	}
}
