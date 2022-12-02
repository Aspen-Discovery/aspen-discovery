<?php


class MillenniumCheckouts {
	/** @var  Millennium $driver */
	private $driver;

	public function __construct($driver) {
		$this->driver = $driver;
	}

	private function extract_title_from_row($row) {
		//Standard Millennium WebPAC
		if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $row, $matches)) {
			return trim(strip_tags($matches[2]));
		} elseif (preg_match('/<a href=".*?\/record\/C__R(.*?)\?.*?"patFuncTitleMain">(.*?)<\/span>/si', $row, $matches)) {
			//Encore
			// This Regex developed using output from Nashville webpac.  plb 3-31-2016
			//TODO: may need to be modified to match both patFuncTitle & patFuncTitleMain, using '/<a href=".*?\/record\/C__R(.*?)\?.*?"patFuncTitle.*?">(.*?)<\/span>/si'. plb 3-31-2016
			return trim(strip_tags($matches[2]));
		} elseif (preg_match('/<td.*?"patFuncTitle">(.*?)<\/td>/si', $row, $matches)) {
			// Prospector Holds in Sierra WebPAC
			// This Regex developed using output from Marmot Sierra webpac.  plb 3-31-2016
			// Note: this regex doesn't extract a record id like the two above do.
			return trim(strip_tags($matches[1]));
		} else {
			// Fallback option
			return trim(strip_tags($row));
		}
	}

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 * @param IndexingProfile $indexingProfile
	 * @return Checkout[]        Array of the patron's transactions on success,
	 * AspenError otherwise.
	 * @access public
	 */
	public function getCheckouts(User $user, IndexingProfile $indexingProfile): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = [];
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

			$sRows = preg_split("/<tr([^>]*)>/", $s);
			$sCount = 0;
			$sKeys = array_pad([], 10, "");

			//Get patron's location to determine if renewals are allowed.
			global $locationSingleton;
			/** @var Location $patronLocation */
			$patronLocation = $locationSingleton->getUserHomeLocation();
			if (isset($patronLocation)) {
				$patronPType = $user->patronType;
				$patronCanRenew = false;
				if ($patronLocation->ptypesToAllowRenewals == '*') {
					$patronCanRenew = true;
				} elseif (preg_match("/^({$patronLocation->ptypesToAllowRenewals})$/", $patronPType)) {
					$patronCanRenew = true;
				}
			} else {
				$patronCanRenew = true;
			}
			$timer->logTime("Determined if patron can renew");

			foreach ($sRows as $srow) {
				$scols = preg_split("/<t(h|d)([^>]*)>/", $srow);
				$curTitle = new Checkout();
				$curTitle->type = 'ils';
				$curTitle->source = $indexingProfile->name;
				$curTitle->userId = $user->id;
				for ($i = 0; $i < sizeof($scols); $i++) {
					$scols[$i] = str_replace("&nbsp;", " ", $scols[$i]);
					$scols[$i] = preg_replace("/<br+?>/", " ", $scols[$i]);
					$scols[$i] = html_entity_decode(trim(substr($scols[$i], 0, stripos($scols[$i], "</t"))));
					//print_r($scols[$i]);
					if ($sCount == 1) {
						$sKeys[$i] = $scols[$i];
					} elseif ($sCount > 1) {

						if (stripos($sKeys[$i], "TITLE") > -1) {
							if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $scols[$i], $matches)) {
								//Standard Millennium WebPAC
								$shortId = $matches[1];
								$bibId = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
								$title = strip_tags($matches[2]);
							} elseif (preg_match('/<a href=".*?\/record\/C__R(.*?)\?.*?"patFuncTitleMain">(.*?)<\/span>/si', $scols[$i], $matches)) {
								//Encore
								$shortId = $matches[1];
								$bibId = '.' . $matches[1]; //Technically, this isn't correct since the check digit is missing
								$title = strip_tags($matches[2]);
							} else {
								$title = strip_tags($scols[$i]);
								$shortId = '';
								$bibId = '';
							}
							$curTitle->shortId = $shortId;
							$curTitle->sourceId = $bibId;
							$curTitle->recordId = $bibId;
							$curTitle->title = utf8_encode($title);
							if (preg_match('/.*<span class="patFuncVol">(.*?)<\/span>.*/si', $scols[$i], $matches)) {
								$curTitle->volume = $matches[1];
							}
						}

						if (stripos($sKeys[$i], "STATUS") > -1) {
							// $sret[$scount-2]['dueDate'] = strip_tags($scols[$i]);
							$due = trim(str_replace("DUE", "", strip_tags($scols[$i])));
							$renewCount = 0;
							if (preg_match('/FINE\(\s*up to now\) (\$\d+\.\d+)/i', $due, $matches)) {
								$curTitle->fine = trim($matches[1]);
							}
							if (preg_match('/(.*)Renewed (\d+) time(?:s)?/i', $due, $matches)) {
								$due = trim($matches[1]);
								$renewCount = $matches[2];
							} elseif (preg_match('/(.*)\+\d+ HOLD.*/i', $due, $matches)) {
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
								$curTitle->dueDate = $dueTime;
							}
							$curTitle->renewCount = $renewCount;

						}

						if (stripos($sKeys[$i], "BARCODE") > -1) {
							$curTitle->barcode = strip_tags($scols[$i]);
						}


						if (stripos($sKeys[$i], "RENEW") > -1) {
							$matches = [];
							if (preg_match('/<input\s*type="checkbox"\s*name="renew(\d+)"\s*id="renew(\d+)"\s*value="(.*?)"\s*\/>/', $scols[$i], $matches)) {
								$curTitle->canRenew = $patronCanRenew;
								$curTitle->itemIndex = $matches[1];
								$curTitle->itemId = $matches[3];
								$curTitle->renewIndicator = $curTitle->itemId . '|' . $curTitle->itemIndex;
								$curTitle->renewalId = $curTitle->itemId . '|' . $curTitle->itemIndex;
							} else {
								$curTitle->canRenew = false;
							}
						}

						if (stripos($sKeys[$i], "CALL NUMBER") > -1) {
							$curTitle->callNumber = strip_tags($scols[$i]);
						}
					}

				}
				if ($sCount > 1) {
					//Get additional information from the MARC Record
					if (isset($curTitle->shortId) && strlen($curTitle->shortId) > 0) {
						$checkDigit = $this->driver->getCheckDigit($curTitle->shortId);
						$curTitle->recordId = '.' . $curTitle->shortId . $checkDigit;
						$curTitle->sourceId = '.' . $curTitle->shortId . $checkDigit;
						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$recordDriver = new MarcRecordDriver($this->driver->accountProfile->recordSource . ":" . $curTitle->recordId);
						if ($recordDriver->isValid()) {
							$curTitle->updateFromRecordDriver($recordDriver);
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

	public function renewAll(User $patron) {
		global $logger;
		$driver = &$this->driver;

		//Setup the call to Millennium
		$barcode = $driver->_getBarcode($patron);
		$patronDump = $driver->_getPatronDump($barcode);
		$curCheckedOut = $patronDump['CUR_CHKOUT'];

		//Login to the patron's account
		$driver->_curl_login($patron);

		//Go to the items page
		$scope = $driver->getDefaultScope();
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patronDump['RECORD_#'] . "/items";
		$checkedOutPageText = $driver->curlWrapper->curlGetPage($curl_url); // TODO Initial page load needed?

		//Post renewal information
		$renewAllPostVariables = [
			'currentsortorder' => 'current_checkout',
			'renewall' => 'YES',
		];

		$checkedOutPageText = $driver->curlWrapper->curlPostPage($curl_url, $renewAllPostVariables);
		//$logger->log("Result of Renew All\r\n" . $checkedOutPageText, Logger::LOG_NOTICE);

		//Clear the existing patron info and get new information.
		$renew_result = [
			'success' => false,
			'message' => [],
			'Renewed' => 0,
			'NotRenewed' => 0,
		];
		$renew_result['Total'] = $curCheckedOut;

		// pattern from marmot sierra :  <b>  RENEWED</b>
		// pattern from Nashville WebPAC : <b> RENEWED successfully</b>
		$numRenewals = preg_match_all("/<b>\s*RENEWED.*?<\/b>/si", $checkedOutPageText, $matches);
		$renew_result['Renewed'] = $numRenewals;
		$renew_result['NotRenewed'] = $renew_result['Total'] - $renew_result['Renewed'];
		if ($renew_result['NotRenewed'] > 0) {
			$renew_result['success'] = false;
			// Now Extract Failure Messages

			// Overall Failure
			if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $checkedOutPageText, $matches)) {
				$msg = ucfirst(strtolower(trim($matches[1])));
				$renew_result['message'][] = "Unable to renew items: $msg.";
			} // The Account is busy
			elseif (preg_match('/Your record is in use/si', $checkedOutPageText)) {
				$renew_result['message'][] = 'Unable to renew this item now, your account is in use by the system.  Please try again later.';
				$logger->log('Account is busy error while attempting renewal', Logger::LOG_WARNING);

			} // Let's Look at the Results
			elseif (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/s', $checkedOutPageText, $matches)) {
				$checkedOutTitleTable = $matches[1];
				if (preg_match_all('/<tr class="patFuncEntry">(.*?)<\/tr>/s', $checkedOutTitleTable, $rowMatches, PREG_SET_ORDER)) {
					foreach ($rowMatches as $row) {
						$row = $row[1];

						//Extract failure message
						if (preg_match('/<td align="left" class="patFuncStatus">.*?<em><font color="red">(.*?)<\/font><\/em>.*?<\/td>/s', $row, $statusMatches)) {
							$msg = ucfirst(strtolower(trim($statusMatches[1])));

							// Add Title to message
							$title = $this->extract_title_from_row($row);

							$renew_result['message'][] = "<p style=\"font-style:italic\">$title</p><p>Unable to renew: $msg.</p>";
						}

					}
				} else {
					$logger->log("Did not find any rows for the table $checkedOutTitleTable", Logger::LOG_DEBUG);
				}
			}

		} else {
			$renew_result['success'] = true;
			$renew_result['message'][] = "All items were renewed successfully.";
		}

		$patron->clearCachedAccountSummaryForSource($this->driver->getIndexingProfile()->name);
		$patron->forceReloadOfCheckouts();

		return $renew_result;
	}

	/**
	 * @param $patron     User
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return array
	 */
	public function renewCheckout($patron, $itemId, $itemIndex) {
		/** var Logger $logger
		 *  var Timer $timer
		 * */ global $logger, $timer;

		//Force loading patron API since that seems to be unlocking the patron record in Millennium for Flatirons
		$barcode = $patron->getBarcode();
		$this->driver->_getPatronDump($barcode, true);

		$driver = &$this->driver;

		$driver->_curl_login($patron);

		//Go to the items page
		$scope = $driver->getDefaultScope();
		$curl_url = $driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username . "/items";
		// Loading this page is not necessary in most cases, but if the patron has a Staff ptype we go into staff mode which makes this page load necessary.
		$driver->curlWrapper->curlGetPage($curl_url);

		$renewPostVariables = [
			'currentsortorder' => 'current_checkout',
			'renewsome' => 'YES',
			'renew' . $itemIndex => $itemId,
		];
		$checkedOutPageText = $driver->curlWrapper->curlPostPage($curl_url, $renewPostVariables);

		//Parse the checked out titles into individual rows
		$message = 'Unable to load renewal information for this entry.';
		$success = false;
		if (preg_match('/<h2>\\s*You cannot renew items because:\\s*<\/h2><ul><li>(.*?)<\/ul>/si', $checkedOutPageText, $matches)) {
			//TODO: extract Title for this message
			$success = false;
			$msg = ucfirst(strtolower(trim($matches[1])));
			$message = "Unable to renew this item: $msg.";
		} elseif (preg_match('/Your record is in use/si', $checkedOutPageText, $matches)) {
			$success = false;
			$message = 'Unable to renew this item now, your account is in use by the system.  Please try again later.';
			$logger->log('Account is busy error while attempting renewal', Logger::LOG_WARNING);
			$timer->logTime('Got System Busy Error while attempting renewal');
		} elseif (preg_match('/<table border="0" class="patFunc">(.*?)<\/table>/s', $checkedOutPageText, $matches)) {
			$checkedOutTitleTable = $matches[1];
			//$logger->log("Found checked out titles table", Logger::LOG_DEBUG);
			if (preg_match_all('/<tr class="patFuncEntry">(.*?)<\/tr>/s', $checkedOutTitleTable, $rowMatches, PREG_SET_ORDER)) {
				//$logger->log("Checked out titles table has " . count($rowMatches) . "rows", Logger::LOG_DEBUG);
				//$logger->log(print_r($rowMatches, true), Logger::LOG_DEBUG);
				foreach ($rowMatches as $i => $row) {
					$rowData = $row[1];
					if (preg_match("/{$itemId}/", $rowData)) {
						//$logger->log("Found the row for this item", Logger::LOG_DEBUG);
						//Extract the renewal message
						if (preg_match('/<td align="left" class="patFuncStatus">.*?<em><font color="red">(.*?)<\/font><\/em>.*?<\/td>/s', $rowData, $statusMatches)) {
							$success = false;
							$msg = ucfirst(strtolower(trim($statusMatches[1])));
							$title = $this->extract_title_from_row($rowData);
							$message = "<p style=\"font-style:italic\">$title</p><p>Unable to renew: $msg.</p>";

							// title needed for in renewSelectedItems to distinguish which item failed.
						} elseif (preg_match('/<td.*?class="patFuncStatus".*?>.*?<em><div style="color:red">(.*?)<\/div><\/em>.*?<\/td>/s', $rowData, $statusMatches)) {
							$success = false;
							$msg = ucfirst(strtolower(trim($statusMatches[1])));
							$title = $this->extract_title_from_row($rowData);
							if (strcasecmp($title, 'INTERLIBRARY LOAN MATERIAL') === 0) {
								$message = "<p style=\"font-style:italic\">$title</p><p>" . translate([
										'text' => 'Unable to renew interlibrary loan materials',
										'isPublicFacing' => true,
									]) . "</p>";
							} else {
								$message = "<p style=\"font-style:italic\">$title</p><p>Unable to renew: $msg.</p>";
							}
							// title needed for in renewSelectedItems to distinguish which item failed.
						} elseif (preg_match('/<td.*?class="patFuncStatus".*?>.*?<em>(.*?)<\/em>.*?<\/td>/s', $rowData, $statusMatches)) {
							$success = true;
							$message = 'Your item was successfully renewed';
						}
						$logger->log("Renew success = " . ($success ? 'true' : 'false') . ", $message", Logger::LOG_DEBUG);
						break; // found our item, get out of loop.
					}
				}
			} else {
				$logger->log("Did not find any rows for the table $checkedOutTitleTable", Logger::LOG_DEBUG);
			}
		} else {
			$success = true;
			$message = 'Your item was successfully renewed';
		}

		$timer->logTime('Finished Renew Item attempt');

		if ($success) {
			$patron->clearCachedAccountSummaryForSource($this->driver->getIndexingProfile()->name);
			$patron->forceReloadOfCheckouts();
		}

		return [
			'itemId' => $itemId,
			'success' => $success,
			'message' => $message,
		];
	}
}
