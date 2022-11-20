<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Pager.php';

class SearchAPI extends Action
{

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$output = '';

		//Set Headers
		header('Content-type: application/json');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		//Check if user can access API with keys sent from LiDA
		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if($this->grantTokenAccess()) {
				if (in_array($method, array('getAppBrowseCategoryResults', 'getAppActiveBrowseCategories', 'getAppSearchResults', 'getListResults', 'getSavedSearchResults', 'getSortList', 'getAppliedFilters', 'getAvailableFacets', 'getAvailableFacetsKeys', 'searchLite', 'getDefaultFacets', 'getFacetClusterByKey', 'searchFacetCluster', 'getFormatCategories', 'getBrowseCategoryListForUser'))) {
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('SearchAPI', $method);
					$jsonOutput = json_encode(array('result' => $this->$method()));
				} else {
					$output = json_encode(array('error' => 'invalid_method'));
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(array('error' => 'unauthorized_access'));
			}
			ExternalRequestLogEntry::logRequest('SearchAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], isset($jsonOutput) ? $jsonOutput : $output, []);
			echo isset($jsonOutput) ? $jsonOutput : $output;
		} elseif (IPAddress::allowAPIAccessForClientIP() || in_array($method, ['getListWidget', 'getCollectionSpotlight'])) {
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			if (!empty($method) && method_exists($this, $method)) {
				if (in_array($method, array('getListWidget', 'getCollectionSpotlight'))) {
					header('Content-type: text/html');
					$output = $this->$method();
				} else {
					$jsonOutput = json_encode(array('result' => $this->$method()));
				}
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('SearchAPI', $method);
				echo isset($jsonOutput) ? $jsonOutput : $output;
			} else {
				echo json_encode(array('error' => 'invalid_method'));
			}
		} else {
			$this->forbidAPIAccess();
		}
	}

	// The time intervals in seconds beyond which we consider the status as not current
	const
		STATUS_OK = 'okay',
		STATUS_WARN = 'warning',
		STATUS_CRITICAL = 'critical';


	function getIndexStatus()
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		require_once ROOT_DIR . '/services/API/SystemAPI.php';
		global $configArray;
		$checks = [];
		$serverStats = [];
		$systemApi = new SystemAPI();

		//Check if solr is running by pinging it
		/** @var SearchObject_AbstractGroupedWorkSearcher $solrSearcher */
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

		//Check for encryption key
		$hasKeyFile = $systemApi->doesKeyFileExist();
		if ($hasKeyFile){
			$this->addCheck($checks, 'Encryption Key');
		}else{
			$this->addCheck($checks, 'Encryption Key', self::STATUS_CRITICAL, "The encryption key does not exist.");
		}

