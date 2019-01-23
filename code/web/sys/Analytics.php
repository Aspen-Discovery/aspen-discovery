<?php
require_once ROOT_DIR . '/sys/analytics/Analytics_Session.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_Event.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_Search.php';
require_once ROOT_DIR . '/sys/analytics/Analytics_PageView.php';
require_once ROOT_DIR . '/sys/BotChecker.php';

class Analytics
{
	/** @var Analytics_Session */
	private $session;
	/** @var Analytics_PageView */
	private $pageView;
	/** @var Analytics_Event[] */
	private $events = array();
	/** @var Analytics_Search */
	private $search;
	private $finished = false;
	private $trackingDisabled = false;

	function __construct($ipAddress, $startTime){
		global $configArray;
		if (!isset($configArray)){
			die("You must load configuration before creating a tracker");
		}

		global $interface;
		if (!isset($interface)){
			die("You must setup the interface before creating a tracker");
		}

		//Make sure that we don't track visits from bots
		if (BotChecker::isRequestFromBot() == true){
			//$logger->log("Disabling logging because the request is from a bot", PEAR_LOG_DEBUG);
			$this->trackingDisabled = true;
			$this->finished = true;
			return;
		}

		//Check to see if analytics is enabled
		if (isset($configArray['System']['enableAnalytics']) && $configArray['System']['enableAnalytics'] == false){
			$this->trackingDisabled = true;
			return;
		}
		//Check to see if we are in maintenance mode
		if (isset($configArray['System']['available']) && $configArray['System']['available'] == false){
			$this->trackingDisabled = true;
			return;
		}

		$session = new Analytics_Session();

		//disable error handler since the tables may not be installed yet.
		disableErrorHandler();
		$sessionId = session_id();
		$session->session_id = $sessionId;
		if ($session->find(true)){
			$this->session = $session;
			if ($this->session->ip != $ipAddress){
				$this->session->ip = $ipAddress;
				$this->doGeoIP();
			}
		}else{
			$this->session = $session;
			$this->session->sessionStartTime = $startTime;
			$this->session->lastRequestTime = $startTime;
			$this->session->ip = $ipAddress;

			$this->doGeoIP();

			$this->session->insert();
		}

		$this->pageView = new Analytics_PageView();
		$this->pageView->sessionId = $this->session->id;
		$this->pageView->pageStartTime = $startTime;
		$this->pageView->fullUrl = $_SERVER['REQUEST_URI'];
		enableErrorHandler();
	}

	function disableTracking(){
		$this->trackingDisabled = true;
	}

	function enableTracking(){
		global $configArray;
		//Before enabling analytics, make sure that we can use it.
		if (isset($configArray['System']['enableAnalytics']) && $configArray['System']['enableAnalytics'] == true) {
			$this->trackingDisabled = false;
		}
	}

	function isTrackingDisabled(){
		return $this->trackingDisabled;
	}

	function setModule($module){
		$this->pageView->module = $module;
	}

	function setAction($action){
		$this->pageView->action = $action;
	}

	function setObjectId($objectId){
		$this->pageView->objectId = $objectId;
	}

	function setMethod($method){
		$this->pageView->method = $method;
	}

	function setLanguage($language){
		$this->pageView->language = $language;
	}

	function setTheme($theme){
		$this->session->setTheme($theme);
	}

	function setMobile($mobile){
		$this->session->mobile = $mobile;
	}

	function setDevice($device){
		$this->session->setDevice($device);
	}

	function setPhysicalLocation($physicalLocation){
		$this->session->setPhysicalLocation($physicalLocation);
	}

	function setPatronType($patronType){
		$this->session->setPatronType($patronType);
	}

	function setHomeLocationId($homeLocationId){
		$this->session->homeLocationId = $homeLocationId;
	}

