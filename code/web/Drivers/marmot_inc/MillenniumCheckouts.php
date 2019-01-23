<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/20/13
 * Time: 2:42 PM
 */

class MillenniumCheckouts {
	/** @var  Millennium $driver */
	private $driver;

	public function __construct($driver){
		$this->driver = $driver;
	}

	private function extract_title_from_row($row) {
		//Standard Millennium WebPAC
		if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $row, $matches)) {
			return trim(strip_tags($matches[2]));
		}
		//Encore
		elseif (preg_match('/<a href=".*?\/record\/C__R(.*?)\?.*?"patFuncTitleMain">(.*?)<\/span>/si', $row, $matches)) {
			// This Regex developed using output from Nashville webpac.  plb 3-31-2016
			//TODO: may need to be modified to match both patFuncTitle & patFuncTitleMain, using '/<a href=".*?\/record\/C__R(.*?)\?.*?"patFuncTitle.*?">(.*?)<\/span>/si'. plb 3-31-2016
			return trim(strip_tags($matches[2]));
		}
		// Prospector Holds in Sierra WebPAC
		elseif (preg_match('/<td.*?"patFuncTitle">(.*?)<\/td>/si', $row, $matches)){
			// This Regex developed using output from Marmot Sierra webpac.  plb 3-31-2016
			// Note: this regex doesn't extract a record id like the two above do.
			return trim(strip_tags($matches[1]));
		}
		// Fallback option
		else{
			return trim(strip_tags($row));
		}
	}


	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user    The user to load transactions for
	 *
	 * @return mixed        Array of the patron's transactions on success,
	 * PEAR_Error otherwise.
	 * @access public
	 */
	public function getMyCheckouts($user) {
		$checkedOutTitles = array();
		global $timer;
		$timer->logTime("Ready to load checked out titles from Millennium");
		//Load the information from millennium using CURL
		$sResult = $this->driver->_fetchPatronInfoPage($user, 'items');
		$timer->logTime("Loaded checked out titles from Millennium");
		if ($sResult) {

			$sResult = preg_replace("/<[^<]+?>\\W<[^<]+?>\\W\\d* ITEM.? CHECKED OUT<[^<]+?>\\W<[^<]+?>/i", "", $sResult);

			$s = substr($sResult, stripos($sResult, 'patFunc'));
			$s = substr($s, strpos($s, ">") + 1);
			$s = substr($s, 0, stripos($s, "</table"));
			$s = preg_replace("/<br \\/>/", "", $s);

			$sRows            = preg_split("/<tr([^>]*)>/", $s);
			$sCount           = 0;
			$sKeys            = array_pad(array(), 10, "");

			//Get patron's location to determine if renewals are allowed.
			global $locationSingleton;
			/** @var Location $patronLocation */
			$patronLocation = $locationSingleton->getUserHomeLocation();
			if (isset($patronLocation)) {
				$patronPType    = $user->patronType;
				$patronCanRenew = false;
				if ($patronLocation->ptypesToAllowRenewals == '*') {
					$patronCanRenew = true;
				} else if (preg_match("/^({$patronLocation->ptypesToAllowRenewals})$/", $patronPType)) {
					$patronCanRenew = true;
				}
			} else {
				$patronCanRenew = true;
			}
			$timer->logTime("Determined if patron can renew");

			foreach ($sRows as $srow) {
				$scols    = preg_split("/<t(h|d)([^>]*)>/", $srow);
				$curTitle = array();
				for ($i = 0; $i < sizeof($scols); $i++) {
					$scols[$i] = str_replace("&nbsp;", " ", $scols[$i]);
					$scols[$i] = preg_replace("/<br+?>/", " ", $scols[$i]);
					$scols[$i] = html_entity_decode(trim(substr($scols[$i], 0, stripos($scols[$i], "</t"))));
					//print_r($scols[$i]);
					if ($sCount == 1) {
						$sKeys[$i] = $scols[$i];
					} else if ($sCount > 1) {

						if (stripos($sKeys[$i], "TITLE") > -1) {
							if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $scols[$i], $matches)) {
								//Standard Millennium WebPAC
								$shortId = $matches[1];
								$bibid   = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
								$title   = strip_tags($matches[2]);
							} elseif (preg_match('/<a href=".*?\/record\/C__R(.*?)\?.*?"patFuncTitleMain">(.*?)<\/span>/si', $scols[$i], $matches)) {
								//Encore
								$shortId = $matches[1];
								$bibid   = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
								$title   = strip_tags($matches[2]);
							} else {
								$title   = strip_tags($scols[$i]);
								$shortId = '';
								$bibid   = '';
							}
							$curTitle['checkoutSource'] = 'ILS';
							$curTitle['shortId']        = $shortId;
							$curTitle['id']             = $bibid;
							$curTitle['title']          = utf8_encode($title);
							if (preg_match('/.*<span class="patFuncVol">(.*?)<\/span>.*/si', $scols[$i], $matches)) {
								$curTitle['volume'] = $matches[1];
							}
						}

						if (stripos($sKeys[$i], "STATUS") > -1) {
							// $sret[$scount-2]['dueDate'] = strip_tags($scols[$i]);
							$due        = trim(str_replace("DUE", "", strip_tags($scols[$i])));
							$renewCount = 0;
							if (preg_match('/FINE\(\s*up to now\) (\$\d+\.\d+)/i', $due, $matches)) {
								$curTitle['fine'] = trim($matches[1]);
							}
							if (preg_match('/(.*)Renewed (\d+) time(?:s)?/i', $due, $matches)) {
								$due        = trim($matches[1]);
								$renewCount = $matches[2];
							} else if (preg_match('/(.*)\+\d+ HOLD.*/i', $due, $matches)) {
								$due = trim($matches[1]);
							}
							if (preg_match('/(\d{2}-\d{2}-\d{2})/', $due, $dueMatches)) {
								$dateDue = DateTime::createFromFormat('m-d-y', $dueMatches[1]);
								if ($dateDue) {
									$dueTime = $dateDue->getTimestamp();
								} else {
									$dueTime = null;
								}
							} else {
								$dueTime = strtotime($due);
							}
							if ($dueTime != null) {
								$curTitle['dueDate'] = $dueTime;
							}
							$curTitle['renewCount'] = $renewCount;

						}

						if (stripos($sKeys[$i], "BARCODE") > -1) {
							$curTitle['barcode'] = strip_tags($scols[$i]);
						}


						if (stripos($sKeys[$i], "RENEW") > -1) {
							$matches = array();
							if (preg_match('/<input\s*type="checkbox"\s*name="renew(\d+)"\s*id="renew(\d+)"\s*value="(.*?)"\s*\/>/', $scols[$i], $matches)) {
								$curTitle['canrenew']       = $patronCanRenew;
								$curTitle['itemindex']      = $matches[1];
								$curTitle['itemid']         = $matches[3];
								$curTitle['renewIndicator'] = $curTitle['itemid'] . '|' . $curTitle['itemindex'];
								$curTitle['renewMessage']   = '';
							} else {
								$curTitle['canrenew'] = false;
							}

						}


						if (stripos($sKeys[$i], "CALL NUMBER") > -1) {
							$curTitle['request'] = "null";
						}
					}

				}
				if ($sCount > 1) {
					//Get additional information from the MARC Record
					if (isset($curTitle['shortId']) && strlen($curTitle['shortId']) > 0) {
						$checkDigit           = $this->driver->getCheckDigit($curTitle['shortId']);
						$curTitle['recordId'] = '.' . $curTitle['shortId'] . $checkDigit;
						$curTitle['id']       = '.' . $curTitle['shortId'] . $checkDigit;
						require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
						$recordDriver = new MarcRecord($this->driver->accountProfile->recordSource . ":" . $curTitle['recordId']);
						if ($recordDriver->isValid()) {
							$curTitle['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
							$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
							$curTitle['ratingData']    = $recordDriver->getRatingData();
							$curTitle['format']        = $recordDriver->getPrimaryFormat();
							$curTitle['author']        = $recordDriver->getPrimaryAuthor();
							//Always use title from the index since classic will show 240 rather than 245
							$curTitle['title']         = $recordDriver->getTitle();
							$curTitle['title_sort']    = $recordDriver->getSortableTitle();
							$curTitle['link']          = $recordDriver->getLinkUrl();
						} else {
							$curTitle['coverUrl']      = "";
							$curTitle['groupedWorkId'] = "";
							$curTitle['format']        = "Unknown";
							$curTitle['author']        = "";
						}
					}
					$checkedOutTitles[] = $curTitle;
				}

				$sCount++;
			}
			$timer->logTime("Parsed checkout information");

		}
		return $checkedOutTitles;
	}

	public function renewAll($patron){
		global $logger;
		$driver = &$this->driver;

		//Setup the call to Millennium
		$barcode = $driver->_getBarcode($patron);
		$patronDump = $driver->_getPatronDump($barcode);
		$curCheckedOut = $patronDump['CUR_CHKOUT'];

		//Login to the patron's account
		$driver->_curl_login($patron);

		//Pause briefly between logging in and posting the actual renewal
//		usleep(150000);
		// moved to millenium driver _curl_login()

		//Go to the items page
		$scope = $driver->getDefaultScope();
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] ."/items";
		$checkedOutPageText = $driver->_curlGetPage($curl_url); // TODO Initial page load needed?

		//Post renewal information
		$renewAllPostVariables = array(
			'currentsortorder' => 'current_checkout',
			'renewall' => 'YES',
		);

		$checkedOutPageText = $driver->_curlPostPage($curl_url, $renewAllPostVariables);
		//$logger->log("Result of Renew All\r\n" . $checkedOutPageText, PEAR_LOG_INFO);

		//Clear the existing patron info and get new information.
		$renew_result = array(
			'success' => false,
			'message' => array(),
			'Renewed' => 0,
			'Unrenewed' => 0
		);
		$renew_result['Total'] = $curCheckedOut;

		// pattern from marmot sierra :  <b>  RENEWED</b>
		// pattern from Nashville WebPAC : <b> RENEWED successfully</b>
		$numRenewals = preg_match_all("/<b>\s*RENEWED.*?<\/b>/si", $checkedOutPageText, $matches);
		$renew_result['Renewed'] = $numRenewals;
		$renew_result['Unrenewed'] = $renew_result['Total'] - $renew_result['Renewed'];
		if ($renew_result['Unrenewed'] > 0) {
			$renew_result['success'] = false;
			// Now Extract Failure Messages

			// Overall Failure
			if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $checkedOutPageText, $matches)) {
				$msg = ucfirst(strtolower(trim($matches[1])));
				$renew_result['message'][] = "Unable to renew items: $msg.";
			}

			// The Account is busy
			elseif (preg_match('/Your record is in use/si', $checkedOutPageText)) {
				$renew_result['message'][] = 'Unable to renew this item now, your account is in use by the system.  Please try again later.';
				$logger->log('Account is busy error while attempting renewal', PEAR_LOG_WARNING);

			}

			// Let's Look at the Results
			elseif (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/s', $checkedOutPageText, $matches)) {
				$checkedOutTitleTable = $matches[1];
				if (preg_match_all('/<tr class="patFuncEntry">(.*?)<\/tr>/s', $checkedOutTitleTable, $rowMatches, PREG_SET_ORDER)){
					foreach ($rowMatches as $row) {
						$row = $row[1];

							//Extract failure message
							if (preg_match('/<td align="left" class="patFuncStatus">.*?<em><font color="red">(.*?)<\/font><\/em>.*?<\/td>/s', $row, $statusMatches)){
								$msg = ucfirst(strtolower(trim( $statusMatches[1])));

								// Add Title to message
								$title = $this->extract_title_from_row($row);

								$renew_result['message'][] = "<p style=\"font-style:italic\">$title</p><p>Unable to renew: $msg.</p>";
							}

					}
				}else{
					$logger->log("Did not find any rows for the table $checkedOutTitleTable", PEAR_LOG_DEBUG);
				}
			}

		}
		else{
			$renew_result['success'] = true;
			$renew_result['message'][] = "All items were renewed successfully.";
		}

		return $renew_result;
	}

	/**
	 * @param $patron     User
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return array
	 */
	public function renewItem($patron, $itemId, $itemIndex){
		/** var Logger $logger
		 *  var Timer $timer
		 * */
		global $logger, $timer;
		global $analytics;

		//Force loading patron API since that seems to be unlocking the patron record in Millennium for Flatirons
		$this->driver->_getPatronDump($patron->getBarcode(), true);

		$driver = &$this->driver;

		$driver->_curl_login($patron);

		//Pause briefly between logging in and posting the actual renewal
//		usleep(150000);
		// moved to millenium driver _curl_login()

		//Go to the items page
		$scope = $driver->getDefaultScope();
		$curl_url = $driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username ."/items";
		// Loading this page is not necessary in most cases, but if the patron has a Staff ptype we go into staff mode which makes this page load necessary.
		$driver->_curlGetPage($curl_url);

		$renewPostVariables = array(
			'currentsortorder' => 'current_checkout',
			'renewsome' => 'YES',
			'renew' . $itemIndex => $itemId,
		);
		$checkedOutPageText = $driver->_curlPostPage($curl_url, $renewPostVariables);

		//Parse the checked out titles into individual rows
		$message = 'Unable to load renewal information for this entry.';
		$success = false;
		if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $checkedOutPageText, $matches)) {
			//TODO: extract Title for this message
			$success = false;
			$msg = ucfirst(strtolower(trim($matches[1])));
			$message = "Unable to renew this item: $msg.";
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', $msg);
			}
		}
		elseif (preg_match('/Your record is in use/si', $checkedOutPageText, $matches)) {
			$success = false;
			$message = 'Unable to renew this item now, your account is in use by the system.  Please try again later.';
			$logger->log('Account is busy error while attempting renewal', PEAR_LOG_WARNING);
			$timer->logTime('Got System Busy Error while attempting renewal');
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Failed', 'Account in Use');
			}
		}
		elseif (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/s', $checkedOutPageText, $matches)) {
			$checkedOutTitleTable = $matches[1];
			//$logger->log("Found checked out titles table", PEAR_LOG_DEBUG);
			if (preg_match_all('/<tr class="patFuncEntry">(.*?)<\/tr>/s', $checkedOutTitleTable, $rowMatches, PREG_SET_ORDER)){
				//$logger->log("Checked out titles table has " . count($rowMatches) . "rows", PEAR_LOG_DEBUG);
				//$logger->log(print_r($rowMatches, true), PEAR_LOG_DEBUG);
					foreach ($rowMatches as $i => $row) {
					$rowData = $row[1];
					if (preg_match("/{$itemId}/", $rowData)){
						//$logger->log("Found the row for this item", PEAR_LOG_DEBUG);
						//Extract the renewal message
						if (preg_match('/<td align="left" class="patFuncStatus">.*?<em><font color="red">(.*?)<\/font><\/em>.*?<\/td>/s', $rowData, $statusMatches)) {
							$success = false;
							$msg = ucfirst(strtolower(trim($statusMatches[1])));
							$title = $this->extract_title_from_row($rowData);
							$message = "<p style=\"font-style:italic\">$title</p><p>Unable to renew: $msg.</p>";

							// title needed for in renewSelectedItems to distinguish which item failed.
						}elseif (preg_match('/<td.*?class="patFuncStatus".*?>.*?<em><div style="color:red">(.*?)<\/div><\/em>.*?<\/td>/s', $rowData, $statusMatches)){
							$success = false;
							$msg = ucfirst(strtolower(trim($statusMatches[1])));
							$title = $this->extract_title_from_row($rowData);
							$message = "<p style=\"font-style:italic\">$title</p><p>Unable to renew: $msg.</p>";
							// title needed for in renewSelectedItems to distinguish which item failed.
						} elseif (preg_match('/<td.*?class="patFuncStatus".*?>.*?<em>(.*?)<\/em>.*?<\/td>/s', $rowData, $statusMatches)){
							$success = true;
							$message = 'Your item was successfully renewed';
						}
						$logger->log("Renew success = ".($success ? 'true' : 'false').", $message", PEAR_LOG_DEBUG);
						break; // found our item, get out of loop.
					}
				}
			}else{
				$logger->log("Did not find any rows for the table $checkedOutTitleTable", PEAR_LOG_DEBUG);
			}
		}
		else{
			$success = true;
			$message = 'Your item was successfully renewed';
			if ($analytics){
				$analytics->addEvent('ILS Integration', 'Renew Successful');
			}
		}

		$timer->logTime('Finished Renew Item attempt');

		return array(
			'itemId'  => $itemId,
			'success' => $success,
			'message' => $message);
	}
}
