<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Pager.php';

class SearchAPI extends Action
{

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$output = '';

		//Make sure the user can access the API based on the IP address or if AUTH_USER is not set
		if ((!IPAddress::allowAPIAccessForClientIP()) || !isset($_SERVER['PHP_AUTH_USER'])) {
			$this->forbidAPIAccess();
		}

		//Check if user can access API with keys sent from LiDA
		if (isset($_SERVER['PHP_AUTH_USER']) && $this->grantTokenAccess()) {
			if (in_array($method, array('getAppBrowseCategoryResults', 'getAppActiveBrowseCategories'))) {
				$result = [
					'result' => $this->$method()
				];
				$output = json_encode($result);
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('SystemAPI', $method);
			} else {
				$output = json_encode(array('error' => 'invalid_method'));
			}
		} elseif (!(isset($_SERVER['PHP_AUTH_USER'])) || !$this->grantTokenAccess()) {
			header('HTTP/1.0 401 Unauthorized');
			$output = json_encode(array('error' => 'unauthorized_access'));
		}

		if (IPAddress::allowAPIAccessForClientIP()) {
			if (!empty($method) && method_exists($this, $method)) {
				if (in_array($method, array('getListWidget', 'getCollectionSpotlight'))) {
					$output = $this->$method();
				} else {
					$jsonOutput = json_encode(array('result' => $this->$method()));
				}
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('SearchAPI', $method);
			} else {
				$jsonOutput = json_encode(array('error' => 'invalid_method'));
			}
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
		require_once ROOT_DIR . '/services/API/SystemAPI.php';
		global $configArray;
		$checks = [];
		$serverStats = [];
		$systemApi = new SystemAPI();

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
				if (empty($logEntry->endTime) && date('H') >= 8){
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

	private function getSavedSearchBrowseCategoryResults(int $pageSize)
	{

			if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
				return array('success' => false, 'message' => 'The username and password must be provided to load saved searches.');
			}

			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::validateAccount($username, $password);

			if ($user == false) {
				return array('success' => false, 'message' => 'Sorry, we could not find a user with those credentials.');
			}

			$label = explode('_', $_REQUEST['id']);
			$id = $label[3];
			require_once ROOT_DIR . '/services/API/ListAPI.php';
			$listApi = new ListAPI();
			$records = $listApi->getSavedSearchTitles($id, $pageSize);
			$response['items'] = $records;

		return $response;
	}

	private function getUserListBrowseCategoryResults(int $pageToLoad, int $pageSize)
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

		$label = explode('_', $_REQUEST['id']);
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
		//Check to see if we have an active location, will be null if we don't have a specific location
		//based off of url, branch parameter, or IP address
		$activeLocation = $locationSingleton->getActiveLocation();

		list($username, $password) = $this->loadUsernameAndPassword();
		$appUser = UserAccount::validateAccount($username, $password);

		//Get a list of browse categories for that library / location
		/** @var BrowseCategoryGroupEntry[] $browseCategories */
		if ($activeLocation == null){
			//We don't have an active location, look at the library
			$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategories();
		}else{
			//We have a location get data for that
			$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategories();
		}
		$formattedCategories = array();

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		//Format for return to the user, we want to return
		// - the text id of the category
		// - the display label
		// - Clickable link to load the category
		foreach ($browseCategories as $curCategory){
			$categoryInformation = new BrowseCategory();
			$categoryInformation->id = $curCategory->browseCategoryId;

			if ($categoryInformation->find(true)) {
				if ($categoryInformation->isValidForDisplay($appUser)) {
					if ($categoryInformation->textId == ("system_saved_searches")) {
						$savedSearches = $listApi->getSavedSearches();
						$allSearches = $savedSearches['searches'];
						$categoryResponse['subCategories'] = [];
						foreach ($allSearches as $savedSearch) {
							$categoryResponse['subCategories'][] = [
								'key' => $categoryInformation->textId . '_' . $savedSearch['id'],
								'title' => $categoryInformation->label . ': ' . $savedSearch['title'],
								'source' => "SavedSearch",
							];
						}
					} elseif ($categoryInformation->textId == ("system_user_lists")) {
						$userLists = $listApi->getUserLists();
						$categoryResponse['subCategories'] = [];
						$allUserLists = $userLists['lists'];
						if (count($allUserLists) > 0) {
							foreach ($allUserLists as $userList) {
								if ($userList['id'] != "recommendations") {
									$categoryResponse['subCategories'][] = [
										'key' => $categoryInformation->textId . '_' . $userList['id'],
										'title' => $categoryInformation->label . ': ' . $userList['title'],
										'source' => "List",
									];
								}
							}
						}
					} else {
						$categoryResponse = array(
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'source' => $categoryInformation->source,
						);
						if ($includeSubCategories) {
							$subCategories = $categoryInformation->getSubCategories();
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
												];
											}
										}
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

	/** @noinspection PhpUnused */
	function getAppBrowseCategoryResults(){
		if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
			$pageToLoad = (int) $_REQUEST['page'];
		}else{
			$pageToLoad = 1;
		}
		$pageSize = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : self::ITEMS_PER_PAGE;
		$thisId = $_REQUEST['id'];
		$response = [];

		if(strpos($thisId,"system_saved_searches") !== false) {
			$result = $this->getSavedSearchBrowseCategoryResults($pageSize);
			$response['key'] = $thisId;
			$response['records'] = $result['items'];
		} elseif(strpos($thisId,"system_user_lists") !== false) {
			$result = $this->getUserListBrowseCategoryResults($pageToLoad, $pageSize);
			$response['key'] = $thisId;
			$response['records'] = $result['items'];
		} else {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = $thisId;

			if ($browseCategory->find(true)) {
				if ($browseCategory->textId == 'system_recommended_for_you') {
					$this->getSuggestionsBrowseCategoryResults($pageToLoad, $pageSize);
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
						$searchObject->setFieldsToReturn('id,title_display');
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
					$response['key'] = $browseCategory->textId;
					$response['records'] = $records;
				}
			} else {
				$response = [
					'success' => false,
					'message' => 'Browse category not found'
				];
			}
		}

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
		if (is_array($username)) {
			$username = reset($username);
		}
		if (is_array($password)) {
			$password = reset($password);
		}
		return array($username, $password);
	}
}