	function doGeoIP(){
		global $configArray;
		//Load GeoIP data
		require_once ROOT_DIR . '/sys/MaxMindGeoIP/geoip.inc';
		require_once ROOT_DIR . '/sys/MaxMindGeoIP/geoipcity.inc';
		$geoIP = geoip_open($configArray['Site']['local'] . '/../../sites/default/GeoIPCity.dat', GEOIP_MEMORY_CACHE);
		$geoRecord = GeoIP_record_by_addr($geoIP, $this->session->ip);
		if ($geoRecord){
			$this->session->setCountry($geoRecord->country_code);
			$this->session->setState($geoRecord->region);
			$this->session->setCity($geoRecord->city);
			$this->session->latitude = $geoRecord->latitude;
			$this->session->longitude = $geoRecord->longitude;
		}
		geoip_close($geoIP);
	}

	function addEvent($category, $action, $data1 = '', $data2 = '', $data3 = ''){
		if ($this->trackingDisabled) return;
		$event = new Analytics_Event();
		$event->sessionId = $this->session->id;
		$event->category = $category;
		$event->action = $action;
		$event->data = substr($data1, 0, 256);
		$event->data2 = substr($data2, 0, 256);
		$event->data3 = substr($data3, 0, 256);
		$event->eventTime = time();
		$this->events[] = $event;
	}

	function addSearch($scope, $lookfor, $isAdvanced, $searchType, $facetsApplied, $numResults){
		if ($this->trackingDisabled) return;
		//Make sure we aren't logging a bunch of spam
		if (strlen($lookfor) >= 256){
			return;
		}
		if (preg_match('/http:|mailto:|https:/i', $lookfor)){
			return;
		}
		$this->search = new Analytics_Search();
		$this->search->sessionId = $this->session->id;
		$this->search->scope = $scope;
		$this->search->lookfor = $lookfor;
		$this->search->isAdvanced = $isAdvanced;
		$this->search->searchType = $searchType;
		$this->search->facetsApplied = $facetsApplied;
		$this->search->searchTime = time();
		$this->search->numResults = $numResults;
	}

	function __destruct(){
		$this->finish();
	}

	function finish(){
		if ($this->finished){
			return;
		}
		$this->finished = true;
		global $configArray;
		if (!isset($configArray['System']['enableAnalytics']) || $configArray['System']['enableAnalytics'] == false){
			return;
		}

		//disableErrorHandler();
		if (!$this->trackingDisabled){
			//Save or update the session
			$this->session->lastRequestTime = time();
			$this->session->update();
			//Save the page view
			$this->pageView->pageEndTime = time();
			$this->pageView->loadTime =$this->pageView->pageEndTime - $this->pageView->pageStartTime;
			$this->pageView->insert();
			//Save searches
			if ($this->search){
				$this->search->insert();
			}
		}
		//Save events
		foreach ($this->events as $event){
			$event->insert();
		}

		//enableErrorHandler();
	}

	function getSessionFilters(){
		/** @var Analytics_Session $session  */
		$session = null;
		if (isset($_REQUEST['filter']) && isset($_REQUEST['filterValue'])){
			$filterFields = $_REQUEST['filter'];
			$filterValues = $_REQUEST['filterValue'];
			foreach($filterFields as $index => $fieldName){
				if (isset($filterValues[$index])){
					$value = $filterValues[$index];
					if (in_array($fieldName, array('countryId', 'cityId', 'stateId', 'themeId', 'mobile', 'deviceId', 'physicalLocationId', 'patronTypeId', 'homeLocationId'))){
						if ($session == null){
							$session = new Analytics_Session();
						}

						$session->$fieldName = $value;
					}
				}
			}
		}
		return $session;
	}

	function getSessionFilterString(){
		$filterParams = "";
		if (isset($_REQUEST['filter'])){
			foreach ($_REQUEST['filter'] as $index => $filterName){
				if (isset($_REQUEST['filterValue'][$index])){
					if (strlen($filterParams) > 0){
						$filterParams .= "&";
					}
					$filterVal = $_REQUEST['filterValue'][$index];
					$filterParams .= "filter[$index]={$filterName}";
					$filterParams .= "&filterValue[$index]={$filterVal}";
				}
			}
		}
		if (isset($_REQUEST['startDate'])){
			$filterParams .= "&startDate=" . urlencode($_REQUEST['startDate']);
		}
		if (isset($_REQUEST['endDate'])){
			$filterParams .= "&endDate=" . urlencode($_REQUEST['endDate']);
		}
		return $filterParams;
	}

