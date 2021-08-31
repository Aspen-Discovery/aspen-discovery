<?php

class MillenniumReadingHistory {
	/**
	 * @var Millennium $driver;
	 */
	private $driver;
	public function __construct($driver){
		$this->driver = $driver;
	}

	/**
	 * @param User $patron
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @return array
	 */
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
		global $timer;
		//Load the information from millennium using CURL
		$pageContents = $this->driver->_fetchPatronInfoPage($patron, 'readinghistory');

		//Check to see if there are multiple pages of reading history
		$hasPagination = preg_match('/<td[^>]*class="browsePager"/', $pageContents);
		if ($hasPagination){
			//Load a list of extra pages to load.  The pagination links display multiple times, so load into an associative array to make them unique
			preg_match_all('/<a href="readinghistory&page=(\\d+)">/', $pageContents, $additionalPageMatches);
			$maxPageNum = 0;
			foreach ($additionalPageMatches[1] as $additionalPageMatch){
				if ($additionalPageMatch > $maxPageNum){
					$maxPageNum = $additionalPageMatch;
				}
			}
		}

		$recordsRead = 0;
		$readingHistoryTitles = $this->parseReadingHistoryPage($pageContents, $patron, $sortOption, $recordsRead);
		$recordsRead += count($readingHistoryTitles);
		if (isset($maxPageNum)){
			for ($pageNum = 2; $pageNum <= $maxPageNum; $pageNum++){
				$pageContents = $this->driver->_fetchPatronInfoPage($patron, 'readinghistory&page=' . $pageNum);
				$additionalTitles = $this->parseReadingHistoryPage($pageContents, $patron, $sortOption, $recordsRead);
				$recordsRead += count($additionalTitles);
				$readingHistoryTitles = array_merge($readingHistoryTitles, $additionalTitles);
			}
		}

		if ($sortOption == "checkedOut" || $sortOption == "returned"){
			krsort($readingHistoryTitles);
		}else{
			ksort($readingHistoryTitles);
		}
		$numTitles = count($readingHistoryTitles);
		//process pagination
		if ($recordsPerPage != -1){
			$startRecord = ($page - 1) * $recordsPerPage;
			$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
		}

		set_time_limit(20 * count($readingHistoryTitles));
		foreach ($readingHistoryTitles as $key => $historyEntry){
			//Get additional information from resources table
			$historyEntry['ratingData']  = null;
			$historyEntry['permanentId'] = null;
			$historyEntry['linkUrl']     = null;
			$historyEntry['coverUrl']    = null;
			$historyEntry['format']      = array();
			if (isset($historyEntry['shortId']) && strlen($historyEntry['shortId']) > 0){
				$historyEntry['recordId'] = "." . $historyEntry['shortId'] . $this->driver->getCheckDigit($historyEntry['shortId']);
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($this->driver->accountProfile->recordSource . ':' . $historyEntry['recordId']);
				if ($recordDriver->isValid()){
					$historyEntry['ratingData']  = $recordDriver->getRatingData();
					$historyEntry['permanentId'] = $recordDriver->getPermanentId();
					$historyEntry['linkUrl']     = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
					$historyEntry['coverUrl']    = $recordDriver->getBookcoverUrl('medium', true);
					$historyEntry['format']      = $recordDriver->getFormats();
				}
				$recordDriver = null;
			}
			$readingHistoryTitles[$key] = $historyEntry;
		}

		//The history is active if there is an opt out link.
		$historyActive = (strpos($pageContents, 'OptOut') > 0);
		$timer->logTime("Loaded Reading history for patron");
		if ($historyActive && !$patron->trackReadingHistory){
			//The user does have reading history even though we hadn't detected it before.
			$patron->trackReadingHistory = true;
			$patron->update();
		}
		if (!$historyActive && $patron->trackReadingHistory){
			//The user does have reading history even though we hadn't detected it before.
			$patron->trackReadingHistory = false;
			$patron->update();
		}

		return array('historyActive'=>$historyActive, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   User    $patron
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($patron, $action, $selectedTitles){
		//Load the reading history page
		$scope = $this->driver->getDefaultScope();
		$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username ."/readinghistory";

		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = $this->driver->_getLoginFormValues($patron);
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$loginResult = curl_exec($curl_connection);

		//When a library uses Encore, the initial login does a redirect and requires additional parameters.
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $loginResult, $loginMatches)) {
			//Get the lt value
			$lt = $loginMatches[1];
			//Login again
			$post_data['lt'] = $lt;
			$post_data['_eventId'] = 'submit';
			$post_items = array();
			foreach ($post_data as $key => $value) {
				$post_items[] = $key . '=' . $value;
			}
			$post_string = implode ('&', $post_items);
			curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
			curl_exec($curl_connection);
		}

