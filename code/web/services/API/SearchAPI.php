<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Pager.php';

class SearchAPI extends Action
{

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		if (!in_array($method, array('getListWidget', 'getCollectionSpotlight', 'getIndexStatus')) && !IPAddress::allowAPIAccessForClientIP()){
			$this->forbidAPIAccess();
		}
		$output = '';
		if (!empty($method) && method_exists($this, $method)) {
			if (in_array($method, array('getListWidget', 'getCollectionSpotlight'))) {
				$output = $this->$method();
			} else {
				$jsonOutput = json_encode(array('result' => $this->$method()));
			}
		} else {
			$jsonOutput = json_encode(array('error' => 'invalid_method'));
		}

		// Set Headers
		if (isset($jsonOutput)) {
			header('Content-type: application/json');
		} else {
			header('Content-type: text/html');
		}
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		echo isset($jsonOutput) ? $jsonOutput : $output;
	}

	// The time intervals in seconds beyond which we consider the status as not current
	const
		STATUS_OK = 'okay',
		STATUS_WARN = 'warning',
		STATUS_CRITICAL = 'critical';


	function getIndexStatus()
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		global $configArray;
		$checks = [];
		$serverStats = [];

		//Check if solr is running by pinging it
		/** @var SearchObject_GroupedWorkSearcher $solrSearcher */
		$solrSearcher = SearchObjectFactory::initSearchObject('GroupedWork');
		if (!$solrSearcher->ping()) {
			$this->addCheck($checks, 'Solr', self::STATUS_CRITICAL, "Solr is not responding");
		}else{
			$this->addCheck($checks, 'Solr');
		}

		//Check for a current backup
		global $serverName;
		$backupDir = "/data/aspen-discovery/{$serverName}/sql_backup/";
		if (!file_exists($backupDir)){
			$this->addCheck($checks, 'Backup', self::STATUS_CRITICAL, "Backup directory $backupDir does not exist");
		}else{
			$backupFiles = scandir($backupDir);
			$backupFileFound = false;
			$backupFileTooSmall = false;
			foreach ($backupFiles as $backupFile){
				if (preg_match('/.*\.sql\.gz/', $backupFile)){
					$fileCreationTime = filectime($backupDir . $backupFile);
					if ((time() - $fileCreationTime) < (24.5 * 60 * 60)){
						$fileSize = filesize($backupDir . $backupFile);
						if ($fileSize > 1000) {
							//We have a backup file created in the last 24.5 hours (30 min buffer to give time for the backup to be created)
							$backupFileFound = true;
						}else{
							$backupFileFound = true;
							$backupFileTooSmall = true;
						}
					}
				}
			}
			if (!$backupFileFound){
				$this->addCheck($checks, 'Backup', self::STATUS_CRITICAL, "A current backup of Aspen was not found in $backupDir.  Check my.cnf to be sure mysqldump credentials exist.");
			}else{
				if ($backupFileTooSmall){
					$this->addCheck($checks, 'Backup', self::STATUS_CRITICAL, "The backup for Aspen was found, but is too small.  Check my.cnf to be sure mysqldump credentials exist.");
				}else{
					$this->addCheck($checks, 'Backup');
				}
			}
		}

		//Check free disk space
		if (is_dir('/data')) {
			$freeSpace = disk_free_space('/data');
			$this->addServerStat($serverStats, 'Data Disk Space', StringUtils::formatBytes($freeSpace));
			if ($freeSpace < 7500000000) {
				$this->addCheck($checks, 'Data Disk Space', self::STATUS_CRITICAL, "The data drive currently has less than 7.5GB of space available");
			} else {
				$this->addCheck($checks, 'Data Disk Space');
			}
		}

		//Check free disk space
		if (is_dir('/usr')) {
			$freeSpace = disk_free_space('/usr');
			$this->addServerStat($serverStats, 'Usr Disk Space', StringUtils::formatBytes($freeSpace));
			if ($freeSpace < 5000000000) {
				$this->addCheck($checks, 'Usr Disk Space', self::STATUS_CRITICAL, "The usr drive currently has less than 5GB of space available");
			} else {
				$this->addCheck($checks, 'Usr Disk Space');
			}
		}

		//Check free memory
		if ($configArray['System']['operatingSystem'] == 'linux'){
			$fh = fopen('/proc/meminfo','r');
			$freeMem = 0;
			$totalMem = 0;
			while ($line = fgets($fh)) {
				$pieces = array();
				if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) {
					$totalMem = $pieces[1] * 1024;
				}else if (preg_match('/^MemAvailable:\s+(\d+)\skB$/', $line, $pieces)) {
					$freeMem = $pieces[1] * 1024;
				}
			}
			$this->addServerStat($serverStats, 'Total Memory', StringUtils::formatBytes($totalMem));
			$this->addServerStat($serverStats, 'Available Memory', StringUtils::formatBytes($freeMem));
			$percentMemoryUsage = round((1 - ($freeMem / $totalMem)) * 100, 1);
			$this->addServerStat($serverStats, 'Percent Memory In Use', $percentMemoryUsage);
			if ($freeMem < 1000000000){
				$this->addCheck($checks, 'Memory Usage', self::STATUS_CRITICAL, "Less than 1GB ($freeMem) of available memory exists, increase available resources");
			}elseif ($percentMemoryUsage > 95){
				$this->addCheck($checks, 'Memory Usage', self::STATUS_CRITICAL, "{$percentMemoryUsage}% of total memory is in use, increase available resources");
			}else{
				$this->addCheck($checks, 'Memory Usage');
			}
			fclose($fh);

			//Get the number of CPUs available
			$numCPUs = (int)shell_exec("cat /proc/cpuinfo | grep processor | wc -l");

			//Check load (use the 5 minute load)
			$load = sys_getloadavg();
			$this->addServerStat($serverStats, '1 minute Load Average', $load[0]);
			$this->addServerStat($serverStats, '5 minute Load Average', $load[1]);
			$this->addServerStat($serverStats, '15 minute Load Average', $load[2]);
			$this->addServerStat($serverStats, 'Load Per CPU', ($load[1] / $numCPUs));
			if ($load[1] > $numCPUs * 1.5){
				if ($load[0] >= $load[1]){
					$this->addCheck($checks, 'Load Average', self::STATUS_CRITICAL, "Load is very high {$load[1]} and is increasing");
				}else{
					$this->addCheck($checks, 'Load Average', self::STATUS_WARN, "Load is very high {$load[1]}, but it is decreasing");
				}
			}else{
				$this->addCheck($checks, 'Load Average');
			}

			//Check wait time
			$topInfo = shell_exec("top -n 1 -b | grep %Cpu");
			if (preg_match('/(\d+\.\d+) wa,/', $topInfo, $matches)){
				$waitTime = $matches[1];
				$this->addServerStat($serverStats, 'Wait Time', $waitTime);
				if ($waitTime > 15){
					$this->addCheck($checks, 'Wait Time', self::STATUS_WARN, "Wait time is over 15 $waitTime");
				}elseif ($waitTime > 30){
					$this->addCheck($checks, 'Wait Time', self::STATUS_CRITICAL, "Wait time is over 30 $waitTime");
				}else{
					$this->addCheck($checks, 'Wait Time');
				}
			}else{
				$this->addCheck($checks, 'Wait Time', self::STATUS_CRITICAL, "Wait time not found in $topInfo");
			}
		}

		//Check nightly index
		require_once ROOT_DIR . '/sys/Indexing/ReindexLogEntry.php';
		$logEntry = new ReindexLogEntry();
		$logEntry->orderBy("id DESC");
		$logEntry->limit(0, 1);
		if ($logEntry->find(true)){
			if ($logEntry->numErrors > 0){
				$this->addCheck($checks, 'Nightly Index', self::STATUS_CRITICAL, 'The last nightly index had errors');
			}else{
				$this->addCheck($checks, 'Nightly Index');
			}
		}else{
			$this->addCheck($checks, 'Nightly Index', self::STATUS_CRITICAL, 'Nightly index has never run');
		}

		//Check for errors within the logs
		require_once ROOT_DIR . '/sys/Module.php';
		$aspenModule = new Module();
		$aspenModule->enabled = true;
		$aspenModule->find();
		while ($aspenModule->fetch()){
			if (!empty($aspenModule->logClassPath) && !empty($aspenModule->logClassName)){
				//Check to see how many settings we have
				$numSettings = 1;
				if (!empty($aspenModule->settingsClassPath) && !empty($aspenModule->settingsClassName)){
					/** @noinspection PhpIncludeInspection */
					require_once ROOT_DIR . $aspenModule->settingsClassPath;
					/** @var DataObject $settings */
					$settings = new $aspenModule->settingsClassName;
					$numSettings = $settings->count();
				}
				if ($numSettings == 0){
					continue;
				}
				/** @noinspection PhpIncludeInspection */
				require_once ROOT_DIR . $aspenModule->logClassPath;
				/** @var BaseLogEntry $logEntry */
				$logEntry = new $aspenModule->logClassName();
				$logEntry->orderBy("id DESC");
				$logEntry->limit(0, 3 * $numSettings);
				$logErrors = 0;
				$logEntry->find();
				$numUnfinishedEntries = 0;
				$lastFinishTime = 0;
				$isFirstEntry = true;
				while ($logEntry->fetch()){
					if ($logEntry->numErrors > 0){
						$logErrors++;
					}
					if (empty($logEntry->endTime)){
						$numUnfinishedEntries++;
						if ($isFirstEntry && (time() - $logEntry->startTime) >= 8 * 60 * 60){
							$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "The last log entry for {$aspenModule->name} has been running for more than 8 hours");
						}
					}else{
						if ($logEntry->endTime > $lastFinishTime){
							$lastFinishTime = $logEntry->endTime;
						}
					}
					$isFirstEntry = false;
				}
				$checkEntriesInLast24Hours = true;
				if ($aspenModule->name == 'Open Archives'){
					$checkEntriesInLast24Hours = false;
				}
				if ($checkEntriesInLast24Hours && ($lastFinishTime < time() - 24 * 60 * 60)){
					$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "No log entries for {$aspenModule->name} have completed in the last 24 hours");
				}else{
					if ($logErrors > 0){
						$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "The last {$logErrors} log entry for {$aspenModule->name} had errors");
					}else{
						if ($numUnfinishedEntries > $numSettings){
							$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "{$numUnfinishedEntries} of the last 3 log entries for {$aspenModule->name} did not finish.");
						}else{
							$this->addCheck($checks, $aspenModule->name);
						}
					}
				}
			}
		}

		//Check cron to be sure it doesn't have errors either
		require_once ROOT_DIR . '/sys/CronLogEntry.php';
		$cronLogEntry = new CronLogEntry();
		$cronLogEntry->orderBy("id DESC");
		$cronLogEntry->limit(0, 1);
		if ($cronLogEntry->find(true)){
			if ($cronLogEntry->numErrors > 0){
				$this->addCheck($checks, "Cron", self::STATUS_WARN, "The last cron log entry had errors");
			}else{
				$this->addCheck($checks, "Cron");
			}
		}

		//Check to see if sitemaps have been created
		$sitemapFiles = scandir(ROOT_DIR . '/sitemaps');
		$groupedWorkSitemapFound = false;
		foreach ($sitemapFiles as $sitemapFile) {
			if (strpos($sitemapFile, 'grouped_work_site_map_') === 0){
				$groupedWorkSitemapFound = true;
				break;
			}
		}
		if (!$groupedWorkSitemapFound){
			$this->addCheck($checks, "Sitemap", self::STATUS_CRITICAL, "No sitemap found for grouped works");
		}else{
			$this->addCheck($checks, "Sitemap");
		}

		// Unprocessed Offline Holds //
		$offlineHoldEntry = new OfflineHold();
		$offlineHoldEntry->status = 'Not Processed';
		$offlineHolds = $offlineHoldEntry->count();
		if (!empty($offlineHolds)) {
			$this->addCheck($checks, "Offline Holds", self::STATUS_CRITICAL, "There are $offlineHolds un-processed offline holds");
		}else{
			$this->addCheck($checks, "Offline Holds");
		}

		$hasCriticalErrors = false;
		$hasWarnings = false;
		foreach ($checks as $check){
			if ($check['status'] == self::STATUS_CRITICAL){
				$hasCriticalErrors = true;
				break;
			}if ($check['status'] == self::STATUS_WARN){
				$hasWarnings = true;
			}
		}

		if ($hasCriticalErrors || $hasWarnings) {
			$result = array(
				'aspen_health_status' => $hasCriticalErrors ? self::STATUS_CRITICAL : self::STATUS_WARN, // Critical warnings trump Warnings;
				'message' => "Errors have been found",
				'checks' => $checks,
				'serverStats' => $serverStats
			);
		} else {
			$result = array(
				'aspen_health_status' => self::STATUS_OK,
				'message' => "Everything is current",
				'checks' => $checks,
				'serverStats' => $serverStats
			);
		}

		if (isset($_REQUEST['prtg'])) {
			// Reformat $result to the structure expected by PRTG

			$prtgStatusValues = array(
				self::STATUS_OK => 0,
				self::STATUS_WARN => 1,
				self::STATUS_CRITICAL => 2
			);

			$prtg_results = array(
				'prtg' => array(
					'result' => array(
						0 => array(
							'channel' => 'Aspen Status',
							'value' => $prtgStatusValues[$result['status']],
							'limitmode' => 1,
							'limitmaxwarning' => $prtgStatusValues[self::STATUS_OK],
							'limitmaxerror' => $prtgStatusValues[self::STATUS_WARN]
						)
					),
					'text' => $result['message']
				)
			);

			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

			die(json_encode($prtg_results));
		}

		return $result;
	}

	private function addCheck(&$checks, $checkName, $status = self::STATUS_OK, $note = ''){
		$checkNameMachine = str_replace(' ', '_', strtolower($checkName));
		$checks[$checkNameMachine] = [
			'name' => $checkName,
			'status' => $status
		];
		if (!empty($note)){
			$checks[$checkNameMachine]['note'] = $note;
		}
	}

	private function addServerStat(array &$serverStats, string $statName, $value)
	{
		$statNameMachine = str_replace(' ', '_', strtolower($statName));
		$serverStats[$statNameMachine] = [
			'name' => $statName,
			'value' => $value
		];
	}

	/**
	 * Do a basic search and return results as a JSON array
	 */
	function search()
	{
		global $interface;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$timer->logTime('Include search engine');

		//setup the results array.
		$jsonResults = array();

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->assign('sortList', $searchObject->getSortList());
		$interface->assign('rssLink', $searchObject->getRSSUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result->getMessage());
		}
		$timer->logTime('Process Search');

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();

		if ($searchObject->getResultTotal() < 1) {
			// No record found
			$interface->setTemplate('list-none.tpl');
			$jsonResults['recordCount'] = 0;

			// Was the empty result set due to an error?
			$error = $searchObject->getIndexError();
			if ($error !== false) {
				// If it's a parse error or the user specified an invalid field, we
				// should display an appropriate message:
				if (stristr($error, 'org.apache.lucene.queryParser.ParseException') ||
					preg_match('/^undefined field/', $error)) {
					$jsonResults['parseError'] = true;

					// Unexpected error -- let's treat this as a fatal condition.
				} else {
					AspenError::raiseError(new AspenError('Unable to process query<br />' .
						'Solr Returned: ' . $error));
				}
			}

			$timer->logTime('no hits processing');

		} else {
			$timer->logTime('save search');

			// Assign interface variables
			$summary = $searchObject->getResultSummary();
			$jsonResults['recordCount'] = $summary['resultTotal'];
			$jsonResults['recordStart'] = $summary['startRecord'];
			$jsonResults['recordEnd'] = $summary['endRecord'];

			// Big one - our results
			$recordSet = $searchObject->getResultRecordSet();
			//Remove fields as needed to improve the display.
			foreach ($recordSet as $recordKey => $record) {
				unset($record['auth_author']);
				unset($record['auth_authorStr']);
				unset($record['callnumber-first-code']);
				unset($record['spelling']);
				unset($record['callnumber-first']);
				unset($record['title_auth']);
				unset($record['callnumber-subject']);
				unset($record['author-letter']);
				unset($record['marc_error']);
				unset($record['shortId']);
				$recordSet[$recordKey] = $record;
			}
			$jsonResults['recordSet'] = $recordSet;
			$timer->logTime('load result records');

			$facetSet = $searchObject->getFacetList();
			$jsonResults['facetSet'] = [];
			foreach ($facetSet as $name => $facetInfo){
				$jsonResults['facetSet'][$name] = [
					'label' => $facetInfo['label']->displayName,
					'list' => $facetInfo['list'],
					'hasApplied' => $facetInfo['hasApplied'],
					'valuesToShow' => $facetInfo['valuesToShow'],
					'showAlphabetically' => $facetInfo['showAlphabetically'],
				];
			}

			//Check to see if a format category is already set
			$categorySelected = false;
			if (isset($facetSet['top'])) {
				foreach ($facetSet['top'] as $title => $cluster) {
					if ($cluster['label'] == 'Category') {
						foreach ($cluster['list'] as $thisFacet) {
							if ($thisFacet['isApplied']) {
								$categorySelected = true;
							}
						}
					}
				}
			}
			$jsonResults['categorySelected'] = $categorySelected;
			$timer->logTime('finish checking to see if a format category has been loaded already');

			// Process Paging
			$link = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
				'fileName' => $link,
				'perPage' => $summary['perPage']);
			$pager = new Pager($options);
			$jsonResults['paging'] = array(
				'currentPage' => $pager->getCurrentPage(),
				'totalPages' => $pager->getTotalPages(),
				'totalItems' => $pager->getTotalItems(),
				'itemsPerPage' => $pager->getItemsPerPage(),
			);
			$interface->assign('pageLinks', $pager->getLinks());
			$timer->logTime('finish hits processing');
		}

		// Report additional information after the results
		$jsonResults['query_time'] = round($searchObject->getQuerySpeed(), 2);
		$jsonResults['lookfor'] = $searchObject->displayQuery();
		$jsonResults['searchType'] = $searchObject->getSearchType();
		// Will assign null for an advanced search
		$jsonResults['searchIndex'] = $searchObject->getSearchIndex();
		$jsonResults['time'] = round($searchObject->getTotalSpeed(), 2);
		$jsonResults['savedSearch'] = $searchObject->isSavedSearch();
		$jsonResults['searchId'] = $searchObject->getSearchId();
		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$jsonResults['page'] = $currentPage;


		// Save the ID of this search to the session so we can return to it easily:
		$_SESSION['lastSearchId'] = $searchObject->getSearchId();

		// Save the URL of this search to the session so we can return to it easily:
		$_SESSION['lastSearchURL'] = $searchObject->renderSearchUrl();

		// Return the results for display to the user.
		return $jsonResults;
	}

	/**
	 * This is old for historical compatibility purposes.
	 *
	 * @return string
	 * @noinspection PhpUnused
	 * @deprecated
	 *
	 */
	function getListWidget()
	{
		return $this->getCollectionSpotlight();
	}

	function getCollectionSpotlight()
	{
		global $interface;
		if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::validateAccount($username, $password);
			$interface->assign('user', $user);
		} else {
			$user = UserAccount::getLoggedInUser();
			$interface->assign('user', $user);
		}
		//Load the collectionSpotlight configuration
		require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
		require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';
		$collectionSpotlight = new CollectionSpotlight();
		$id = $_REQUEST['id'];

		if (isset($_REQUEST['reload'])) {
			$interface->assign('reload', true);
		} else {
			$interface->assign('reload', false);
		}

		$collectionSpotlight->id = $id;
		if ($collectionSpotlight->find(true)) {
			$interface->assign('collectionSpotlight', $collectionSpotlight);

			if (!empty($_REQUEST['resizeIframe'])) {
				$interface->assign('resizeIframe', true);
			}
			//return the collectionSpotlight
			return $interface->fetch('CollectionSpotlight/collectionSpotlight.tpl');
		} else {
			return '';
		}
	}

	/** @noinspection PhpUnused */
	function getRecordIdForTitle()
	{
		$title = strip_tags($_REQUEST['title']);
		$_REQUEST['lookfor'] = $title;
		$_REQUEST['searchIndex'] = 'Keyword';

		global $interface;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$timer->logTime('Include search engine');

		// Initialise from the current search globals
		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->assign('sortList', $searchObject->getSortList());
		$interface->assign('rssLink', $searchObject->getRSSUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result->getMessage());
		}

		if ($searchObject->getResultTotal() < 1) {
			return "";
		} else {
			//Return the first result
			$recordSet = $searchObject->getResultRecordSet();
			$firstRecord = reset($recordSet);
			return $firstRecord['id'];
		}
	}

	/** @noinspection PhpUnused */
	function getRecordIdForItemBarcode()
	{
		$barcode = strip_tags($_REQUEST['barcode']);
		$_REQUEST['lookfor'] = $barcode;
		$_REQUEST['searchIndex'] = 'barcode';

		global $interface;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$timer->logTime('Include search engine');

		// Initialise from the current search globals
		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->assign('sortList', $searchObject->getSortList());
		$interface->assign('rssLink', $searchObject->getRSSUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result->getMessage());
		}

		if ($searchObject->getResultTotal() >= 1) {
			//Return the first result
			$recordSet = $searchObject->getResultRecordSet();
			foreach ($recordSet as $recordKey => $record) {
				return $record['id'];
			}
		}
		return "";
	}

	/** @noinspection PhpUnused */
	function getTitleInfoForISBN()
	{
		if (isset($_REQUEST['isbn'])){
			$isbn = str_replace('-', '', strip_tags($_REQUEST['isbn']));
		}else{
			$isbn = '';
		}

		$_REQUEST['lookfor'] = $isbn;
		$_REQUEST['searchIndex'] = 'ISN';

		global $interface;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$timer->logTime('Include search engine');

		//setup the results array.
		$jsonResults = array();

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		// Set Interface Variables
		//   Those we can construct BEFORE the search is executed
		$interface->assign('sortList', $searchObject->getSortList());
		$interface->assign('rssLink', $searchObject->getRSSUrl());

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result->getMessage());
		}

		global $solrScope;
		if ($searchObject->getResultTotal() >= 1) {
			//Return the first result
			$recordSet = $searchObject->getResultRecordSet();
			foreach ($recordSet as $recordKey => $record) {
				$jsonResults[] = array(
					'id' => $record['id'],
					'title' => isset($record['title_display']) ? $record['title_display'] : null,
					'author' => isset($record['author_display']) ? $record['author_display'] : (isset($record['author2']) ? $record['author2'] : ''),
					'format' => isset($record['format_' . $solrScope]) ? $record['format_' . $solrScope] : '',
					'format_category' => isset($record['format_category_' . $solrScope]) ? $record['format_category_' . $solrScope] : '',
				);
			}
		}
		return $jsonResults;
	}

	function getBreadcrumbs()
	{
		return [];
	}
}