	function getSessionFilterSQL(){
		$sessionFilterSQL = null;
		if (isset($_REQUEST['filter'])){
			$filterFields = $_REQUEST['filter'];
			$filterValues = isset($_REQUEST['filterValue']) ? $_REQUEST['filterValue'] : array();
			foreach($filterFields as $index => $fieldName){
				if (isset($filterValues[$index])){
					$value = $filterValues[$index];
					if (in_array($fieldName, array('country', 'city', 'state', 'theme', 'mobile', 'device', 'physicalLocation', 'patronType', 'homeLocationId'))){
						if ($sessionFilterSQL != null){
							$sessionFilterSQL .= " AND ";
						}
						$sessionFilterSQL .= "$fieldName = '" . mysql_escape_string($value) . "'";
					}
				}
			}
		}
		return $sessionFilterSQL;
	}

	function getReportData($source, $forGraph = false){
		$data = array();
		if ($source == 'searchesByType'){
			$data['name'] = "Searches By Type";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Search Type', 'Times Used', 'Percent Usage');
			$data['data'] = $this->getSearchesByType($forGraph);
		}elseif ($source == 'searchesByScope'){
			$data['name'] = "Searches By Scope";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Scope', 'Number of Searches', 'Percent Usage');
			$data['data'] = $this->getSearchesByScope($forGraph);
		}elseif ($source == 'searchesWithFacets'){
			$data['name'] = "Searches with Facets";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Searches', 'Percent Usage');
			$data['data'] = $this->getSearchesWithFacets($forGraph);
		}elseif ($source == 'facetUsageByType'){
			$data['name'] = "Facet Usage";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Facet', 'Number of Searches', 'Percent Usage');
			$data['data'] = $this->getFacetUsageByType($forGraph);
		}elseif ($source == 'topSearches'){
			$data['name'] = "Top Searches";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Search Term', 'Number of Searches');
			$data['paginate'] = true;
			$data['data'] = $this->getTopSearches($forGraph);

		}elseif ($source == 'pageViewsByModule'){
			$data['name'] = "Page Views By Module";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Module', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByModule($forGraph);
		}elseif ($source == 'pageViewsByModuleAction'){
			$data['name'] = "Page Views By Module and Action";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Module', 'Action', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByModuleAction($forGraph);
		}elseif ($source == 'pageViewsByTheme'){
			$data['name'] = "Page Views By Theme";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Theme', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByTheme($forGraph);
		}elseif ($source == 'pageViewsByDevice'){
			$data['name'] = "Page Views By Device";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Device', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByDevice($forGraph);
		}elseif ($source == 'pageViewsByHomeLocation'){
			$data['name'] = "Page Views By Home Location";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Patron Home Library', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByHomeLocation($forGraph);
		}elseif ($source == 'pageViewsByPhysicalLocation'){
			$data['name'] = "Page Views By Home Location";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Physical Location', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByPhysicalLocation($forGraph);

		}elseif ($source == 'holdsByResult'){
			$data['name'] = "Holds By Result";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('Hold Result', 'Times', '% Result');
			$data['data'] = $this->getHoldsByResult($forGraph);
		}elseif ($source == 'holdsPerSession'){
			$data['name'] = "Holds Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Placed', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsPerSession($forGraph);
		}elseif ($source == 'holdsCancelledPerSession'){
			$data['name'] = "Holds Cancelled Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Cancelled', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsCancelledPerSession($forGraph);
		}elseif ($source == 'holdsUpdatedPerSession'){
			$data['name'] = "Holds Updated Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Updated', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsCancelledPerSession($forGraph);
		}elseif ($source == 'holdsFailedPerSession'){
			$data['name'] = "Holds Failed Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Failed', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsCancelledPerSession($forGraph);
		}elseif ($source == 'renewalsByResult'){
			$data['name'] = "Renewals By Result";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('Rewnewal Result', 'Times', '% Result');
			$data['data'] = $this->getRenewalsByResult($forGraph);
		}
		return $data;
	}