		if ($action == 'deleteMarked'){
			//Load patron page readinghistory/rsh with selected titles marked
			if (!isset($selectedTitles) || count($selectedTitles) == 0){
				return;
			}
			$titles = array();
			foreach ($selectedTitles as $titleId){
				$titles[] = $titleId . '=1';
			}
			$title_string = implode ('&', $titles);
			//Issue a get request to delete the item from the reading history.
			//Note: Millennium really does issue a malformed url, and it is required
			//to make the history delete properly.
			$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username ."/readinghistory/rsh&" . $title_string;
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_exec($curl_connection);
		}elseif ($action == 'deleteAll'){
			//load patron page readinghistory/rah
			$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username ."/readinghistory/rah";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_exec($curl_connection);
		}elseif ($action == 'exportList'){
			//Leave this unimplemented for now.
		}elseif ($action == 'optOut'){
			//load patron page readinghistory/OptOut
			$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username ."/readinghistory/OptOut";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_exec($curl_connection);
			$patron->trackReadingHistory = false;
			$patron->update();
		}elseif ($action == 'optIn'){
			//load patron page readinghistory/OptIn
			$curl_url = $this->driver->getVendorOpacUrl() . "/patroninfo~S{$scope}/" . $patron->username ."/readinghistory/OptIn";
			curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
			curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
			curl_exec($curl_connection);
			$patron->trackReadingHistory = true;
			$patron->update();
		}
		curl_close($curl_connection);
		unlink($cookie);
	}

	private function parseReadingHistoryPage($pageContents, $patron, $sortOption, $recordsRead) {
		set_time_limit(60);

		//Get the headers from the table
		preg_match_all('/<th\\s+class="patFuncHeaders">\\s*(.*?)\\s*<\/th>/si', $pageContents, $result, PREG_SET_ORDER);
		$sKeys = array();
		for ($matchi = 0; $matchi < count($result); $matchi++) {
			$sKeys[] = strip_tags($result[$matchi][1]);
		}

		//Get the rows for the table
		preg_match_all('/<tr\\s+class="patFuncEntry">(.*?)<\/tr>/si', $pageContents, $result, PREG_SET_ORDER);
		$sRows = array();
		for ($matchi = 0; $matchi < count($result); $matchi++) {
			$sRows[] = $result[$matchi][1];
		}

		$sCount = 1;
		$readingHistoryTitles = array();
		foreach ($sRows as $sRow) {
			preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $sRow, $result, PREG_SET_ORDER);
			$sCols = array();
			for ($matchi = 0; $matchi < count($result); $matchi++) {
				$sCols[] = $result[$matchi][1];
			}
			$historyEntry = array();
			for ($i=0; $i < sizeof($sCols); $i++) {
				$sCols[$i] = str_replace("&nbsp;"," ",$sCols[$i]);
				$sCols[$i] = preg_replace ("/<br+?>/"," ", $sCols[$i]);
				$sCols[$i] = html_entity_decode(trim($sCols[$i]));
				if (stripos($sKeys[$i],"Mark") > -1) {
					if (preg_match('/id="rsh(\\d+)"/', $sCols[$i], $matches)){
						$itemIndex = $matches[1];
						$historyEntry['itemindex'] = $itemIndex;
					}
					$historyEntry['deletable'] = "BOX";
				}

				if (stripos($sKeys[$i],"Title") > -1) {
					//echo("Title value is <br/>$sCols[$i]<br/>");
					if (preg_match('/.*?<a href=\\"\/record=(.*?)(?:~S\\d{1,2})\\">(.*?)<\/a>.*/', $sCols[$i], $matches)) {
						$shortId = $matches[1];
						$bibId = '.' . $matches[1];
						$historyEntry['id'] = $bibId;
						$historyEntry['shortId'] = $shortId;
					}elseif (preg_match('/.*<a href=".*?\/record\/C__R(.*?)\\?.*?">(.*?)<\/a>.*/si', $sCols[$i], $matches)){
						$shortId = $matches[1];
						$bibId = '.' . $matches[1] . $this->driver->getCheckDigit($shortId);
						$historyEntry['id'] = $bibId;
						$historyEntry['shortId'] = $shortId;
					}
					$title = strip_tags($sCols[$i]);
					$historyEntry['title'] = utf8_encode($title);
				}

				if (stripos($sKeys[$i],"Author") > -1) {
					$historyEntry['author'] = utf8_encode(strip_tags($sCols[$i]));
				}

				if (stripos($sKeys[$i],"Checked Out") > -1) {
					$checkoutTime = DateTime::createFromFormat('m-d-Y', strip_tags($sCols[$i]))->getTimestamp();
					$historyEntry['checkout'] = $checkoutTime;
					$historyEntry['checkin'] = $checkoutTime;
				}
				if (stripos($sKeys[$i],"Details") > -1) {
					$historyEntry['details'] = strip_tags($sCols[$i]);
				}

				if (is_array($patron)){
					$historyEntry['borrower_num'] = $patron['id'];
				}else{
					$historyEntry['borrower_num'] = $patron->id;
				}
			} //Done processing row

			$historyEntry['title_sort'] = preg_replace('/[^a-z\s]/', '', strtolower($historyEntry['title']));

			//$historyEntry['itemindex'] = $itemindex++;
			if ($sortOption == "title"){
				$titleKey = $historyEntry['title_sort'];
			}elseif ($sortOption == "author"){
				$titleKey = $historyEntry['author'] . "_" . $historyEntry['title_sort'];
			}elseif ($sortOption == "checkedOut" || $sortOption == "returned"){
				if ($historyEntry['checkout']){
					$titleKey = $historyEntry['checkout'] . "_" . $historyEntry['title_sort'];
				}else{
					//print_r($historyEntry);
					$titleKey = $historyEntry['title_sort'];
				}
			}elseif ($sortOption == "format"){
				$titleKey = $historyEntry['format'] . "_" . $historyEntry['title_sort'];
			}else{
				$titleKey = $historyEntry['title_sort'];
			}
			$titleKey .= '_' . ($sCount + $recordsRead);
			$readingHistoryTitles[$titleKey] = $historyEntry;

			$sCount++;
		}//processed all rows in the table
		return $readingHistoryTitles;
	}
}