<?php

require_once ROOT_DIR . '/Action.php';

class AJAX extends Action {

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$text_methods = array('SysListTitles', 'getEmailForm', 'sendEmail', 'getDplaResults');
			//TODO re-config to use the JSON outputting here.
			$json_methods = array('getAutoSuggestList', 'getMoreSearchResults', 'getListTitles', 'loadExploreMoreBar');
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
				header('Content-type: application/json; charset=utf-8');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

				try {
					$result = $this->$method();
					if (true){
						$output = json_encode($result);
					}else{
						require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';
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
					}


				} catch (Exception $e) {
					$output = json_encode(array('error' => 'error_encoding_data', 'message' => $e));
					global $logger;
					$logger->log("Error encoding json data $e", Logger::LOG_ERROR);
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

			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mail = new Mailer();
			$emailResult = $mail->send($to, $subject, $body, $from);

			if ($emailResult === true){
				$result = array(
						'result' => true,
						'message' => 'Your email was sent successfully.'
				);
			}elseif (($emailResult instanceof AspenError)){
				$result = array(
						'result' => false,
						'message' => "Your email message could not be sent: {$emailResult->getMessage()}."
				);
			}else{
				$result = array(
						'result' => false,
						'message' => 'Your email message could not be sent due to an unknown error.'
				);
			}
		}else{
			$result = array(
					'result' => false,
					'message' => 'Sorry, we can&apos;t send emails with html or other data in it.'
			);
		}

		echo json_encode($result);
	}

	function getAutoSuggestList(){
		require_once ROOT_DIR . '/services/Search/lib/SearchSuggestions.php';
		global $timer;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$searchTerm = isset($_REQUEST['searchTerm']) ? $_REQUEST['searchTerm'] : $_REQUEST['q'];
        $searchIndex = isset($_REQUEST['searchIndex']) ? $_REQUEST['searchIndex'] : '';
        $searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : '';
		$cacheKey = 'auto_suggest_list_' . urlencode($searchSource) . '_' . urlencode($searchIndex
            ) . '_' . urlencode($searchTerm);
		$searchSuggestions = $memCache->get($cacheKey);
		if ($searchSuggestions == false || isset($_REQUEST['reload'])){
			$suggestions = new SearchSuggestions();
			$commonSearches = $suggestions->getAllSuggestions($searchTerm, $searchIndex, $searchSource);
			$commonSearchTerms = array();
			foreach ($commonSearches as $searchTerm){
				if (is_array($searchTerm)){
				    $plainText = preg_replace('~</?b>~i', '', $searchTerm['phrase']);
					$commonSearchTerms[] =[
					    'label' => $searchTerm['phrase'],
                        'value' => $plainText
                    ];
				}else{
					$commonSearchTerms[] = $searchTerm;
				}
			}
			$searchSuggestions = $commonSearchTerms;
			$memCache->set($cacheKey, $searchSuggestions, 0, $configArray['Caching']['search_suggestions'] );
			$timer->logTime("Loaded search suggestions $cacheKey");
		}
		return $searchSuggestions;
	}

	function getProspectorResults(){
		$prospectorSavedSearchId = $_GET['prospectorSavedSearchId'];

		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		global $interface;
		global $library;
		global $timer;

		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		$searchObject = $searchObject->restoreSavedSearch($prospectorSavedSearchId, false);

		//Load results from Prospector
		$prospector = new Prospector();

		// Only show prospector results within search results if enabled
		if ($library && $library->enableProspectorIntegration && $library->showProspectorResultsAtEndOfSearch){
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
		return $this->getListTitles();
	}

	/**
	 * @return array data representing the list information
	 */
	function getListTitles(){
		global $timer;

		$listName = strip_tags(isset($_GET['scrollerName']) ? $_GET['scrollerName'] : 'List' . $_GET['id']);

		//Determine the caching parameters
		require_once(ROOT_DIR . '/services/API/ListAPI.php');
		$listAPI = new ListAPI();

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

		}else{
			$listData = array('titles' => array(), 'currentIndex' => 0);
			if ($titles['message']) $listData['error'] = $titles['message']; // send error message to widget javascript
		}

		return $listData;
	}

	function getEmailForm(){
		global $interface;
		$results = array(
			'title' => 'Email Search',
			'modalBody' => $interface->fetch('Search/email.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='$(\"#emailSearchForm\").submit();'>Send Email</span>"
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

		if (isset($_REQUEST['view'])) $_REQUEST['view'] = $displayMode; // overwrite any display setting for now

		/** @var string $searchSource */
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		// Initialise from the current search globals
		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);

		$searchObject->setLimit(24); // a set of 24 covers looks better in display

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result->getMessage());
			$success = false;
		}
		$searchObject->close();

		// Process for Display //
		$recordSet = $searchObject->getResultRecordHTML();
		$displayTemplate = 'Search/covers-list.tpl'; // structure for bookcover tiles

		// Rating Settings
		global $library, $location;
		$browseCategoryRatingsMode = null;
		if ($location) $browseCategoryRatingsMode = $location->browseCategoryRatingsMode; // Try Location Setting
		if (!$browseCategoryRatingsMode) $browseCategoryRatingsMode = $library->browseCategoryRatingsMode;  // Try Library Setting

		// when the Ajax rating is turned on, they have to be initialized with each load of the category.
		if ($browseCategoryRatingsMode == 'stars') $recordSet[] = '<script type="text/javascript">AspenDiscovery.Ratings.initializeRaters()</script>';

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
