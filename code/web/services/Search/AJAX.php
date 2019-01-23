<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
 *
 */

require_once ROOT_DIR . '/Action.php';

class AJAX extends Action {

	function launch()
	{
		global $analytics;
		$analytics->disableTracking();
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$text_methods = array('GetAutoSuggestList', 'SysListTitles', 'getEmailForm', 'sendEmail', 'getDplaResults');
			//TODO reconfig to use the JSON outputting here.
			$json_methods = array('getMoreSearchResults', 'GetListTitles', 'loadExploreMoreBar');
			// Plain Text Methods //
			if (in_array($method, $text_methods)) {
				header('Content-type: text/plain');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				$this->$method();
			} // JSON Methods //
			elseif (in_array($method, $json_methods)) {
				//			$response = $this->$method();

				//			header ('Content-type: application/json');
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

				try {
					$result = $this->$method();
					require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';
					//					$utf8EncodedValue = ArrayUtils::utf8EncodeArray(array('result'=>$result));
					$utf8EncodedValue = ArrayUtils::utf8EncodeArray($result);
					$output = json_encode($utf8EncodedValue);
					$error = json_last_error();
					if ($error != JSON_ERROR_NONE || $output === FALSE) {
						if (function_exists('json_last_error_msg')) {
							$output = json_encode(array('error' => 'error_encoding_data', 'message' => json_last_error_msg()));
						} else {
							$output = json_encode(array('error' => 'error_encoding_data', 'message' => json_last_error()));
						}
						global $configArray;
						if ($configArray['System']['debug']) {
							print_r($utf8EncodedValue);
						}
					}
				} catch (Exception $e) {
					$output = json_encode(array('error' => 'error_encoding_data', 'message' => $e));
					global $logger;
					$logger->log("Error encoding json data $e", PEAR_LOG_ERR);
				}

				echo $output;
			} else {
				header('Content-type: text/xml');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
				echo "<AJAXResponse>\n";
				$this->$method();
				echo '</AJAXResponse>';
			}
		}else {
			$output = json_encode(array('error'=>'invalid_method'));
			echo $output;
		}
	}

	function IsLoggedIn()
	{
		echo "<result>" .
		(UserAccount::isLoggedIn() ? "True" : "False") . "</result>";
	}

	// Email Search Results
	function sendEmail()
	{
		global $interface;

		$subject = translate('Library Catalog Search Result');
		$url = $_REQUEST['sourceUrl'];
		$to = $_REQUEST['to'];
		$from = $_REQUEST['from'];
		$message = $_REQUEST['message'];
		$interface->assign('from', $from);
		if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)){
			$interface->assign('message', $message);
			$interface->assign('msgUrl', $url);
			$body = $interface->fetch('Emails/share-link.tpl');

			require_once ROOT_DIR . '/sys/Mailer.php';
			$mail = new VuFindMailer();
			$emailResult = $mail->send($to, $from, $subject, $body);

			if ($emailResult === true){
				$result = array(
						'result' => true,
						'message' => 'Your e-mail was sent successfully.'
				);
			}elseif (PEAR_Singleton::isError($emailResult)){
				$result = array(
						'result' => false,
						'message' => "Your e-mail message could not be sent: {$emailResult->message}."
				);
			}else{
				$result = array(
						'result' => false,
						'message' => 'Your e-mail message could not be sent due to an unknown error.'
				);
			}
		}else{
			$result = array(
					'result' => false,
					'message' => 'Sorry, we can&apos;t send e-mails with html or other data in it.'
			);
		}

		echo json_encode($result);
	}

	function GetAutoSuggestList(){
		require_once ROOT_DIR . '/services/Search/lib/SearchSuggestions.php';
		global $timer;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$searchTerm = isset($_REQUEST['searchTerm']) ? $_REQUEST['searchTerm'] : $_REQUEST['q'];
		$searchType = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
		$cacheKey = 'auto_suggest_list_' . urlencode($searchType) . '_' . urlencode($searchTerm);
		$searchSuggestions = $memCache->get($cacheKey);
		if ($searchSuggestions == false || isset($_REQUEST['reload'])){
			$suggestions = new SearchSuggestions();
			$commonSearches = $suggestions->getAllSuggestions($searchTerm, $searchType);
			$commonSearchTerms = array();
			foreach ($commonSearches as $searchTerm){
				if (is_array($searchTerm)){
					$commonSearchTerms[] = $searchTerm['phrase'];
				}else{
					$commonSearchTerms[] = $searchTerm;
				}
			}
			$searchSuggestions = json_encode($commonSearchTerms);
			$memCache->set($cacheKey, $searchSuggestions, 0, $configArray['Caching']['search_suggestions'] );
			$timer->logTime("Loaded search suggestions $cacheKey");
		}
		echo $searchSuggestions;
	}

	function getProspectorResults(){
		$prospectorSavedSearchId = $_GET['prospectorSavedSearchId'];

		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		global $configArray;
		global $interface;
		global $library;
		global $timer;

		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$db = new $class($url);
		$searchObject = $searchObject->restoreSavedSearch($prospectorSavedSearchId, false);

		//Load results from Prospector
		$prospector = new Prospector();

		// Only show prospector results within search results if enabled
		if ($library && $library->enablePospectorIntegration && $library->showProspectorResultsAtEndOfSearch){
			$prospectorResults = $prospector->getTopSearchResults($searchObject->getSearchTerms(), 5);
			$interface->assign('prospectorResults', $prospectorResults['records']);
		}

		$prospectorLink = $prospector->getSearchLink($searchObject->getSearchTerms());
		$interface->assign('prospectorLink', $prospectorLink);
		$timer->logTime('load Prospector titles');
		echo $interface->fetch('Search/ajax-prospector.tpl');
	}

	/**
	 * For historical purposes.	Make sure the old API wll still work.
	 */
	function SysListTitles(){
		if (!isset($_GET['id'])){
			$_GET['id'] = $_GET['name'];
		}
		return $this->GetListTitles();
	}

	/**
	 * @return string JSON encoded data representing the list information
	 */
	function GetListTitles(){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;

		$listName = strip_tags(isset($_GET['scrollerName']) ? $_GET['scrollerName'] : 'List' . $_GET['id']);

		//Determine the caching parameters
		require_once(ROOT_DIR . '/services/API/ListAPI.php');
		$listAPI = new ListAPI();
		$cacheInfo = $listAPI->getCacheInfoForList();

		$cacheName = $cacheInfo['cacheName'];
		if (isset($_REQUEST['coverSize']) && $_REQUEST['coverSize'] == 'medium'){
			$cacheName .= '_medium';
		}

		$listData = $memCache->get($cacheName);
		if (!$listData || isset($_REQUEST['reload']) || (isset($listData['titles']) && count($listData['titles']) == 0)){
			global $interface;
			$interface->assign('listName', $listName);

			$showRatings = isset($_REQUEST['showRatings']) && $_REQUEST['showRatings'];
			$interface->assign('showRatings', $showRatings); // overwrite values that come from library settings

			$numTitlesToShow = isset($_REQUEST['numTitlesToShow']) ? $_REQUEST['numTitlesToShow'] : 25;

			$titles = $listAPI->getListTitles(null, $numTitlesToShow);
			$timer->logTime("getListTitles");
			if ($titles['success'] == true){
				$titles = $titles['titles'];
				if (is_array($titles)){
					foreach ($titles as $key => $rawData){
						$interface->assign('key', $key);
						// 20131206 James Staub: bookTitle is in the list API and it removes the final frontslash, but I didn't get $rawData['bookTitle'] to load

						$titleShort = preg_replace(array('/\:.*?$/', '/\s*\/$\s*/'),'', $rawData['title']);
//						$titleShort = preg_replace('/\:.*?$/','', $rawData['title']);
//						$titleShort = preg_replace('/\s*\/$\s*/','', $titleShort);

						$imageUrl = $rawData['small_image'];
						if (isset($_REQUEST['coverSize']) && $_REQUEST['coverSize'] == 'medium'){
							$imageUrl = $rawData['image'];
						}

						$interface->assign('title',       $titleShort);
						$interface->assign('author',      $rawData['author']);
						$interface->assign('description', isset($rawData['description']) ? $rawData['description'] : null);
						$interface->assign('length',      isset($rawData['length']) ? $rawData['length'] : null);
						$interface->assign('publisher',   isset($rawData['publisher']) ? $rawData['publisher'] : null);
						$interface->assign('shortId',     $rawData['shortId']);
						$interface->assign('id',          $rawData['id']);
						$interface->assign('titleURL',    $rawData['titleURL']);
						$interface->assign('imageUrl',    $imageUrl);

						if ($showRatings){
							$interface->assign('ratingData', $rawData['ratingData']);
							$interface->assign('showNotInterested', false);
						}

						$rawData['formattedTitle']         = $interface->fetch('ListWidget/formattedTitle.tpl');
						$rawData['formattedTextOnlyTitle'] = $interface->fetch('ListWidget/formattedTextOnlyTitle.tpl');
						// TODO: Modify these for Archive Objects

						$titles[$key] = $rawData;
					}
				}
				$currentIndex = count($titles) > 5 ? floor(count($titles) / 2) : 0;

				$listData = array('titles' => $titles, 'currentIndex' => $currentIndex);

				$memCache->set($cacheInfo['cacheName'], $listData, 0, $cacheInfo['cacheLength']);
			}else{
				$listData = array('titles' => array(), 'currentIndex' => 0);
				if ($titles['message']) $listData['error'] = $titles['message']; // send error message to widget javascript
			}
		}
		return $listData;
	}

	function getEmailForm(){
		global $interface;
		$results = array(
			'title' => 'E-Mail Search',
			'modalBody' => $interface->fetch('Search/email.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='$(\"#emailSearchForm\").submit();'>Send E-Mail</span>"
		);
		echo json_encode($results);
	}

	function getDplaResults(){
		require_once ROOT_DIR . '/sys/SearchObject/DPLA.php';
		$dpla = new DPLA();
		$searchTerm = $_REQUEST['searchTerm'];
		$results = $dpla->getDPLAResults($searchTerm);
		$formattedResults = $dpla->formatResults($results['records']);

		$returnVal = array(
			'rawResults' => $results['records'],
			'formattedResults' => $formattedResults,
		);

		//Format the results
		echo(json_encode($returnVal));
	}

	function getMoreSearchResults($displayMode = 'covers'){
		// Called Only for Covers mode //
		$success = true; // set to false on error

//		$currentPage = isset($_REQUEST['pageToLoad']) ? $_REQUEST['pageToLoad'] : 1;
//		$query = ltrim($_REQUEST['query'], '?');
//		parse_str($query, $_REQUEST);
//		$_REQUEST['page'] = $currentPage;
		// quick & dirty way to get search parameters

		// More involved method for grabbing variables
		//		parse_str($query, $searchParams);
//		$test = array_merge($_REQUEST, $searchParams, array('page' => $currentPage));
//		$_REQUEST = $test;

		if (isset($_REQUEST['view'])) $_REQUEST['view'] = $displayMode; // overwrite any display setting for now

		/** @var string $searchSource */
//		$searchSource = isset($searchParams['searchSource']) ? $searchParams['searchSource'] : 'local';
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		// Initialise from the current search globals
		/** @var SearchObject_Solr $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);

//		if ($displayMode == 'covers') {
			$searchObject->setLimit(24); // a set of 24 covers looks better in display
//		}

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result->getMessage());
			$success = false;
		}
		$searchObject->close();

		// Process for Display //
		$recordSet = $searchObject->getResultRecordHTML($displayMode);
//		if ($displayMode == 'covers'){
			$displayTemplate = 'Search/covers-list.tpl'; // structure for bookcover tiles

		// Rating Settings
		global $library, $location;
		$browseCategoryRatingsMode = null;
		if ($location) $browseCategoryRatingsMode = $location->browseCategoryRatingsMode; // Try Location Setting
		if (!$browseCategoryRatingsMode) $browseCategoryRatingsMode = $library->browseCategoryRatingsMode;  // Try Library Setting

		// when the Ajax rating is turned on, they have to be initialized with each load of the category.
		if ($browseCategoryRatingsMode == 'stars') $recordSet[] = '<script type="text/javascript">VuFind.Ratings.initializeRaters()</script>';

//		}
//		else { // default
//			$displayTemplate = 'Search/list-list.tpl'; // structure for regular results
//		}
		global $interface;
		$interface->assign('recordSet', $recordSet);
		$records = $interface->fetch($displayTemplate);
		$result = array(
			'success' => $success,
			'records' => $records,
		);
		// let front end know if we have reached the end of the result set
		if ($searchObject->getPage() * $searchObject->getLimit() >= $searchObject->getResultTotal()) $result['lastPage'] = true;
		return $result;
	}

	function loadExploreMoreBar(){
		global $interface;

		$section = $_REQUEST['section'];
		$searchTerm = $_REQUEST['searchTerm'];
		if (is_array($searchTerm)){
			$searchTerm = reset($searchTerm);
		}
		$searchTerm = urldecode(html_entity_decode($searchTerm));

		//Load explore more data
		require_once ROOT_DIR . '/sys/ExploreMore.php';
		$exploreMore = new ExploreMore();
		$exploreMoreOptions = $exploreMore->loadExploreMoreBar($section, $searchTerm);
		if (count($exploreMoreOptions) == 0){
			$result = array(
					'success' => false,
			);
		}else{
			$result = array(
					'success' => true,
					'exploreMoreBar' => $interface->fetch("Search/explore-more-bar.tpl")
			);
		}

		return $result;
	}

}

function ar2xml($ar)
{
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;
	foreach ($ar as $facet => $value) {
		$element = $doc->createElement($facet);
		foreach ($value as $term => $cnt) {
			$child = $doc->createElement('term', $term);
			$child->setAttribute('count', $cnt);
			$element->appendChild($child);
		}
		$doc->appendChild($element);
	}

	return strstr($doc->saveXML(), "\n");
}