	function getSearchesByType($forGraph){
		$searchesInfo = array();

		$searches = new Analytics_Search();
		$searches->selectAdd('count(analytics_search.id) as numSearches');
		$searches->selectAdd('searchType');
		$session = $this->getSessionFilters();
		if ($session != null){
			$searches->joinAdd($session);
		}
		$searches->addDateFilters();
		$searches->groupBy('searchType');
		$searches->orderBy('numSearches  DESC');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->searchType] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$numSearchesReported = 0;
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$numSearchesReported += $searchCount;
			if ($forGraph){
				$searchesInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}else{
				$searchesInfo[] = array($searchName, $searchCount, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}
			if ($forGraph && count($searchesInfo) >= 5){
				break;
			}
		}
		if ($forGraph){
			$searchesInfo[] = array('Other', (float)sprintf('%01.2f', (($totalSearches - $numSearchesReported) / $totalSearches) * 100));
		}
		return $searchesInfo;
	}

	function getSearchesByScope($forGraph){
		//load searches by type
		$searches = new Analytics_Search();
		$searches->selectAdd('count(analytics_search.id) as numSearches');
		$searches->selectAdd('scope');
		$session = $this->getSessionFilters();
		if ($session != null){
			$searches->joinAdd($session);
		}
		$searches->addDateFilters();
		$searches->groupBy('scope');
		$searches->orderBy('numSearches  DESC');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->scope] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesInfo = array();
		$numSearchesReported = 0;
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$numSearchesReported += $searchCount;
			if ($forGraph){
				$searchesInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}else{
				$searchesInfo[] = array($searchName, $searchCount, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}
			if ($forGraph && count($searchesInfo) >= 5){
				break;
			}
		}
		if ($forGraph){
			$searchesInfo[] = array('Other', (float)sprintf('%01.2f', (($totalSearches - $numSearchesReported) / $totalSearches) * 100));
		}

		return $searchesInfo;
	}

	function getSearchesWithFacets($forGraph){
		//load searches by type
		$searches = new Analytics_Search();
		$searches->selectAdd('count(analytics_search.id) as numSearches');
		$session = $this->getSessionFilters();
		if ($session != null){
			$searches->joinAdd($session);
		}
		$searches->addDateFilters();
		$searches->groupBy('facetsApplied');
		if ($forGraph){
			$searches->limit(0, 10);
		}
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->facetsApplied == 0 ? 'No Facets' : 'Facets Applied'] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesInfo = array();
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$searchesInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
		}

		return $searchesInfo;
	}

	function getFacetUsageByType($forGraph){
		$eventInfo = array();
		//load searches by type
		$events = new Analytics_Event();

		$events->selectAdd('count(analytics_event.id) as numEvents');
		$events->category = 'Apply Facet';
		$events->selectAdd('action');
		$session = $this->getSessionFilters();
		if ($session != null){
			$events->joinAdd($session);
		}
		$events->addDateFilters();
		$events->groupBy('action');
		$events->orderBy('numEvents DESC');
		$events->find();
		$eventsByFacetTypeRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsByFacetTypeRaw[$events->action] = (int)$events->numEvents;
			$totalEvents += $events->numEvents;
		}
		$numReported = 0;
		foreach ($eventsByFacetTypeRaw as $searchName => $searchCount){
			if ($forGraph && (float)($searchCount / $totalEvents) < .02){
				break;
			}
			$numReported += $searchCount;
			if ($forGraph){
				$eventInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalEvents) * 100));
			}else{
				$eventInfo[] = array($searchName, $searchCount, (float)sprintf('%01.2f', ($searchCount / $totalEvents) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($totalEvents - $numReported) / $totalEvents) * 100));
		}

		return $eventInfo;
	}

	function getTopSearches($forGraph){
		$search = new Analytics_Search();
		$search->selectAdd();
		$search->selectAdd("count(analytics_search.id) as numSearches");
		$search->selectAdd("lookfor");
		$session = $this->getSessionFilters();
		if ($session != null){
			$search->joinAdd($session);
		}
		$search->addDateFilters();
		$search->whereAdd("numResults > 0");
		$search->groupBy('lookfor');
		$search->orderBy('numSearches DESC');
		if ($forGraph){
			$search->limit(0, 20);
		}else{

			$search->limit(0, 50);
		}
		$search->find();
		$topSearches = array();
		while ($search->fetch()){
			if (!is_null($search->lookfor) && strlen(trim($search->lookfor)) > 0){
				$searchTerm = $search->lookfor;
			}else{
				$searchTerm = "&lt;blank&gt;";
			}
			if ($forGraph){
				$topSearches[] = "$searchTerm ({$search->numSearches})";
			}else{
				$topSearches[] = array($searchTerm, $search->numSearches);
			}
		}
		return $topSearches;
	}

	function getPageViewsByDevice($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		require_once ROOT_DIR . '/sys/analytics/Analytics_Device.php';
		$device = new Analytics_Device();
		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$pageViews->addDateFilters();
		$session->joinAdd($device);
		$pageViews->joinAdd($session);
		$pageViews->groupBy('deviceId');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->value, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByHomeLocation($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$location = new Location();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$pageViews->addDateFilters();
		$session->joinAdd($location);
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('displayName');
		$pageViews->groupBy('displayName');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->displayName, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByPhysicalLocation($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		require_once ROOT_DIR . '/sys/analytics/Analytics_PhysicalLocation.php';
		$physicalLocation = new Analytics_PhysicalLocation;

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$session->joinAdd($physicalLocation);
		$pageViews->addDateFilters();
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('value');
		$pageViews->groupBy('physicalLocationId');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 5);
		}
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->value, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByTheme($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		require_once ROOT_DIR . '/sys/analytics/Analytics_Theme.php';
		$theme = new Analytics_Theme;
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$session->joinAdd($theme);
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->addDateFilters();
		$pageViews->selectAdd('value');
		$pageViews->groupBy('themeId');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByThemeRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByThemeRaw[] = array ($pageViews->value, (int)$pageViews->numViews);
		}

		return $pageViewsByThemeRaw;
	}

	function getPageViewsByModule($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->selectAdd('module');
		$session = $this->getSessionFilters();
		if ($session != null){
			$pageViews->joinAdd($session);
		}
		$pageViews->addDateFilters();
		$pageViews->groupBy('module');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByModuleRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByModuleRaw[] = array ($pageViews->module, (int)$pageViews->numViews);
		}

		return $pageViewsByModuleRaw;
	}

	function getPageViewsByModuleAction($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->selectAdd('module');
		$pageViews->selectAdd('action');
		$session = $this->getSessionFilters();
		if ($session != null){
			$pageViews->joinAdd($session);
		}
		$pageViews->addDateFilters();
		$pageViews->groupBy('module');
		$pageViews->groupBy('action');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByModuleRaw = array();
		while ($pageViews->fetch()){
			if ($forGraph){
				$pageViewsByModuleRaw[] = array ($pageViews->module . ' - ' . $pageViews->action, (int)$pageViews->numViews);
			}else{$pageViewsByModuleRaw[] = array ($pageViews->module, $pageViews->action, (int)$pageViews->numViews);

			}
		}

		return $pageViewsByModuleRaw;
	}

	function getRenewalsByResult($forGraph){
		//load searches by type
		$events = new Analytics_Event();
		$events->addDateFilters();
		$events->selectAdd('data');
		$events->selectAdd('count(analytics_event.id) as numEvents');
		$events->category = 'ILS Integration';
		$events->whereAdd("action in ('Renew Successful', 'Renew Failed')");
		$session = $this->getSessionFilters();
		if ($session != null){
			$events->joinAdd($session);
		}
		$events->groupBy('action');
		$events->orderBy('numEvents DESC');
		$events->find();
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->action] = (int)$events->numEvents;
			$totalEvents += $events->numEvents;
		}
		$numReported = 0;
		$eventInfo = array();
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $totalEvents) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($totalEvents - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($totalEvents - $numReported) / $totalEvents) * 100));
		}

		return $eventInfo;
	}

	function getHoldsByResult($forGraph){
		//load searches by type
		$events = new Analytics_Event();
		$events->addDateFilters();
		$events->selectAdd('data');
		$events->selectAdd('count(analytics_event.id) as numEvents');
		$events->category = 'ILS Integration';
		$events->whereAdd("action in ('Failed Hold', 'Successful Hold')");
		$session = $this->getSessionFilters();
		if ($session != null){
			$events->joinAdd($session);
		}
		$events->groupBy('action');
		$events->orderBy('numEvents DESC');
		$events->find();
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->action] = (int)$events->numEvents;
			$totalEvents += $events->numEvents;
		}
		$numReported = 0;
		$eventInfo = array();
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $totalEvents) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($totalEvents - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($totalEvents - $numReported) / $totalEvents) * 100));
		}

		return $eventInfo;
	}

	function getHoldsPerSession($forGraph){
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		//load searches by type
		$events = new Analytics_Event();
		$eventDateFilter = $events->getDateFilterSQL();
		$sessionFilter = $this->getSessionFilterSQL();
		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(analytics_event.id) as numHolds, sessionId FROM analytics_event INNER JOIN analytics_session on sessionId = analytics_session.id WHERE action ='Successful Hold' " . $eventDateFilter . " " . $sessionFilter . " GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;

		$totalEvents = $numTotalSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $totalEvents) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($totalEvents - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($totalEvents - $numReported) / $totalEvents) * 100));
		}

		return $eventInfo;
	}

	function getHoldsCancelledPerSession($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		//Get total sessions from the databse
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		$eventDateFilter = $events->getDateFilterSQL();
		$sessionFilter = $this->getSessionFilterSQL();
		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(analytics_event.id) as numHolds, sessionId FROM analytics_event INNER JOIN analytics_session on sessionId = analytics_session.id WHERE action ='Hold Cancelled' " . $eventDateFilter . " " . $sessionFilter . " GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $numTotalSessions) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($numTotalSessions - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($numTotalSessions - $numReported) / $numTotalSessions) * 100));
		}

		return $eventInfo;
	}

	function getHoldsUpdatedPerSession($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		//Get total sessions from the databse
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		$eventDateFilter = $events->getDateFilterSQL();
		$sessionFilter = $this->getSessionFilterSQL();
		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(analytics_event.id) as numHolds, sessionId FROM analytics_event INNER JOIN analytics_session on sessionId = analytics_session.id WHERE action ='Hold Updated' " . $eventDateFilter . " " . $sessionFilter . " GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $numTotalSessions) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($numTotalSessions - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($numTotalSessions - $numReported) / $numTotalSessions) * 100));
		}

		return $eventInfo;
	}

	function getHoldsFailedPerSession($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		//Get total sessions from the databse
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		$eventDateFilter = $events->getDateFilterSQL();
		$sessionFilter = $this->getSessionFilterSQL();
		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(analytics_event.id) as numHolds, sessionId FROM analytics_event INNER JOIN analytics_session on sessionId = analytics_session.id WHERE action ='Failed Hold' " . $eventDateFilter . " " . $sessionFilter . " GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $numTotalSessions) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($numTotalSessions - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($numTotalSessions - $numReported) / $numTotalSessions) * 100));
		}

		return $eventInfo;
	}
}