		$hasPendingUpdates = $systemApi->hasPendingDatabaseUpdates();
		if ($hasPendingUpdates){
			$this->addCheck($checks, 'Pending Database Updates', self::STATUS_CRITICAL, "There are pending database updates.");
		}else{
			$this->addCheck($checks, 'Pending Database Updates');
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
			}elseif ($percentMemoryUsage > 95 && $freeMem < 2500000000){
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
			if ($load[1] > $numCPUs * 2.5){
				if ($load[0] >= $load[1]){
					$this->addCheck($checks, 'Load Average', self::STATUS_CRITICAL, "Load is very high {$load[1]} and is increasing");
				}else{
					$this->addCheck($checks, 'Load Average', self::STATUS_WARN, "Load is very high {$load[1]}, but it is decreasing");
				}
			}elseif ($load[1] > $numCPUs * 1.5){
				if ($load[0] >= $load[1]){
					$this->addCheck($checks, 'Load Average', self::STATUS_WARN, "Load is high {$load[1]} and is increasing");
				}else{
					$this->addCheck($checks, 'Load Average', self::STATUS_WARN, "Load is high {$load[1]}, but it is decreasing");
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
				//Check to see if it's after 8 am and the nightly index is still running.
				if (empty($logEntry->endTime) && date('H') >= 8 && date('H') < 21){
					$this->addCheck($checks, 'Nightly Index', self::STATUS_CRITICAL, "Nightly index is still running after 8 am.");
				}else {
					$this->addCheck($checks, 'Nightly Index');
				}
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
				$numEntriesToCheck = 3;
				if ($aspenModule->name == 'Open Archives'){
					$numEntriesToCheck = 1;
				}
				$logEntry->limit(0, $numEntriesToCheck * $numSettings);
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
				if ($aspenModule->name == 'Web Builder'){
					// Check to make sure there is web builder content to actually index
					require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
					require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
					require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
					$portalPage = new PortalPage();
					$basicPage = new BasicPage();
					$webResource = new WebResource();
					$portalPage->find();
					$basicPage->find();
					$webResource->find();
					if ($portalPage->getNumResults() > 0) {
						$checkEntriesInLast24Hours = true;
					} else if ($basicPage->getNumResults() > 0){
						$checkEntriesInLast24Hours = true;
					} else if ($webResource->getNumResults() > 0) {
						$checkEntriesInLast24Hours = true;
					} else {
						$checkEntriesInLast24Hours = false;
					}

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

		//Check for interface errors in the last hour
		$aspenError = new AspenError();
		$aspenError->whereAdd('timestamp > ' . (time() - 60 * 60));
		$numErrors = $aspenError->count();
		if ($numErrors > 10){
			$this->addCheck($checks, 'Interface Errors', self::STATUS_CRITICAL, "$numErrors Interface Errors have occurred in the last hour");
		}elseif ($numErrors > 1){
			$this->addCheck($checks, 'Interface Errors', self::STATUS_WARN, "$numErrors Interface Errors have occurred in the last hour");
		}else{
			$this->addCheck($checks, 'Interface Errors');
		}

		//Check for interface errors in the last hour
		$aspenError = new AspenError();
		$aspenError->whereAdd('timestamp > ' . (time() - 60 * 60));
		$numErrors = $aspenError->count();
		if ($numErrors > 10){
			$this->addCheck($checks, 'Interface Errors', self::STATUS_CRITICAL, "$numErrors Interface Errors have occurred in the last hour");
		}elseif ($numErrors > 1){
			$this->addCheck($checks, 'Interface Errors', self::STATUS_WARN, "$numErrors Interface Errors have occurred in the last hour");
		}else{
			$this->addCheck($checks, 'Interface Errors');
		}

		//Check NYT Log to see if it has errors
		require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
		$nytSetting = new NewYorkTimesSetting();
		if ($nytSetting->find(true)){
			require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';
			$nytLog = new NYTUpdateLogEntry();
			$nytLog->orderBy("id DESC");
			$nytLog->limit(0, 1);

			if (!$nytLog->find(true)){
				$this->addCheck($checks, 'NYT Lists', self::STATUS_WARN, "New York Times Lists have not been loaded");
			}else{
				$numErrors = 0;
				if ($nytLog->numErrors > 0){
					$numErrors++;
				}
				if ($numErrors > 0){
					$this->addCheck($checks, 'NYT Lists', self::STATUS_WARN, "The last log for New York Times Lists had errors");
				}else{
					$this->addCheck($checks, 'NYT Lists');
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

		//Check third party enrichment to see if it is enabled
		require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';
		$novelistSetting = new NovelistSetting();
		if ($novelistSetting->find(true)){
			$this->addCheck($checks, "Novelist");
		}

		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSetting = new SyndeticsSetting();
		if ($syndeticsSetting->find(true)){
			$this->addCheck($checks, "Syndetics");
		}

		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSetting = new ContentCafeSetting();
		if ($contentCafeSetting->find(true)){
			$this->addCheck($checks, "Content Cafe");
		}

		require_once ROOT_DIR . '/sys/Enrichment/CoceServerSetting.php';
		$coceSetting = new CoceServerSetting();
		if ($coceSetting->find(true)){
			$this->addCheck($checks, "Coce");
		}

		require_once ROOT_DIR . '/sys/Enrichment/OMDBSetting.php';
		$omdbSetting = new OMDBSetting();
		if ($omdbSetting->find(true)){
			$this->addCheck($checks, "OMDB");
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

		global $interface;
		$gitBranch = $interface->getVariable('gitBranchWithCommit');
		if ($hasCriticalErrors || $hasWarnings) {
			$result = array(
				'aspen_health_status' => $hasCriticalErrors ? self::STATUS_CRITICAL : self::STATUS_WARN, // Critical warnings trump Warnings;
				'version' => $gitBranch,
				'message' => "Errors have been found",
				'checks' => $checks,
				'serverStats' => $serverStats
			);
		} else {
			$result = array(
				'aspen_health_status' => self::STATUS_OK,
				'version' => $gitBranch,
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

		if (isset($_REQUEST['pageSize']) && is_numeric($_REQUEST['pageSize'])){
			$searchObject->setLimit($_REQUEST['pageSize']);
		}

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
			foreach ($facetSet as $name => $facetInfo) {
				$jsonResults['facetSet'][$name] = [
					'label' => $facetInfo['label'],
					'list' => $facetInfo['list'],
					'hasApplied' => $facetInfo['hasApplied'],
					'valuesToShow' => $facetInfo['valuesToShow'],
					'showAlphabetically' => $facetInfo['showAlphabetically'],
					'multiSelect' => (bool)$facetInfo['multiSelect'],
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

			$jsonResults['sortList'] = $searchObject->getSortList();
			$jsonResults['sortedBy'] = $searchObject->getSort();

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
		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
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
		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
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

	function getActiveBrowseCategories(){
		//Figure out which library or location we are looking at
		global $library;
		global $locationSingleton;
		global $configArray;
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listApi = new ListAPI();

		$includeSubCategories = false;
		if (isset($_REQUEST['includeSubCategories'])){
			$includeSubCategories = ($_REQUEST['includeSubCategories'] == 'true') || ($_REQUEST['includeSubCategories'] == 1);
		}
		//Check to see if we have an active location, will be null if we don't have a specific location
		//based off of url, branch parameter, or IP address
		$activeLocation = $locationSingleton->getActiveLocation();

		//Get a list of browse categories for that library / location
		/** @var BrowseCategoryGroupEntry[] $browseCategories */
		if ($activeLocation == null){
			//We don't have an active location, look at the library
			$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategories();
		}else{
			//We have a location get data for that
			$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategories();
		}

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		//Format for return to the user, we want to return
		// - the text id of the category
		// - the display label
		// - Clickable link to load the category
		$formattedCategories = array();
		foreach ($browseCategories as $curCategory){
			$categoryInformation = new BrowseCategory();
			$categoryInformation->id = $curCategory->browseCategoryId;

			if ($categoryInformation->find(true)) {
				if ($categoryInformation->isValidForDisplay()){
					if($categoryInformation->textId == "system_user_lists") {
						$userLists = $listApi->getUserLists();
						$categoryResponse['subCategories'] = [];
						$allUserLists = $userLists['lists'];
						if(count($allUserLists) > 0) {
							$categoryResponse = array(
								'text_id' => $categoryInformation->textId,
								'display_label' => $categoryInformation->label,
								'link' => $configArray['Site']['url'] . '?browseCategory=' . $categoryInformation->textId,
								'source' => $categoryInformation->source,
							);
							foreach ($allUserLists as $userList) {
								if($userList['id'] != "recommendations") {
									$categoryResponse['subCategories'][] = [
										'text_id' => $categoryInformation->textId . '_' . $userList['id'],
										'display_label' => $userList['title'],
										'source' => "List",
									];
								}
							}
							$formattedCategories[] = $categoryResponse;
						}
					} elseif($categoryInformation->textId == "system_saved_searches") {
						$savedSearches = $listApi->getSavedSearches();
						$categoryResponse['subCategories'] = [];
						$allSearches = $savedSearches['searches'];
						if(count($allSearches) > 0){
							$categoryResponse = array(
								'text_id' => $categoryInformation->textId,
								'display_label' => $categoryInformation->label,
								'link' => $configArray['Site']['url'] . '?browseCategory=' . $categoryInformation->textId,
								'source' => $categoryInformation->source,
							);
							foreach ($allSearches as $savedSearch) {
								$categoryResponse['subCategories'][] = [
									'text_id' => $categoryInformation->textId . '_' . $savedSearch['id'],
									'display_label' => $savedSearch['title'],
									'source' => "SavedSearch",
								];
							}
						}
						$formattedCategories[] = $categoryResponse;
					} else {
						$categoryResponse = array(
							'text_id' => $categoryInformation->textId,
							'display_label' => $categoryInformation->label,
							'link' => $configArray['Site']['url'] . '?browseCategory=' . $categoryInformation->textId,
							'source' => $categoryInformation->source,
						);
					}
					if ($includeSubCategories) {
						$subCategories = $categoryInformation->getSubCategories();
						$categoryResponse['subCategories'] = [];
						if (count($subCategories) > 0) {
							foreach ($subCategories as $subCategory) {
								$temp = new BrowseCategory();
								$temp->id = $subCategory->subCategoryId;
								if ($temp->find(true)) {
									if ($temp->isValidForDisplay()) {
										$parent = new BrowseCategory();
										$parent->id = $subCategory->browseCategoryId;
										if ($parent->find(true)){
											$parentLabel = $parent->label;
										}
										if ($parentLabel == $temp->label) {
											$displayLabel = $temp->label;
										} else {
											$displayLabel = $parentLabel.': '.$temp->label;
										}
										$categoryResponse['subCategories'][] = [
											'text_id' => $temp->textId,
											'display_label' => $displayLabel,
											'link' => $configArray['Site']['url'] . '?browseCategory=' . $temp->textId . '&subCategory=' . $temp->textId,
											'source' => $temp->source,
										];
									}
								}
							}
						}
					}
					$formattedCategories[] = $categoryResponse;
				}
			}
		}
		return $formattedCategories;
	}

	function getSubCategories($textId = null){
		$textId = $this->getTextId($textId);
		if (!empty($textId)){
			$activeBrowseCategory = $this->getBrowseCategory($textId);
			if ($activeBrowseCategory != null){
				$subCategories = array();
				/** @var SubBrowseCategories $subCategory */
				foreach ($activeBrowseCategory->getSubCategories() as $subCategory) {
					// Get Needed Info about sub-category
					if($textId == "system_saved_searches") {
						$label = explode('_', $subCategory->id);
						$id = $label[3];
						$temp = new SearchEntry();
						$temp->id = $id;
						if ($temp->find(true)) {
							$subCategories[] = array('label' => $subCategory->label, 'textId' => $temp->id, 'source' => "savedSearch");
						}
					} elseif($textId == "system_user_lists") {
						$label = explode('_', $subCategory->id);
						$id = $label[3];
						$temp = new UserList();
						$temp->id = $id;
						$numListItems = $temp->numValidListItems();
						if ($temp->find(true)) {
							if($numListItems > 0) {
								$subCategories[] = array('label' => $temp->title, 'textId' => $temp->id, 'source' => "userList");
							}
						}
					} else {
						$temp = new BrowseCategory();
						$temp->id = $subCategory->subCategoryId;
						if ($temp->find(true)) {
							if ($temp->isValidForDisplay()) {
								$subCategories[] = array('label' => $temp->label, 'textId' => $temp->textId);
							}
						} else {
							global $logger;
							$logger->log("Did not find subcategory with id {$subCategory->subCategoryId}", Logger::LOG_WARNING);
						}
					}
				}
				return [
					'success' => true,
					'subCategories' => $subCategories
				];
			}else{
				return [
					'success' => false,
					'message' => 'Could not find a category with that text id.'
				];
			}
		}else{
			return [
				'success' => false,
				'message' => 'Please provide the text id to load sub categories for.'
			];
		}
	}

	function getBrowseCategoryInfo(){
		$textId = $this->getTextId();
		if ($textId == null){
			return array('success' => false);
		}
		$response = ['success' => true];
		$response['textId'] = $textId;
		$subCategoryInfo = $this->getSubCategories($textId);
		if ($subCategoryInfo['success']){
			$response['subcategories'] = $subCategoryInfo['subCategories'];
		}else{
			$response['subcategories'] = [];
		}


		$mainCategory = $this->getBrowseCategory($textId);

		if ($mainCategory != null){
			// If this category has subcategories, get the results of a sub-category instead.
			if (!empty($response['subcategories']['subCategories'])) {
				// passed URL variable, or first sub-category
				if (!empty($_REQUEST['subCategoryTextId'])) {
					$subCategoryTextId = $_REQUEST['subCategoryTextId'];
				} else {
					$subCategoryTextId = $response['subcategories'][0]['textId'];
				}
				$response['subCategoryTextId'] = $subCategoryTextId;

				// Set the main category label before we fetch the sub-categories main results
				$response['label']  = translate(['text'=>$mainCategory->label,'isPublicFacing'=>true]);

				$subCategory = $this->getBrowseCategory($subCategoryTextId);
				if ($subCategory != null){
					return [
						'success' => false,
						'message' => 'Could not find the sub category "' . $subCategoryTextId . '"'
					];
				}else{
					$this->getBrowseCategoryResults($subCategory, $response);
				}
			}else{
				$this->getBrowseCategoryResults($mainCategory, $response);
			}
		}else{
			return [
				'success' => false,
				'message' => 'Could not find the main category "' . $textId . '"'
			];
		}

		return $response;
	}

	/**
	 * @param null $textId  Optional Id to set the object's textId to
	 * @return null         Return the object's textId value
	 */
	private function getTextId($textId = null){
		if (!empty($textId)) {
			return $textId;
		} else { // set Id only once
			return isset($_REQUEST['textId']) ? $_REQUEST['textId'] : null;
		}
	}

	/**
	 * @param string $textId
	 * @return BrowseCategory
	 */
	private function getBrowseCategory($textId) {
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		$browseCategory->textId = $textId;
		if ($browseCategory->find(true) && $browseCategory->isValidForDisplay()) {
			return $browseCategory;
		}else{
			return null;
		}
	}

	const ITEMS_PER_PAGE = 24;
	private function getBrowseCategoryResults($browseCategory, &$response){
		if (isset($_REQUEST['pageToLoad']) && is_numeric($_REQUEST['pageToLoad'])) {
			$pageToLoad = (int) $_REQUEST['pageToLoad'];
		}else{
			$pageToLoad = 1;
		}
		$pageSize = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : self::ITEMS_PER_PAGE;
		if ($browseCategory->textId == 'system_recommended_for_you') {
			$this->getSuggestionsBrowseCategoryResults($pageToLoad, $pageSize, $response);
		} elseif($browseCategory->textId == 'system_saved_searches') {
			$this->getSavedSearchBrowseCategoryResults($pageToLoad, $pageSize, $response);
		} elseif($browseCategory->textId == 'system_user_lists') {
			$this->getUserListBrowseCategoryResults($pageToLoad, $pageSize, $response);
		} else {
			if ($browseCategory->source == 'List') {
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$sourceList     = new UserList();
				$sourceList->id = $browseCategory->sourceListId;
				if ($sourceList->find(true)) {
					$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
				} else {
					$records = array();
				}
				$response['searchUrl'] = '/MyAccount/MyList/' . $browseCategory->sourceListId;

				// Search Browse Category //
			} elseif ($browseCategory->source == 'CourseReserve') {
				require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
				$sourceList     = new CourseReserve();
				$sourceList->id = $browseCategory->sourceCourseReserveId;
				if ($sourceList->find(true)) {
					$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
				} else {
					$records = array();
				}
				$response['searchUrl'] = '/CourseReserves/' . $browseCategory->sourceCourseReserveId;

				// Search Browse Category //
			} else {
				$searchObject = SearchObjectFactory::initSearchObject($browseCategory->source);
				$defaultFilterInfo  = $browseCategory->defaultFilter;
				$defaultFilters     = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
				foreach ($defaultFilters as $filter) {
					$searchObject->addFilter(trim($filter));
				}
				//Set Sorting, this is actually slightly mangled from the category to Solr
				$searchObject->setSort($browseCategory->getSolrSort());
				if ($browseCategory->searchTerm != '') {
					$searchObject->setSearchTerm($browseCategory->searchTerm);
				}

				//Get titles for the list
				$searchObject->clearFacets();
				$searchObject->disableSpelling();
				$searchObject->disableLogging();
				$searchObject->setLimit($pageSize);
				$searchObject->setPage($pageToLoad);
				$searchObject->processSearch();

				// Big one - our results
				$records = $searchObject->getResultRecordSet();
				//Remove fields as needed to improve the display.
				foreach ($records as $recordKey => $record) {
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
					$records[$recordKey] = $record;
				}

				$response['searchUrl'] = $searchObject->renderSearchUrl();

				// Shutdown the search object
				$searchObject->close();
			}
			$response['records'] = $records;
			$response['numRecords'] = count($records);
		}
	}

	function getBreadcrumbs() : array
	{
		return [];
	}

	private function getSuggestionsBrowseCategoryResults(int $pageToLoad, int $pageSize, &$response = [])
	{
		if (!UserAccount::isLoggedIn()){
			$response = [
				'success' => false,
				'message' => 'Your session has timed out, please login again to view suggestions'
			];
		}else{
			$response['label'] = translate(['text' => 'Recommended for you', 'isPublicFacing'=>true]);
			$response['searchUrl'] = '/MyAccount/SuggestedTitles';

			require_once ROOT_DIR . '/sys/Suggestions.php';
			$suggestions = Suggestions::getSuggestions(-1, $pageToLoad,$pageSize);
			$records = array();
			foreach ($suggestions as $suggestedItemId => $suggestionData) {
				$record = $suggestionData['titleInfo'];
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
				$records[] = $record;
			}

			$response['records'] = $records;
			$response['numRecords'] = count($suggestions);
		}
		return $response;
	}

	private function getAppSuggestionsBrowseCategoryResults(int $pageToLoad, int $pageSize, &$response = [])
	{
		if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
			return array('success' => false, 'message' => 'The username and password must be provided to load system recommendations.');
		}

		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$user = UserAccount::validateAccount($username, $password);

		if ($user == false) {
			return array('success' => false, 'message' => 'Sorry, we could not find a user with those credentials.');
		}

			$response['label'] = translate(['text' => 'Recommended for you', 'isPublicFacing'=>true]);
			$response['searchUrl'] = '/MyAccount/SuggestedTitles';

			require_once ROOT_DIR . '/sys/Suggestions.php';
			$suggestions = Suggestions::getSuggestions(-1, $pageToLoad,$pageSize, $user);
			$records = array();
			foreach ($suggestions as $suggestedItemId => $suggestionData) {
				$record = $suggestionData['titleInfo'];
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
				$records[] = $record;
			}

			$response['records'] = $records;
			$response['numRecords'] = count($suggestions);

		return $response;
	}

	private function getSavedSearchBrowseCategoryResults(int $pageSize, $id = null, $appUser = null)
	{

		if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
			return array('success' => false, 'message' => 'The username and password must be provided to load saved searches.');
		}

		if($appUser) {
			$user = UserAccount::login();
		} else {
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::validateAccount($username, $password);
		}

		if ($user == false) {
			return array('success' => false, 'message' => 'Sorry, we could not find a user with those credentials.');
		}

		if($id) {
			$label = explode('_', $id);
		} else {
			$label = explode('_', $_REQUEST['id']);
		}
		$id = $label[3];
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listApi = new ListAPI();
		$records = $listApi->getSavedSearchTitles($id, $pageSize);
		$response['items'] = $records;

		return $response;
	}

	private function getUserListBrowseCategoryResults(int $pageToLoad, int $pageSize, $id = null)
	{
		if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
			return array('success' => false, 'message' => 'The username and password must be provided to load lists.');
		}

		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$user = UserAccount::validateAccount($username, $password);

		if ($user == false) {
			return array('success' => false, 'message' => 'Sorry, we could not find a user with those credentials.');
		}

		if($id) {
			$label = explode('_',$id);
		} else {
			$label = explode('_', $_REQUEST['id']);
		}
		$id = $label[3];
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$sourceList = new UserList();
		$sourceList->id = $id;
		if ($sourceList->find(true)) {
			$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
		}
		$response['items'] = $records;

		return $response;
	}

# ****************************************************************************************************************************
# * Functions for Aspen LiDA
# *
# ****************************************************************************************************************************
	/** @noinspection PhpUnused */
	function getBrowseCategoryListForUser() {
		//Figure out which library or location we are looking at
		global $library;
		global $locationSingleton;
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listApi = new ListAPI();

		//Check to see if we have an active location, will be null if we don't have a specific location
		//based off of url, branch parameter, or IP address
		$activeLocation = $locationSingleton->getActiveLocation();

		list($username, $password) = $this->loadUsernameAndPassword();
		$appUser = UserAccount::validateAccount($username, $password);

		/** @var BrowseCategoryGroupEntry[] $browseCategories */
		if ($activeLocation == null){
			$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA(null, $appUser, false);
		}else{
			$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA(null, $appUser, false);
		}
		$formattedCategories = array();
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		foreach ($browseCategories as $curCategory){
			$categoryResponse = [];
			$categoryInformation = new BrowseCategory();
			$categoryInformation->id = $curCategory->browseCategoryId;
			if ($categoryInformation->find(true)) {
				if ($categoryInformation->isValidForDisplay($appUser, false) && ($categoryInformation->source == 'GroupedWork' || $categoryInformation->source == 'List')) {
					if ($categoryInformation->textId == ('system_saved_searches')) {
						$savedSearches = $listApi->getSavedSearches($appUser->id);
						$allSearches = $savedSearches['searches'];
						foreach ($allSearches as $savedSearch) {
							$thisId = $categoryInformation->textId . '_' . $savedSearch['id'];
							$categoryResponse = [
								'key' => $thisId,
								'title' => $categoryInformation->label . ': ' . $savedSearch['title'],
								'source' => 'SavedSearch',
								'sourceId' => $savedSearch['id'],
								'isHidden' => $categoryInformation->isDismissed($appUser),
							];
							$formattedCategories[] = $categoryResponse;
						}
					} elseif ($categoryInformation->textId == ('system_user_lists')) {
						$userLists = $listApi->getUserLists();
						$allUserLists = $userLists['lists'] ?? [];
						if (count($allUserLists) > 0) {
							foreach ($allUserLists as $userList) {
								if ($userList['id'] != 'recommendations') {
									$thisId = $categoryInformation->textId . '_' . $userList['id'];
									$list = new UserList();
									$list->id = $userList['id'];
									if($list->find(true)) {
										$categoryResponse = [
											'key' => $thisId,
											'title' => $categoryInformation->label . ': ' . $list->title,
											'source' => 'List',
											'sourceId' => $list->id,
											'isHidden' => $list->isDismissed(),
										];
										$formattedCategories[] = $categoryResponse;
									}
								}
							}
						}
					} elseif ($categoryInformation->source == 'List' && $categoryInformation->textId != ('system_user_lists') && $categoryInformation->sourceListId != '-1' && $categoryInformation->sourceListId) {
						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'id' => $categoryInformation->id,
							'source' => $categoryInformation->source,
							'listId' => $categoryInformation->sourceListId,
							'isHidden' => $categoryInformation->isDismissed($appUser),
						);
						$formattedCategories[] = $categoryResponse;
					}
					elseif ($categoryInformation->textId == ('system_recommended_for_you')) {
						if (empty($appUser) && UserAccount::isLoggedIn()){
							$appUser = UserAccount::getActiveUserObj();
						}
						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'source' => $categoryInformation->source,
							'isHidden' => $categoryInformation->isDismissed($appUser),
						);
						$formattedCategories[] = $categoryResponse;
					} else {
						$subCategories = $categoryInformation->getSubCategories();
						if (count($subCategories) > 0) {
							foreach ($subCategories as $subCategory) {
								$temp = new BrowseCategory();
								$temp->id = $subCategory->subCategoryId;
								if ($temp->find(true)) {
									if ($temp->isValidForDisplay($appUser, false)) {
										if ($temp->source != '') {
											$parent = new BrowseCategory();
											$parent->id = $subCategory->browseCategoryId;
											if ($parent->find(true)) {
												$parentLabel = $parent->label;
											}
											if ($parentLabel == $temp->label) {
												$displayLabel = $temp->label;
											} else {
												$displayLabel = $parentLabel . ': ' . $temp->label;
											}
											$categoryResponse = [
												'key' => $temp->textId,
												'title' => $displayLabel,
												'source' => $temp->source,
												'isHidden' => $temp->isDismissed($appUser),
											];
											$formattedCategories[] = $categoryResponse;
										}
									}
								}
							}
						} else {
							$categoryResponse = [
								'key' => $categoryInformation->textId,
								'title' => $categoryInformation->label,
								'source' => $categoryInformation->source,
								'isHidden' => $categoryInformation->isDismissed($appUser),
							];
							$formattedCategories[] = $categoryResponse;
						}
					}
				}
			}
		}
		return $formattedCategories;
	}

	/** @noinspection PhpUnused */
	function getAppActiveBrowseCategories(){
		//Figure out which library or location we are looking at
		global $library;
		global $locationSingleton;
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listApi = new ListAPI();

		$includeSubCategories = false;
		if (isset($_REQUEST['includeSubCategories'])){
			$includeSubCategories = ($_REQUEST['includeSubCategories'] == 'true') || ($_REQUEST['includeSubCategories'] == 1);
		}

		// check if we should limit the initial return
		$maxCategories = null;
		if(isset($_REQUEST['maxCategories'])) {
			$maxCategories = $_REQUEST['maxCategories'];
		}

		$isLiDARequest = false;
		if(isset($_REQUEST['LiDARequest'])) {
			$isLiDARequest = $_REQUEST['LiDARequest'];
		}

		//Check to see if we have an active location, will be null if we don't have a specific location
		//based off of url, branch parameter, or IP address
		$activeLocation = $locationSingleton->getActiveLocation();

		list($username, $password) = $this->loadUsernameAndPassword();
		$appUser = UserAccount::validateAccount($username, $password);

		//Get a list of browse categories for that library / location
		/** @var BrowseCategoryGroupEntry[] $browseCategories */
		if ($activeLocation == null){
			//We don't have an active location, look at the library
			if($isLiDARequest) {
				$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA($maxCategories, $appUser);
			} else {
				$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategories();
			}
		}else{
			//We have a location get data for that
			if($isLiDARequest) {
				$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA($maxCategories, $appUser);
			} else {
				$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategories();
			}
		}
		$formattedCategories = array();

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		//Format for return to the user, we want to return
		// - the text id of the category
		// - the display label
		// - Clickable link to load the category
		$numCategoriesProcessed = 0;
		foreach ($browseCategories as $curCategory){
			$categoryResponse = [];
			$categoryInformation = new BrowseCategory();
			$categoryInformation->id = $curCategory->browseCategoryId;

			if ($categoryInformation->find(true)) {
				if ($categoryInformation->isValidForDisplay($appUser) && ($categoryInformation->source == "GroupedWork" || $categoryInformation->source == "List")) {
					if ($categoryInformation->textId == ("system_saved_searches")) {
						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'source' => $categoryInformation->source,
							'isHidden' => false,
						);
						$savedSearches = $listApi->getSavedSearches($appUser->id);
						$allSearches = $savedSearches['searches'];
						$categoryResponse['numNewTitles'] = $savedSearches['countNewResults'];
						$categoryResponse['subCategories'] = [];
						foreach ($allSearches as $savedSearch) {
							$thisId = $categoryInformation->textId . '_' . $savedSearch['id'];
							$savedSearchResults = $this->getAppBrowseCategoryResults($thisId, $appUser, 12);
							$formattedSavedSearchResults = [];
							if(count($savedSearchResults) > 0) {
								foreach ($savedSearchResults as $savedSearchResult) {
									$formattedSavedSearchResults[] = [
										'id' => $savedSearchResult['id'],
										'title_display' => $savedSearchResult['title'],
										'isNew' => $savedSearchResult['isNew'],
									];
								}
							}
							$categoryResponse['subCategories'][] = [
								'key' => $thisId,
								'title' => $categoryInformation->label . ': ' . $savedSearch['title'],
								'source' => "SavedSearch",
								'sourceId' => $savedSearch['id'],
								'isHidden' => false,
								'records' => $formattedSavedSearchResults,
							];
							$numCategoriesProcessed++;
							if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories){
								break;
							}
						}
					} elseif ($categoryInformation->textId == ("system_user_lists")) {
						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'source' => $categoryInformation->source,
							'isHidden' => false,
						);
						$userLists = $listApi->getUserLists();
						$categoryResponse['subCategories'] = [];
						if (isset($userLists['lists'])) {
							$allUserLists = $userLists['lists'];
						}else{
							$allUserLists = [];
						}
						if (count($allUserLists) > 0) {
							foreach ($allUserLists as $userList) {
								if ($userList['id'] != "recommendations") {
									$thisId = $categoryInformation->textId . '_' . $userList['id'];
									$categoryResponse['subCategories'][] = [
										'key' => $thisId,
										'title' => $categoryInformation->label . ': ' . $userList['title'],
										'source' => "List",
										'sourceId' => $userList['id'],
										'isHidden' => false,
										'records' => $this->getAppBrowseCategoryResults($thisId, null, 12),
									];
									$numCategoriesProcessed++;
									if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories){
										break;
									}
								}
							}
						}
					} elseif($categoryInformation->source == "List" && $categoryInformation->textId != ("system_user_lists") && $categoryInformation->sourceListId != "-1" && $categoryInformation->sourceListId) {
						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'id' => $categoryInformation->id,
							'source' => $categoryInformation->source,
							'listId' => $categoryInformation->sourceListId,
							'isHidden' => false,
							'records' => [],
						);

						require_once(ROOT_DIR . '/sys/UserLists/UserList.php');
						require_once(ROOT_DIR . '/sys/UserLists/UserListEntry.php');
						$list = new UserList();
						$list->id = $categoryInformation->sourceListId;
						if($list->find(true)) {
							$listEntry = new UserListEntry();
							$listEntry->listId = $list->id;
							$listEntry->find();
							$count = 0;
							do {
								if($listEntry->source == "Lists") {
									$categoryResponse['lists'][] = array(
										'sourceId' => $listEntry->sourceId,
										'title' => $listEntry->title,
									);
									$count++;
								} else {
									if($listEntry->sourceId) {
										$categoryResponse['records'][] = array(
											'id' => $listEntry->sourceId,
											'title' => $listEntry->title,
										);
										$count++;
									}
								}
							} while ($listEntry->fetch() && $count < 12);
							$numCategoriesProcessed++;
							if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories){
								break;
							}
						}

					} elseif ($categoryInformation->textId == ("system_recommended_for_you")) {
						if (empty($appUser) && UserAccount::isLoggedIn()){
							$appUser = UserAccount::getActiveUserObj();
						}
						require_once(ROOT_DIR . '/sys/Suggestions.php');
						$suggestions = Suggestions::getSuggestions($appUser->id);

						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'source' => $categoryInformation->source,
							'isHidden' => false,
						);

						$categoryResponse['records'] = [];
						if(count($suggestions) > 0) {
							foreach ($suggestions as $suggestion) {
								$categoryResponse['records'][] = [
									'id' => $suggestion['titleInfo']['id'],
									'title_display' => $suggestion['titleInfo']['title_display'],
								];
							}
						}
						$numCategoriesProcessed++;
						if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories){
							break;
						}
					} else {
						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'source' => $categoryInformation->source,
							'isHidden' => false,
							'records' => [],
						);
						$subCategories = $categoryInformation->getSubCategories();
						if (count($subCategories) == 0){
							$categoryResponse['records'] = $this->getAppBrowseCategoryResults($categoryInformation->textId, null, 12);
						}
						if ($includeSubCategories) {
							$categoryResponse['subCategories'] = [];
							if (count($subCategories) > 0) {
								foreach ($subCategories as $subCategory) {
									$temp = new BrowseCategory();
									$temp->id = $subCategory->subCategoryId;
									if ($temp->find(true)) {
										if ($temp->isValidForDisplay($appUser)) {
											if ($temp->source != '') {
												$parent = new BrowseCategory();
												$parent->id = $subCategory->browseCategoryId;
												if ($parent->find(true)) {
													$parentLabel = $parent->label;
												}
												if ($parentLabel == $temp->label) {
													$displayLabel = $temp->label;
												} else {
													$displayLabel = $parentLabel . ': ' . $temp->label;
												}
												$categoryResponse['subCategories'][] = [
													'key' => $temp->textId,
													'title' => $displayLabel,
													'source' => $temp->source,
													'isHidden' => false,
													'records' => $this->getAppBrowseCategoryResults($temp->textId, null, 12)
												];
												$numCategoriesProcessed++;
												if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories){
													break;
												}
											}
										}
									}
									if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories){
										break;
									}
								}
							}
						}
						$numCategoriesProcessed++;
					}
					$formattedCategories[] = $categoryResponse;
					if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories){
						break;
					}
				}
			}
		}
		return $formattedCategories;
	}

	/** @noinspection PhpUnused */
	function getAppBrowseCategoryResults($id = null, $appUser = null, $pageSize = null){
		if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
			$pageToLoad = (int) $_REQUEST['page'];
		}else{
			$pageToLoad = 1;
		}

		if(!$pageSize) {
			$pageSize = $_REQUEST['limit'] ?? self::ITEMS_PER_PAGE;
		}
		if($id) {
			$thisId = $id;
		} else {
			$thisId = $_REQUEST['id'];
		}
		$response = [];

		if(strpos($thisId,"system_saved_searches") !== false) {
			if($id) {
				$result = $this->getSavedSearchBrowseCategoryResults($pageSize, $id, $appUser);
			} else {
				$result = $this->getSavedSearchBrowseCategoryResults($pageSize);
			}
			if(!$id) {$response['key'] = $thisId;}
			if (isset($result['items'])) {
				$response['records'] = $result['items'];
			}else{
				//Error loading items
				$response['records'] = [];
			}
		} elseif(strpos($thisId,"system_user_lists") !== false) {
			if($id) {
				$result = $this->getUserListBrowseCategoryResults($pageToLoad, $pageSize, $id);
			} else {
				$result = $this->getUserListBrowseCategoryResults($pageToLoad, $pageSize);
			}
			if(!$id) {$response['key'] = $thisId;}
			$response['records'] = $result['items'];
		} else {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = $thisId;

			if ($browseCategory->find(true)) {
				if ($browseCategory->textId == 'system_recommended_for_you') {
					$records = $this->getAppSuggestionsBrowseCategoryResults($pageToLoad, $pageSize);
					$response['key'] = $browseCategory->textId;
					$response['records'] = $records['records'];
				} else {
					if ($browseCategory->source == 'List') {
						require_once ROOT_DIR . '/sys/UserLists/UserList.php';
						$sourceList = new UserList();
						$sourceList->id = $browseCategory->sourceListId;
						if ($sourceList->find(true)) {
							$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
						} else {
							$records = array();
						}

						// Search Browse Category //
					} elseif ($browseCategory->source == 'CourseReserve') {
						require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
						$sourceList = new CourseReserve();
						$sourceList->id = $browseCategory->sourceCourseReserveId;
						if ($sourceList->find(true)) {
							$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
						} else {
							$records = array();
						}

						// Search Browse Category //
					} else {
						$searchObject = SearchObjectFactory::initSearchObject($browseCategory->source);
						$defaultFilterInfo = $browseCategory->defaultFilter;
						$defaultFilters = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
						foreach ($defaultFilters as $filter) {
							$searchObject->addFilter(trim($filter));
						}
						//Set Sorting, this is actually slightly mangled from the category to Solr
						$searchObject->setSort($browseCategory->getSolrSort());
						if ($browseCategory->searchTerm != '') {
							$searchObject->setSearchTerm($browseCategory->searchTerm);
						}

						//Get titles for the list
						$searchObject->setFieldsToReturn('id,title_display,author_display,format,language');
						$searchObject->clearFacets();
						$searchObject->disableSpelling();
						$searchObject->disableLogging();
						$searchObject->setLimit($pageSize);
						$searchObject->setPage($pageToLoad);
						$searchObject->processSearch();

						// The results to send to LiDA
						$records = $searchObject->getResultRecordSet();

						// Shutdown the search object
						$searchObject->close();
					}
					if(!$id) {$response['key'] = $browseCategory->textId;}
					$response['records'] = $records;
				}
			} else {
				$response = [
					'success' => false,
					'message' => 'Browse category not found'
				];
			}
		}

		if($id) {
			return $response['records'];
		}

		return $response;
	}

	function getListResults()
	{
		if(!empty($_REQUEST['page'])) {
			$pageToLoad = $_REQUEST['page'];
		} else {
			$pageToLoad = 1;
		}

		if(!empty($_REQUEST['limit'])) {
			$pageSize = $_REQUEST['limit'];
		} else {
			$pageSize = self::ITEMS_PER_PAGE;
		}

		if(!empty($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
		} else {
			return array('success' => false, 'message' => 'List id not provided');
		}

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$sourceList = new UserList();
		$sourceList->id = $id;
		if ($sourceList->find(true)) {
			$response['title'] = $sourceList->title;
			$response['id'] = $sourceList->id;
			$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
		}
		$response['items'] = $records;

		return $response;
	}

	function getSavedSearchResults()
	{
		if(isset($_REQUEST['limit'])) {
			$pageSize = $_REQUEST['limit'];
		} else {
			$pageSize = self::ITEMS_PER_PAGE;
		}

		if(isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
		} else {
			return array('success' => false, 'message' => 'Search id not provided');
		}

		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listApi = new ListAPI();
		$records = $listApi->getSavedSearchTitles($id, $pageSize);

		$response['items'] = $records;

		return $response;
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	private function loadUsernameAndPassword() : array
	{
		if (isset($_REQUEST['username'])) {
			$username = $_REQUEST['username'];
		} else {
			$username = '';
		}
		if (isset($_REQUEST['password'])) {
			$password = $_REQUEST['password'];
		} else {
			$password = '';
		}
		
		// check for post request data
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$username = $_POST['username'];
			$password = $_POST['password'];
		}

		if (is_array($username)) {
			$username = reset($username);
		}
		if (is_array($password)) {
			$password = reset($password);
		}
		return array($username, $password);
	}

	/** @noinspection PhpUnused */
	function getAppSearchResults() : array {
		global $configArray;
		$results['success'] = true;
		$results['message'] = '';
		$searchResults = $this->search();

		$shortname = $_REQUEST['library'];
		$page = $_REQUEST['page'];

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		if (!empty($searchResults['recordSet'])) {
			$results['lookfor'] = $searchResults['lookfor'];
			$results['count'] = count($searchResults['recordSet']);
			$results['totalResults'] = $searchResults['recordCount'];
			$results['categorySelected'] = $searchResults['categorySelected'];
			$results['sortedBy'] = $searchResults['sortedBy'];
			foreach ($searchResults['recordSet'] as $item) {
				$groupedWork = new GroupedWorkDriver($item);
				$author = $item['author_display'];

				$ccode = '';
				if (isset($item['collection_' . $shortname][0])) {
					$ccode = $item['collection_' . $shortname][0];
				}

				$format = '';
				if (isset($item['format_' . $shortname][0])) {
					$format = $item['format_' . $shortname][0];
				}
				$iconName = $configArray['Site']['url']  . "/bookcover.php?id=" . $item['id'] . "&size=medium&type=grouped_work";
				$id = $item['id'];
				if($ccode != '') {
					$format = $format . ' - ' . $ccode;
				} else {
					$format = $format;
				}

				$summary = utf8_encode(trim(strip_tags($item['display_description'])));
				$summary = str_replace('&#8211;', ' - ', $summary);
				$summary = str_replace('&#8212;', ' - ', $summary);
				$summary = str_replace('&#160;', ' ', $summary);
				if (empty($summary)) {
					$summary = 'There is no summary available for this title';
				}

				$title = ucwords($item['title_display']);
				unset($itemList);

				$relatedRecords = $groupedWork->getRelatedRecords();

				$language = "";

				foreach ($relatedRecords as $relatedRecord) {
					$language = $relatedRecord->language;
					if (!isset($itemList)) {
						$itemList[] = array('id' => $relatedRecord->id, 'name' => $relatedRecord->format, 'source' => $relatedRecord->source);
					} elseif (!in_array($relatedRecord->format, array_column($itemList, 'name'))) {
						$itemList[] = array('id' => $relatedRecord->id, 'name' => $relatedRecord->format, 'source' => $relatedRecord->source);
					}
				}

				if (!empty($itemList)) {
					$results['items'][] = array('title' => trim($title), 'author' => $author, 'image' => $iconName, 'format' => $format, 'itemList' => $itemList, 'key' => $id, 'summary' => $summary, 'language' => $language);
				}

				$results['sortList'] = $searchResults['sortList'];
				$results['facetSet'] = $searchResults['facetSet'];
				$results['paging'] = $searchResults['paging'];
			}
		}

		if (empty($results['items'])) {
			$results['items'] = [];
			$results['count'] = 0;
			if($page == 1) {
				$results['message'] = "No search results found";
			} else {
				$results['message'] = "End of results";
			}
		}

		return $results;
	}

	/** @noinspection PhpUnused */
	function searchLite() {
		global $timer;
		global $configArray;

		$results = [
			'success' => false,
			'count' => 0,
			'totalResults' => 0,
			'lookfor' => $_REQUEST['lookfor'],
			'title' => translate(['text' => 'No Results Found', 'isPublicFacing' => true]),
			'items' => [],
			'message' => translate(['text'=> "Your search '%1%' did not match any resources.", 1=>$_REQUEST['lookfor'], 'isPublicFacing'=>true])
		];

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$timer->logTime('Include search engine');

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		if (isset($_REQUEST['pageSize']) && is_numeric($_REQUEST['pageSize'])){
			$searchObject->setLimit($_REQUEST['pageSize']);
		}
		$searchObject->setFieldsToReturn('id,title_display,author_display,language,display_description,format');
		$timer->logTime('Setup Search');

		// Process Search
		$searchResults = $searchObject->processSearch(false, true);
		$timer->logTime('Process Search');

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();

		if ($searchObject->getResultTotal() < 1) {
			// No record found
			$timer->logTime('no hits processing');
		} else {
			$timer->logTime('save search');
			$summary = $searchObject->getResultSummary();
			$results['id'] = $searchObject->getSearchId();
			$results['lookfor'] = $searchObject->displayQuery();
			$results['sort'] = $searchObject->getSort();
			// Process Paging
			$link = $searchObject->renderLinkPageTemplate();
			$options = array('totalItems' => $summary['resultTotal'],
				'fileName' => $link,
				'perPage' => $summary['perPage']);
			$pager = new Pager($options);
			$results['totalResults'] = $pager->getTotalItems();
			$results['count'] = $summary['resultTotal'];
			$results['page_current'] = $pager->getCurrentPage();
			$results['page_total'] = $pager->getTotalPages();
			$timer->logTime('finish hits processing');
			$records = $searchObject->getResultRecordSet();
			$items = [];
			foreach ($records as $recordKey => $record) {
				$items[$recordKey]['key'] = $record['id'];
				$items[$recordKey]['title'] = $record['title_display'];
				$items[$recordKey]['author'] = $record['author_display'];
				$items[$recordKey]['image'] = $configArray['Site']['url'] . "/bookcover.php?id=" . $record['id'] . "&size=medium&type=grouped_work";
				$items[$recordKey]['language'] = $record['language'][0];
				$items[$recordKey]['summary'] = $record['display_description'];
				$i = 0;
				foreach($record['format'] as $format) {
					$items[$recordKey]['itemList'][$i]['key'] = $i;
					$items[$recordKey]['itemList'][$i]['name'] = $format;
					$i++;
				}
			}
			$results['items'] = $items;
			$results['success'] = true;
			$results['time'] = round($searchObject->getTotalSpeed(), 2);
			$results['title'] = translate(['text' => 'Catalog Search', 'isPublicFacing' => true]);
			$results['message'] = translate(['text'=> "Your search '%1%' returned %2% results", 1=>$_REQUEST['lookfor'], 2=>$results['count'], 'isPublicFacing'=>true]);
			$timer->logTime('load result records');
			if($results['page_current'] == $results['page_total']) {
				$results['message'] = "end of results";
			}
		}
		if(empty($results['items'])) {
			if($_REQUEST['page'] != 1) {
				$results['message'] = "end of results";
			}
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	private function restoreSearch($id) {
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$search = new SearchEntry();
		$search->id = $id;
		if($search->find(true)) {
			$minSO = unserialize($search->search_object);
			$storedSearch = SearchObjectFactory::deminify($minSO, $search);
			$searchObj = $storedSearch->restoreSavedSearch($id, false, true);
			if($searchObj) {
				$searchObj->processSearch(false, true);
				return $searchObj;
			}
		}
		return false;
	}

	/** @noinspection PhpUnused */
	function getSortList() {
		$results = [
			'success' => false,
			'message' => '',
		];
		if(empty($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'A valid search id not provided');
		}
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$id = $_REQUEST['id'];
		$search = new SearchEntry();
		$search->id = $id;
		if($search->find(true)) {
			$minSO = unserialize($search->search_object);
			$searchObj = SearchObjectFactory::deminify($minSO, $search);
			$sortList = $searchObj->getSortList();
			$items = [];
			$i = 0;
			$key = translate(['text'=> 'Sort By' , 'isPublicFacing'=>true]);
			$items['key'] = 0;
			$items['label'] = $key;
			$items['field'] = 'sort_by';
			$items['hasApplied'] = true;
			$items['multiSelect'] = false;
			foreach($sortList as $value => $sort) {
				$items['facets'][$i]['value'] = $value;
				$items['facets'][$i]['display'] = translate(['text'=>$sort['desc'], 'isPublicFacing'=>true]);
				$items['facets'][$i]['field'] = 'sort_by';
				$items['facets'][$i]['count'] = 0;
				$items['facets'][$i]['isApplied'] = $sort['selected'];
				$items['facets'][$i]['multiSelect'] = false;
				$i++;
			}
			$results = [
				'success' => true,
				'id' => $id,
				'time' => round($searchObj->getQuerySpeed(), 2),
				'data' => $items,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getFormatCategories() {
		$results = [
			'success' => false,
			'message' => '',
		];
		if(empty($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'A valid search id not provided');
		}
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$id = $_REQUEST['id'];
		$searchObj = $this->restoreSearch($id);
		if($searchObj) {
			global $interface;
			$topFacetSet = $interface->getVariable('topFacetSet');
			$formatCategories = $topFacetSet['format_category'];
			$items = [];
			$i = 0;
			$items['key'] = 0;
			$items['label'] = $formatCategories['label'];
			$items['field'] = $formatCategories['field_name'];
			$items['hasApplied'] = $formatCategories['hasApplied'];
			$items['multiSelect'] = (bool)$formatCategories['multiSelect'];
			foreach($formatCategories['list'] as $category) {
				$items['facets'][$i]['value'] = $category['value'];
				$items['facets'][$i]['display'] = $category['display'];
				$items['facets'][$i]['field'] = $formatCategories['field_name'];
				$items['facets'][$i]['count'] = $category['count'];
				$items['facets'][$i]['isApplied'] = $category['isApplied'];
				$items['facets'][$i]['multiSelect'] = (bool)$formatCategories['multiSelect'];
				$i++;
			}
			$results = [
				'success' => true,
				'id' => $id,
				'time' => round($searchObj->getQuerySpeed(), 2),
				'data' => $items,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getAvailableFacets() {
		$results = [
			'success' => false,
			'message' => 'Unable to restore search from id',
		];
		if(empty($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'A valid search id not provided');
		}
		$includeSortList = $_REQUEST['includeSortList'] ?? true;
		$id = $_REQUEST['id'];
		$searchObj = $this->restoreSearch($id);
		if($searchObj) {
			global $interface;
			$topFacetSet = $interface->getVariable('topFacetSet');
			$facets = $interface->getVariable('sideFacetSet');
			//$facets = $searchObj->getFacetList();
			$items = [];
			$index = 0;
			if($includeSortList) {
				$sortList = $searchObj->getSortList();
				$i = 0;
				$key = translate(['text'=> 'Sort By' , 'isPublicFacing'=>true]);
				$items[$key]['key'] = 0;
				$items[$key]['label'] = $key;
				$items[$key]['field'] = 'sort_by';
				$items[$key]['hasApplied'] = true;
				$items[$key]['multiSelect'] = false;
				foreach($sortList as $value => $sort) {
					$items[$key]['facets'][$i]['value'] = $value;
					$items[$key]['facets'][$i]['display'] = translate(['text'=>$sort['desc'], 'isPublicFacing'=>true]);
					$items[$key]['facets'][$i]['field'] = 'sort_by';
					$items[$key]['facets'][$i]['count'] = 0;
					$items[$key]['facets'][$i]['isApplied'] = $sort['selected'];
					$items[$key]['facets'][$i]['multiSelect'] = false;
					$i++;
				}
			}
			foreach($facets as $facet) {
				$index++;
				$i = 0;
				if($facet['field_name'] == 'availability_toggle') {
					$availabilityToggle = $topFacetSet['availability_toggle'];
					$key = $availabilityToggle['label'];
					$items[$key]['key'] = $index;
					$items[$key]['label'] = $key;
					$items[$key]['field'] = $availabilityToggle['field_name'];
					$items[$key]['hasApplied'] = $availabilityToggle['hasApplied'];
					$items[$key]['multiSelect'] = (bool)$availabilityToggle['multiSelect'];
					foreach($availabilityToggle['list'] as $item) {
						$items[$key]['facets'][$i]['value'] = $item['value'];
						$items[$key]['facets'][$i]['display'] = $item['display'];
						$items[$key]['facets'][$i]['field'] = $availabilityToggle['field_name'];
						$items[$key]['facets'][$i]['count'] = $item['count'];
						$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
						if(isset($item['multiSelect'])) {
							$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
						} else {
							$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
						}
						$i++;
					}
				} else {
					$key = $facet['label'];
					$items[$key]['key'] = $index;
					$items[$key]['label'] = $key;
					$items[$key]['field'] = $facet['field_name'];
					$items[$key]['hasApplied'] = $facet['hasApplied'];
					$items[$key]['multiSelect'] = (bool)$facet['multiSelect'];
					if(isset($facet['sortedList'])) {
						foreach($facet['sortedList'] as $item) {
							$items[$key]['facets'][$i]['value'] = $item['value'];
							$items[$key]['facets'][$i]['display'] = $item['display'];
							$items[$key]['facets'][$i]['field'] = $facet['field_name'];
							$items[$key]['facets'][$i]['count'] = $item['count'];
							$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
							if(isset($item['multiSelect'])) {
								$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
							} else {
								$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
							}
							$i++;
						}
					} else {
						foreach($facet['list'] as $item) {
							$items[$key]['facets'][$i]['value'] = $item['value'];
							$items[$key]['facets'][$i]['display'] = $item['display'];
							$items[$key]['facets'][$i]['field'] = $facet['field_name'];
							$items[$key]['facets'][$i]['count'] = $item['count'];
							$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
							if(isset($item['multiSelect'])) {
								$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
							} else {
								$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
							}
							$i++;
						}
					}
				}
			}
			$results = [
				'success' => true,
				'id' => $id,
				'time' => round($searchObj->getQuerySpeed(), 2),
				'data' => $items,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getAvailableFacetsKeys() {
		$results = [
			'success' => false,
			'message' => 'Unable to restore search from id',
		];
		if(empty($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'A valid search id not provided');
		}
		$includeSort = $_REQUEST['includeSort'] ?? true;
		$id = $_REQUEST['id'];
		$searchObj = $this->restoreSearch($id);
		if($searchObj) {
			global $interface;
			$facets = $interface->getVariable('sideFacetSet');
			//$facets = $searchObj->getFacetList();
			$items = array_keys($facets);
			if($includeSort) {
				$items[] = 'sort_by';
			}
			$results = [
				'success' => true,
				'id' => $id,
				'time' => round($searchObj->getQuerySpeed(), 2),
				'options' => $items,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getAppliedFilters() {
		$results = [
			'success' => false,
			'message' => 'Unable to restore search from id',
		];
		if(empty($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'A valid search id not provided');
		}
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$id = $_REQUEST['id'];
		$search = new SearchEntry();
		$search->id = $id;
		if($search->find(true)) {
			$minSO = unserialize($search->search_object);
			$searchObj = SearchObjectFactory::deminify($minSO, $search);
			$filters = $searchObj->getFilterList();
			$items = [];

			$includeSort = $_REQUEST['includeSort'] ?? true;
			if($includeSort) {
				$list = $searchObj->getSortList();
				$sort = [];
				foreach($list as $index => $item) {
					if($item['selected'] == true){
						$sort = $item;
						$sort['value'] = $index;
						break;
					}
				}
				$i = 0;
				$key = translate(['text'=> 'Sort By' , 'isPublicFacing'=>true]);
				$items[$key][$i]['value'] = $sort['value'];
				$items[$key][$i]['display'] = $sort['desc'];
				$items[$key][$i]['field'] = 'sort_by';
				$items[$key][$i]['count'] = 0;
				$items[$key][$i]['isApplied'] = true;
			}

			foreach($filters as $key => $filter) {
				$i = 0;
				foreach($filter as $item) {
					$items[$key][$i]['value'] = $item['value'];
					$items[$key][$i]['display'] = $item['display'];
					$items[$key][$i]['field'] = $item['field'];
					$items[$key][$i]['count'] = 0;
					$items[$key][$i]['isApplied'] = true;
					$i++;
				}
			}
			$results = [
				'success' => true,
				'id' => $id,
				'time' => round($searchObj->getQuerySpeed(), 2),
				'data' => $items,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getDefaultFacets() {
		$limit = $_REQUEST['limit'] ?? 5;
		$searchObj = SearchObjectFactory::initSearchObject();
		$searchObj->init();
		$obj = $searchObj->getFacetConfig();
		$searchObj->close();
		$obj = array_slice($obj, 0, $limit);
		$facets = [];
		$i = 0;
		foreach($obj as $facet) {
			$facets[$i]['value'] = $facet->facetName;
			$facets[$i]['display'] = $facet->displayName;
			$facets[$i]['field'] = $facet->facetName;
			$facets[$i]['count'] = 0;
			$facets[$i]['isApplied'] = false;
			$facets[$i]['multiSelect'] = (bool)$facet->multiSelect;
			$i++;
		}
		return [
			'success' => true,
			'limit' => $limit,
			'time' => round($searchObj->getQuerySpeed(), 2),
			'data' => $facets,
		];
	}

	/** @noinspection PhpUnused */
	function getFacetClusterByKey() {
		$results = [
			'success' => false,
			'message' => 'Unable to restore search from id',
		];
		if(empty($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'A valid search id not provided');
		}
		if(empty($_REQUEST['cluster'])) {
			return array('success' => false, 'message' => 'A valid cluster field_name not provided');
		}
		$id = $_REQUEST['id'];
		$key = $_REQUEST['cluster'];
		$searchObj = $this->restoreSearch($id);
		if($searchObj) {
			$facets = $searchObj->getFacetList();
			$cluster = $facets[$key] ?? [];
			$results = [
				'success' => true,
				'id' => $id,
				'time' => round($searchObj->getQuerySpeed(), 2),
				'field' => $cluster['field_name'],
				'display' => $cluster['label'],
				'hasApplied' => $cluster['hasApplied'],
				'multiSelect' => (bool)$cluster['multiSelect'],
				'options' => $cluster['list'],
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	// placeholder for fetching more facets when searching thru large (>100) clusters
	function searchFacetCluster() {
		$results = [
			'success' => false,
			'message' => 'Unable to restore search from id',
		];
		if(empty($_REQUEST['id'])) {
			return array('success' => false, 'message' => 'A valid search id not provided');
		}
		$id = $_REQUEST['id'];
		$term = $_REQUEST['term'];
		$searchObj = $this->restoreSearch($id);
		if($searchObj) {
			// do something with the term
		}
		return $results;
	}
}