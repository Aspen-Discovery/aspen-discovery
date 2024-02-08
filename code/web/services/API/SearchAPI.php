<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Pager.php';

class SearchAPI extends Action {

	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$output = '';

		//Set Headers
		header('Content-type: application/json');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		//Check if user can access API with keys sent from LiDA
		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'getAppBrowseCategoryResults',
					'getAppActiveBrowseCategories',
					'getAppSearchResults',
					'getListResults',
					'getSavedSearchResults',
					'getSortList',
					'getAppliedFilters',
					'getAvailableFacets',
					'getAvailableFacetsKeys',
					'searchLite',
					'getDefaultFacets',
					'getFacetClusterByKey',
					'searchFacetCluster',
					'getFormatCategories',
					'getBrowseCategoryListForUser',
					'searchAvailableFacets',
					'getSearchSources',
					'getSearchIndexes'
				])) {
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('SearchAPI', $method);
					$jsonOutput = json_encode(['result' => $this->$method()]);
				} else {
					$output = json_encode(['error' => 'invalid_method']);
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(['error' => 'unauthorized_access']);
			}
			ExternalRequestLogEntry::logRequest('SearchAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], isset($jsonOutput) ? $jsonOutput : $output, []);
			echo isset($jsonOutput) ? $jsonOutput : $output;
		} elseif (IPAddress::allowAPIAccessForClientIP() || in_array($method, [
				'getListWidget',
				'getCollectionSpotlight',
			])) {
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			if (!empty($method) && method_exists($this, $method)) {
				if (in_array($method, [
					'getListWidget',
					'getCollectionSpotlight',
				])) {
					header('Content-type: text/html');
					$output = $this->$method();
				} else {
					$jsonOutput = json_encode(['result' => $this->$method()]);
				}
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('SearchAPI', $method);
				echo isset($jsonOutput) ? $jsonOutput : $output;
			} else {
				echo json_encode(['error' => 'invalid_method']);
			}
		} else {
			$this->forbidAPIAccess();
		}
	}

	// The time intervals in seconds beyond which we consider the status as not current
	const
		STATUS_OK = 'okay', STATUS_WARN = 'warning', STATUS_CRITICAL = 'critical';


	function getIndexStatus() {
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
		} else {
			$this->addCheck($checks, 'Solr');
		}

		//Check for a current backup
		global $serverName;
		$backupDir = "/data/aspen-discovery/{$serverName}/sql_backup/";
		$lastBackupSize = 0;
		if (!file_exists($backupDir)) {
			$this->addCheck($checks, 'Backup', self::STATUS_CRITICAL, "Backup directory $backupDir does not exist");
		} else {
			$backupFiles = scandir($backupDir);
			$backupFileFound = false;
			$backupFileTooSmall = false;
			foreach ($backupFiles as $backupFile) {
				if (preg_match('/.*\.tar\.gz/', $backupFile) || preg_match('/.*\.sql\.gz/', $backupFile)) {
					$fileCreationTime = filectime($backupDir . $backupFile);
					if ((time() - $fileCreationTime) < (24.5 * 60 * 60)) {
						$fileSize = filesize($backupDir . $backupFile);
						if ($fileSize > 1000) {
							//We have a backup file created in the last 24.5 hours (30 min buffer to give time for the backup to be created)
							$backupFileFound = true;
							$lastBackupSize = $fileSize;
						} else {
							$backupFileFound = true;
							$backupFileTooSmall = true;
						}
					}
				}
			}
			if (!$backupFileFound) {
				$this->addCheck($checks, 'Backup', self::STATUS_CRITICAL, "A current backup of Aspen was not found in $backupDir.  Check my.cnf to be sure mysqldump credentials exist.");
			} else {
				if ($backupFileTooSmall) {
					$this->addCheck($checks, 'Backup', self::STATUS_CRITICAL, "The backup for Aspen was found, but is too small.  Check my.cnf to be sure mysqldump credentials exist.");
				} else {
					$this->addCheck($checks, 'Backup');
				}
			}
		}

		//Check for encryption key
		$hasKeyFile = $systemApi->doesKeyFileExist();
		if ($hasKeyFile) {
			$this->addCheck($checks, 'Encryption Key');
		} else {
			$this->addCheck($checks, 'Encryption Key', self::STATUS_CRITICAL, "The encryption key does not exist.");
		}

		$hasPendingUpdates = $systemApi->hasPendingDatabaseUpdates();
		if ($hasPendingUpdates) {
			$this->addCheck($checks, 'Pending Database Updates', self::STATUS_CRITICAL, "There are pending database updates.");
		} else {
			$this->addCheck($checks, 'Pending Database Updates');
		}

		//Check free disk space
		if (is_dir('/data')) {
			$freeSpace = disk_free_space('/data');
			$this->addServerStat($serverStats, 'Data Disk Space', StringUtils::formatBytes($freeSpace));
			$backupSizeCriticalLevel = 2.5 * $lastBackupSize;
			$backupSizeWarningLevel = 5 * $lastBackupSize;
			$dataSizeCritical = false;
			$dataSizeWarning = false;
			if ($backupSizeCriticalLevel > 7500000000) {
				if ($freeSpace < $backupSizeCriticalLevel) {
					$this->addCheck($checks, 'Data Disk Space', self::STATUS_CRITICAL, "The data drive currently has less than 2.5x the size of the last backup available");
					$dataSizeCritical = true;
				}
			}else{
				if ($freeSpace < 7500000000) {
					$this->addCheck($checks, 'Data Disk Space', self::STATUS_CRITICAL, "The data drive currently has less than 7.5GB of space available");
					$dataSizeCritical = true;
				}
			}
			if (!$dataSizeCritical) {
				if ($backupSizeWarningLevel > 10000000000) {
					if ($freeSpace < $backupSizeWarningLevel) {
						$this->addCheck($checks, 'Data Disk Space', self::STATUS_WARN, "The data drive currently has less than 5x the size of the last backup available");
						$dataSizeWarning = true;
					}
				}else{
					if ($freeSpace < 10000000000) {
						$this->addCheck($checks, 'Data Disk Space', self::STATUS_WARN, "The data drive currently has less than 10GB of space available");
						$dataSizeWarning = true;
					}
				}
			}

			if (!$dataSizeWarning && !$dataSizeCritical) {
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
		if ($configArray['System']['operatingSystem'] == 'linux') {
			$fh = fopen('/proc/meminfo', 'r');
			$freeMem = 0;
			$totalMem = 0;
			while ($line = fgets($fh)) {
				$pieces = [];
				if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) {
					$totalMem = $pieces[1] * 1024;
				} else {
					if (preg_match('/^MemAvailable:\s+(\d+)\skB$/', $line, $pieces)) {
						$freeMem = $pieces[1] * 1024;
					}
				}
			}
			$this->addServerStat($serverStats, 'Total Memory', StringUtils::formatBytes($totalMem));
			$this->addServerStat($serverStats, 'Available Memory', StringUtils::formatBytes($freeMem));
			$percentMemoryUsage = round((1 - ($freeMem / $totalMem)) * 100, 1);
			$this->addServerStat($serverStats, 'Percent Memory In Use', $percentMemoryUsage);
			if ($freeMem < 1000000000) {
				$this->addCheck($checks, 'Memory Usage', self::STATUS_CRITICAL, "Less than 1GB ($freeMem) of available memory exists, increase available resources");
			} elseif ($percentMemoryUsage > 95 && $freeMem < 2500000000) {
				$this->addCheck($checks, 'Memory Usage', self::STATUS_CRITICAL, "{$percentMemoryUsage}% of total memory is in use, increase available resources");
			} else {
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
			if ($load[1] > $numCPUs * 2.5) {
				if ($load[0] >= $load[1]) {
					$this->addCheck($checks, 'Load Average', self::STATUS_CRITICAL, "Load is very high {$load[1]} and is increasing");
				} else {
					$this->addCheck($checks, 'Load Average', self::STATUS_WARN, "Load is very high {$load[1]}, but it is decreasing");
				}
			} elseif ($load[1] > $numCPUs * 1.5) {
				if ($load[0] >= $load[1]) {
					$this->addCheck($checks, 'Load Average', self::STATUS_WARN, "Load is high {$load[1]} and is increasing");
				} else {
					$this->addCheck($checks, 'Load Average', self::STATUS_WARN, "Load is high {$load[1]}, but it is decreasing");
				}
			} else {
				$this->addCheck($checks, 'Load Average');
			}

			//Check wait time
			$topInfo = shell_exec("top -n 1 -b | grep %Cpu");
			if (preg_match('/(\d+\.\d+) wa,/', $topInfo, $matches)) {
				$waitTime = $matches[1];
				$this->addServerStat($serverStats, 'Wait Time', $waitTime);
				if ($waitTime > 15) {
					$this->addCheck($checks, 'Wait Time', self::STATUS_WARN, "Wait time is over 15 $waitTime");
				} elseif ($waitTime > 30) {
					$this->addCheck($checks, 'Wait Time', self::STATUS_CRITICAL, "Wait time is over 30 $waitTime");
				} else {
					$this->addCheck($checks, 'Wait Time');
				}
			} else {
				$this->addCheck($checks, 'Wait Time', self::STATUS_CRITICAL, "Wait time not found in $topInfo");
			}
		}

		//Check nightly index
		require_once ROOT_DIR . '/sys/Indexing/ReindexLogEntry.php';
		$logEntry = new ReindexLogEntry();
		$logEntry->orderBy("id DESC");
		$logEntry->limit(0, 1);
		if ($logEntry->find(true)) {
			if ($logEntry->numErrors > 0) {
				$this->addCheck($checks, 'Nightly Index', self::STATUS_CRITICAL, 'The last nightly index had errors');
			} else {
				//Check to see if it's after 8 am and the nightly index is still running.
				if (empty($logEntry->endTime) && date('H') >= 8 && date('H') < 21) {
					$this->addCheck($checks, 'Nightly Index', self::STATUS_CRITICAL, "Nightly index is still running after 8 am.");
				} else {
					$this->addCheck($checks, 'Nightly Index');
				}
			}
		} else {
			$this->addCheck($checks, 'Nightly Index', self::STATUS_CRITICAL, 'Nightly index has never run');
		}

		//Check for errors within the logs
		require_once ROOT_DIR . '/sys/Module.php';
		$aspenModule = new Module();
		$aspenModule->enabled = true;
		$aspenModule->find();
		while ($aspenModule->fetch()) {
			if ($aspenModule->name == 'Open Archives') {
				require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesCollection.php';
				$oaiSettings = new OpenArchivesCollection();
				$oaiSettings->deleted = false;
				$allOaiSettings = $oaiSettings->fetchAll();
				$hasErrors = false;
				$oaiNote = '';
				/** @var OpenArchivesCollection $oaiSetting */
				foreach ($allOaiSettings as $oaiSetting) {
					require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesExportLogEntry.php';
					$websiteIndexingEntry = new OpenArchivesExportLogEntry();
					$websiteIndexingEntry->collectionName = $oaiSetting->name;
					$websiteIndexingEntry->orderBy("id DESC");
					$websiteIndexingEntry->find();
					if ($websiteIndexingEntry->getNumResults() > 0) {
						$websiteIndexingEntry->fetch();
						if ($websiteIndexingEntry->numErrors > 0) {
							$oaiNote .= $oaiSetting->name . ' had an error on the last run<br/>';
						}
					}else{
						$hasErrors = true;
						$oaiNote .= $oaiSetting->name . ' has never been indexed<br/>';
					}
				}
				if (!$hasErrors) {
					$this->addCheck($checks, $aspenModule->name);
				}else{
					$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, $oaiNote);
				}
			} elseif ($aspenModule->name == 'Web Indexer') {
				require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';
				$webIndexSettings = new WebsiteIndexSetting();
				$webIndexSettings->deleted = false;
				$webIndexSettings = $webIndexSettings->fetchAll();
				$hasErrors = false;
				$webIndexNote = '';
				/** @var WebsiteIndexSetting $webIndexSetting */
				foreach ($webIndexSettings as $webIndexSetting) {
					require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexLogEntry.php';
					$websiteIndexingEntry = new WebsiteIndexLogEntry();
					$websiteIndexingEntry->websiteName = $webIndexSetting->name;
					$websiteIndexingEntry->orderBy("id DESC");
					$websiteIndexingEntry->find();
					if ($websiteIndexingEntry->getNumResults() > 0) {
						$websiteIndexingEntry->fetch();
						if ($websiteIndexingEntry->numErrors > 0) {
							$webIndexNote .= $webIndexSetting->name . ' had an error on the last run<br/>';
						}
						if (empty($websiteIndexingEntry->endTime)){
							//First indexing entry has not finished, check the one before that
							if ($websiteIndexingEntry->getNumResults() > 1) {
								$websiteIndexingEntry->fetch();
								if ($websiteIndexingEntry->numErrors > 0) {
									$webIndexNote .= $webIndexSetting->name . ' had an error on the last completed run<br/>';
								} elseif (empty($websiteIndexingEntry->endTime)){
									$webIndexNote .= $webIndexSetting->name . ' has not finished indexing on the last 2 tries<br/>';
								}
							} else {
								$webIndexNote .= $webIndexSetting->name . ' has never finished indexing<br/>';
							}
						}
					}else{
						$hasErrors = true;
						$webIndexNote .= $webIndexSetting->name . ' has never been indexed<br/>';
					}
				}
				if (!$hasErrors) {
					$this->addCheck($checks, $aspenModule->name);
				}else{
					$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, $webIndexNote);
				}
			}elseif ($aspenModule->name == 'Side Loads') {
				require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';
				$sideload = new SideLoad();
				$sideloads = $sideload->fetchAll();
				$hasErrors = false;
				$sideloadIndexNote = '';
				/** @var Sideload  $sideload */
				foreach ($sideloads as $sideload) {
					require_once ROOT_DIR . '/sys/Indexing/SideLoadLogEntry.php';
					$sideLoadLogEntry = new SideLoadLogEntry();
					$sideLoadLogEntry->whereAdd("sideLoadsUpdated LIKE " . $sideload->escape("%".$sideload->name."%"));
					$sideLoadLogEntry->orderBy("id DESC");
					$sideLoadLogEntry->find();
					if ($sideLoadLogEntry->getNumResults() > 0) {
						$sideLoadLogEntry->fetch();
						if ($sideLoadLogEntry->numErrors > 0) {
							$sideloadIndexNote .= $sideload->name . " had an error on the last run<br/>";
						}
						if (empty($sideLoadLogEntry->endTime)){
							//First indexing entry has not finished, check the one before that
							if ($sideLoadLogEntry->getNumResults() > 1) {
								$sideLoadLogEntry->fetch();
								if ($sideLoadLogEntry->numErrors > 0) {
									$sideloadIndexNote .= $sideload->name . ' had an error on the last completed run<br/>';
								} elseif (empty($sideLoadLogEntry->endTime)){
									$sideloadIndexNote .= $sideload->name . ' has not finished indexing on the last 2 tries<br/>';
								}
							} else {
								$sideloadIndexNote .= $sideload->name . ' has never finished indexing<br/>';
							}
						}
					}else{
						if ($sideload->lastUpdateOfAllRecords == null && $sideload->lastUpdateOfChangedRecords == null){
							$hasErrors = true;
							$sideloadIndexNote .= $sideload->name . ' has never been indexed<br/>';
						}
					}
				}
				if (!$hasErrors) {
					$this->addCheck($checks, $aspenModule->name);
				}else{
					$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, $sideloadIndexNote);
				}
			} else {
				if (!empty($aspenModule->logClassPath) && !empty($aspenModule->logClassName)) {
					//Check to see how many settings we have
					$numSettings = 1;
					if (!empty($aspenModule->settingsClassPath) && !empty($aspenModule->settingsClassName)) {
						require_once ROOT_DIR . $aspenModule->settingsClassPath;
						/** @var DataObject $settings */
						$settings = new $aspenModule->settingsClassName;
						if ($aspenModule->name == 'Web Builder') {
							$numSettings = 1;
						} else {
							$numSettings = $settings->count();
						}

					}
					if ($numSettings == 0) {
						continue;
					}
					require_once ROOT_DIR . $aspenModule->logClassPath;
					/** @var BaseLogEntry $logEntry */
					$logEntry = new $aspenModule->logClassName();
					$logEntry->orderBy("id DESC");
					$numEntriesToCheck = 3;
					if ($aspenModule->name == 'Web Builder') {
						/** @noinspection PhpPossiblePolymorphicInvocationInspection */
						$logEntry->websiteName = 'Web Builder Content';
					}
					$logEntry->limit(0, $numEntriesToCheck * $numSettings);
					$logErrors = 0;
					$logEntry->find();
					$numUnfinishedEntries = 0;
					$lastFinishTime = 0;
					$isFirstEntry = true;
					while ($logEntry->fetch()) {
						if ($logEntry->numErrors > 0) {
							$logErrors++;
						}
						if (empty($logEntry->endTime)) {
							$numUnfinishedEntries++;
							if ($isFirstEntry && (time() - $logEntry->startTime) >= 8 * 60 * 60) {
								$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "The last log entry for {$aspenModule->name} has been running for more than 8 hours");
							}
						} else {
							if ($logEntry->endTime > $lastFinishTime) {
								$lastFinishTime = $logEntry->endTime;
							}
						}
						$isFirstEntry = false;
					}
					$checkEntriesInLast24Hours = true;
					if ($aspenModule->name == 'Web Builder') {
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
						} else {
							if ($basicPage->getNumResults() > 0) {
								$checkEntriesInLast24Hours = true;
							} else {
								if ($webResource->getNumResults() > 0) {
									$checkEntriesInLast24Hours = true;
								} else {
									$checkEntriesInLast24Hours = false;
									//Nothing to index, skip adding a check.
									continue;
								}
							}
						}

					}
					if ($checkEntriesInLast24Hours && ($lastFinishTime < time() - 24 * 60 * 60)) {
						$this->addCheck($checks, $aspenModule->name, self::STATUS_CRITICAL, "No log entries for {$aspenModule->name} have completed in the last 24 hours");
					} elseif ($checkEntriesInLast24Hours && ($lastFinishTime < time() - 8 * 60 * 60)) {
						$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "No log entries for {$aspenModule->name} have completed in the last 8 hours");
					} else {
						if ($logErrors > 0) {
							$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "The last {$logErrors} log entry for {$aspenModule->name} had errors");
						} else {
							if ($numUnfinishedEntries > $numSettings) {
								$this->addCheck($checks, $aspenModule->name, self::STATUS_WARN, "{$numUnfinishedEntries} of the last 3 log entries for {$aspenModule->name} did not finish.");
							} else {
								$this->addCheck($checks, $aspenModule->name);
							}
						}
					}
				}
			}
		}

		//Check for interface errors in the last hour
		$aspenError = new AspenError();
		$aspenError->whereAdd('timestamp > ' . (time() - 60 * 60));
		$numErrors = $aspenError->count();
		if ($numErrors > 10) {
			$this->addCheck($checks, 'Interface Errors', self::STATUS_CRITICAL, "$numErrors Interface Errors have occurred in the last hour");
		} elseif ($numErrors > 1) {
			$this->addCheck($checks, 'Interface Errors', self::STATUS_WARN, "$numErrors Interface Errors have occurred in the last hour");
		} else {
			$this->addCheck($checks, 'Interface Errors');
		}

		//Check for interface errors in the last hour
		$aspenError = new AspenError();
		$aspenError->whereAdd('timestamp > ' . (time() - 60 * 60));
		$numErrors = $aspenError->count();
		if ($numErrors > 10) {
			$this->addCheck($checks, 'Interface Errors', self::STATUS_CRITICAL, "$numErrors Interface Errors have occurred in the last hour");
		} elseif ($numErrors > 1) {
			$this->addCheck($checks, 'Interface Errors', self::STATUS_WARN, "$numErrors Interface Errors have occurred in the last hour");
		} else {
			$this->addCheck($checks, 'Interface Errors');
		}

		//Check NYT Log to see if it has errors
		require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
		$nytSetting = new NewYorkTimesSetting();
		if ($nytSetting->find(true)) {
			require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';
			$nytLog = new NYTUpdateLogEntry();
			$nytLog->orderBy("id DESC");
			$nytLog->limit(0, 1);

			if (!$nytLog->find(true)) {
				$this->addCheck($checks, 'NYT Lists', self::STATUS_WARN, "New York Times Lists have not been loaded");
			} else {
				$numErrors = 0;
				if ($nytLog->numErrors > 0) {
					$numErrors++;
				}
				if ($numErrors > 0) {
					$this->addCheck($checks, 'NYT Lists', self::STATUS_WARN, "The last log for New York Times Lists had errors");
				} else {
					$this->addCheck($checks, 'NYT Lists');
				}
			}
		}

		//Check cron to be sure it doesn't have errors either
		require_once ROOT_DIR . '/sys/CronLogEntry.php';
		$cronLogEntry = new CronLogEntry();
		$cronLogEntry->orderBy("id DESC");
		$cronLogEntry->limit(0, 1);
		if ($cronLogEntry->find(true)) {
			if ($cronLogEntry->numErrors > 0) {
				$this->addCheck($checks, "Cron", self::STATUS_WARN, "The last cron log entry had errors");
			} else {
				$this->addCheck($checks, "Cron");
			}
		}

		//Check to see if sitemaps have been created, but only if there is at least one record
		$solrSearcher->init();
		$solrSearcher->setFieldsToReturn('id');
		$solrSearcher->setLimit(1);
		$result = $solrSearcher->processSearch();
		if ($result && empty($result['error'])) {
			if ($result['response']['numFound'] > 0) {
				$sitemapFiles = scandir(ROOT_DIR . '/sitemaps');
				$groupedWorkSitemapFound = false;
				foreach ($sitemapFiles as $sitemapFile) {
					if (strpos($sitemapFile, 'grouped_work_site_map_') === 0) {
						$groupedWorkSitemapFound = true;
						break;
					}
				}
				if (!$groupedWorkSitemapFound) {
					$this->addCheck($checks, "Sitemap", self::STATUS_CRITICAL, "No sitemap found for grouped works");
				} else {
					$this->addCheck($checks, "Sitemap");
				}
			}
		}

		//Check anti virus
		$systemVariables = SystemVariables::getSystemVariables();
		if (!empty($systemVariables) && $systemVariables->monitorAntivirus) {
			$antivirusLog = "/var/log/aspen-discovery/clam_av.log";
			if (file_exists($antivirusLog)) {
				$fileModificationTime = filemtime($antivirusLog);
				$fileCreationTime = filectime($antivirusLog);
				if (max($fileModificationTime, $fileCreationTime) < (time() - 24 * 60 * 60)) {
					$this->addCheck($checks, "Antivirus", self::STATUS_CRITICAL, "Antivirus scan has not been run in the last 24 hours.  Last ran at " . date('Y-m-d H:i:s', max($fileModificationTime, $fileCreationTime) . "."));
				} else {
					$antivirusLogFh = fopen($antivirusLog, 'r');
					if ($antivirusLogFh === false) {
						$this->addCheck($checks, "Antivirus", self::STATUS_WARN, "Could not read antivirus log");
					} else {
						$numInfectedFiles = 0;
						$foundInfectedFilesLine = false;
						$numLinesRead = 0;
						while ($line = fgets($antivirusLogFh)) {
							$line = trim($line);
							if (strpos($line, 'Infected files: ') === 0) {
								$line = str_replace('Infected files: ', '', $line);
								$numInfectedFiles = $line;
								$foundInfectedFilesLine = true;
								break;
							}
							$numLinesRead++;
						}
						fclose($antivirusLogFh);
						if ($foundInfectedFilesLine) {
							if ($numInfectedFiles > 0) {
								$this->addCheck($checks, "Antivirus", self::STATUS_CRITICAL, "Antivirus detected $numInfectedFiles infected files");
							} else {
								$this->addCheck($checks, "Antivirus");
							}
						} else {
							$this->addCheck($checks, "Antivirus", self::STATUS_WARN, "Antivirus is running, read $numLinesRead lines");
						}
					}

				}
			} else {
				$this->addCheck($checks, "Antivirus", self::STATUS_WARN, "No Antivirus log file was found");
			}
		}

		//Check third party enrichment to see if it is enabled
		require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';
		$novelistSetting = new NovelistSetting();
		if ($novelistSetting->find(true)) {
			$this->addCheck($checks, "Novelist");
		}

		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSetting = new SyndeticsSetting();
		if ($syndeticsSetting->find(true)) {
			$this->addCheck($checks, "Syndetics");
		}

		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSetting = new ContentCafeSetting();
		if ($contentCafeSetting->find(true)) {
			$this->addCheck($checks, "Content Cafe");
		}

		require_once ROOT_DIR . '/sys/Enrichment/CoceServerSetting.php';
		$coceSetting = new CoceServerSetting();
		if ($coceSetting->find(true)) {
			$this->addCheck($checks, "Coce");
		}

		require_once ROOT_DIR . '/sys/Enrichment/OMDBSetting.php';
		$omdbSetting = new OMDBSetting();
		if ($omdbSetting->find(true)) {
			$this->addCheck($checks, "OMDB");
		}

		require_once ROOT_DIR . '/sys/TwoFactorAuthSetting.php';
		$twoFactorSetting = new TwoFactorAuthSetting();
		if ($twoFactorSetting->find(true)) {
			//If we have settings, make sure at least one is applied to a library and a location
			$library = new Library();
			$library->whereAdd('twoFactorAuthSettingId > 0');
			if ($library->find(true)){
				require_once ROOT_DIR . '/sys/Account/PType.php';
				$ptype = new PType();
				$ptype->whereAdd('twoFactorAuthSettingId > 0');
				if ($ptype->find(true)) {
					$this->addCheck($checks, "Two Factor Authentication");
				}
			}
		}

		$hasCriticalErrors = false;
		$hasWarnings = false;
		foreach ($checks as $check) {
			if ($check['status'] == self::STATUS_CRITICAL) {
				$hasCriticalErrors = true;
				break;
			}
			if ($check['status'] == self::STATUS_WARN) {
				$hasWarnings = true;
			}
		}

		global $interface;
		$gitBranch = $interface->getVariable('gitBranchWithCommit');
		if ($hasCriticalErrors || $hasWarnings) {
			$result = [
				'aspen_health_status' => $hasCriticalErrors ? self::STATUS_CRITICAL : self::STATUS_WARN,
				// Critical warnings trump Warnings;
				'version' => $gitBranch,
				'message' => "Errors have been found",
				'checks' => $checks,
				'serverStats' => $serverStats,
			];
		} else {
			$result = [
				'aspen_health_status' => self::STATUS_OK,
				'version' => $gitBranch,
				'message' => "Everything is current",
				'checks' => $checks,
				'serverStats' => $serverStats,
			];
		}

		if (isset($_REQUEST['prtg'])) {
			// Reformat $result to the structure expected by PRTG

			$prtgStatusValues = [
				self::STATUS_OK => 0,
				self::STATUS_WARN => 1,
				self::STATUS_CRITICAL => 2,
			];

			$prtg_results = [
				'prtg' => [
					'result' => [
						0 => [
							'channel' => 'Aspen Status',
							'value' => $prtgStatusValues[$result['status']],
							'limitmode' => 1,
							'limitmaxwarning' => $prtgStatusValues[self::STATUS_OK],
							'limitmaxerror' => $prtgStatusValues[self::STATUS_WARN],
						],
					],
					'text' => $result['message'],
				],
			];

			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

			die(json_encode($prtg_results));
		}

		return $result;
	}

	private function addCheck(&$checks, $checkName, $status = self::STATUS_OK, $note = '') {
		$checkNameMachine = str_replace(' ', '_', strtolower($checkName));
		$checks[$checkNameMachine] = [
			'name' => $checkName,
			'status' => $status,
		];
		if (!empty($note)) {
			$checks[$checkNameMachine]['note'] = $note;
		}
	}

	private function addServerStat(array &$serverStats, string $statName, $value) {
		$statNameMachine = str_replace(' ', '_', strtolower($statName));
		$serverStats[$statNameMachine] = [
			'name' => $statName,
			'value' => $value,
		];
	}

	/**
	 * Do a basic search and return results as a JSON array
	 */
	function search() {
		global $interface;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$timer->logTime('Include search engine');

		//setup the results array.
		$jsonResults = [];

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		if (isset($_REQUEST['pageSize']) && is_numeric($_REQUEST['pageSize'])) {
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
				if (stristr($error, 'org.apache.lucene.queryParser.ParseException') || preg_match('/^undefined field/', $error)) {
					$jsonResults['parseError'] = true;

					// Unexpected error -- let's treat this as a fatal condition.
				} else {
					AspenError::raiseError(new AspenError('Unable to process query<br />' . 'Solr Returned: ' . $error));
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
			$options = [
				'totalItems' => $summary['resultTotal'],
				'fileName' => $link,
				'perPage' => $summary['perPage'],
			];
			$pager = new Pager($options);
			$jsonResults['paging'] = [
				'currentPage' => $pager->getCurrentPage(),
				'totalPages' => $pager->getTotalPages(),
				'totalItems' => $pager->getTotalItems(),
				'itemsPerPage' => $pager->getItemsPerPage(),
			];
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
	function getListWidget() {
		return $this->getCollectionSpotlight();
	}

	function getCollectionSpotlight() {
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
	function getRecordIdForTitle() {
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
	function getRecordIdForItemBarcode() {
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
	function getTitleInfoForISBN() {
		if (isset($_REQUEST['isbn'])) {
			$isbn = str_replace('-', '', strip_tags($_REQUEST['isbn']));
		} else {
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
		$jsonResults = [];

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
				$jsonResults[] = [
					'id' => $record['id'],
					'title' => isset($record['title_display']) ? $record['title_display'] : null,
					'author' => isset($record['author_display']) ? $record['author_display'] : (isset($record['author2']) ? $record['author2'] : ''),
					'format' => isset($record['format_' . $solrScope]) ? $record['format_' . $solrScope] : '',
					'format_category' => isset($record['format_category_' . $solrScope]) ? $record['format_category_' . $solrScope] : '',
				];
			}
		}
		return $jsonResults;
	}

	function getActiveBrowseCategories() {
		//Figure out which library or location we are looking at
		global $library;
		global $locationSingleton;
		global $configArray;
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listApi = new ListAPI();

		$includeSubCategories = false;
		if (isset($_REQUEST['includeSubCategories'])) {
			$includeSubCategories = ($_REQUEST['includeSubCategories'] == 'true') || ($_REQUEST['includeSubCategories'] == 1);
		}
		//Check to see if we have an active location, will be null if we don't have a specific location
		//based off of url, branch parameter, or IP address
		$activeLocation = $locationSingleton->getActiveLocation();

		//Get a list of browse categories for that library / location
		/** @var BrowseCategoryGroupEntry[] $browseCategories */
		if ($activeLocation == null) {
			//We don't have an active location, look at the library
			$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategories();
		} else {
			//We have a location get data for that
			$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategories();
		}

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		//Format for return to the user, we want to return
		// - the text id of the category
		// - the display label
		// - Clickable link to load the category
		$formattedCategories = [];
		foreach ($browseCategories as $curCategory) {
			$categoryInformation = new BrowseCategory();
			$categoryInformation->id = $curCategory->browseCategoryId;

			if ($categoryInformation->find(true)) {
				if ($categoryInformation->isValidForDisplay()) {
					if ($categoryInformation->textId == "system_user_lists") {
						$userLists = $listApi->getUserLists();
						$categoryResponse['subCategories'] = [];
						$allUserLists = $userLists['lists'];
						if (count($allUserLists) > 0) {
							$categoryResponse = [
								'text_id' => $categoryInformation->textId,
								'display_label' => $categoryInformation->label,
								'link' => $configArray['Site']['url'] . '?browseCategory=' . $categoryInformation->textId,
								'source' => $categoryInformation->source,
							];
							foreach ($allUserLists as $userList) {
								if ($userList['id'] != "recommendations") {
									$categoryResponse['subCategories'][] = [
										'text_id' => $categoryInformation->textId . '_' . $userList['id'],
										'display_label' => $userList['title'],
										'source' => "List",
									];
								}
							}
							$formattedCategories[] = $categoryResponse;
						}
					} elseif ($categoryInformation->textId == "system_saved_searches") {
						$savedSearches = $listApi->getSavedSearches();
						$categoryResponse['subCategories'] = [];
						$allSearches = $savedSearches['searches'];
						if (count($allSearches) > 0) {
							$categoryResponse = [
								'text_id' => $categoryInformation->textId,
								'display_label' => $categoryInformation->label,
								'link' => $configArray['Site']['url'] . '?browseCategory=' . $categoryInformation->textId,
								'source' => $categoryInformation->source,
							];
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
						$categoryResponse = [
							'text_id' => $categoryInformation->textId,
							'display_label' => $categoryInformation->label,
							'link' => $configArray['Site']['url'] . '?browseCategory=' . $categoryInformation->textId,
							'source' => $categoryInformation->source,
						];
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
										if ($parent->find(true)) {
											$parentLabel = $parent->label;
										}
										if ($parentLabel == $temp->label) {
											$displayLabel = $temp->label;
										} else {
											$displayLabel = $parentLabel . ': ' . $temp->label;
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

	function getSubCategories($textId = null) {
		$textId = $this->getTextId($textId);
		if (!empty($textId)) {
			$activeBrowseCategory = $this->getBrowseCategory($textId);
			if ($activeBrowseCategory != null) {
				$subCategories = [];
				/** @var SubBrowseCategories $subCategory */
				foreach ($activeBrowseCategory->getSubCategories() as $subCategory) {
					// Get Needed Info about sub-category
					if ($textId == "system_saved_searches") {
						$label = explode('_', $subCategory->id);
						$id = $label[3];
						$temp = new SearchEntry();
						$temp->id = $id;
						if ($temp->find(true)) {
							$subCategories[] = [
								'label' => $subCategory->label,
								'textId' => $temp->id,
								'source' => "savedSearch",
							];
						}
					} elseif ($textId == "system_user_lists") {
						$label = explode('_', $subCategory->id);
						$id = $label[3];
						$temp = new UserList();
						$temp->id = $id;
						$numListItems = $temp->numValidListItems();
						if ($temp->find(true)) {
							if ($numListItems > 0) {
								$subCategories[] = [
									'label' => $temp->title,
									'textId' => $temp->id,
									'source' => "userList",
								];
							}
						}
					} else {
						$temp = new BrowseCategory();
						$temp->id = $subCategory->subCategoryId;
						if ($temp->find(true)) {
							if ($temp->isValidForDisplay()) {
								$subCategories[] = [
									'label' => $temp->label,
									'textId' => $temp->textId,
								];
							}
						} else {
							global $logger;
							$logger->log("Did not find subcategory with id {$subCategory->subCategoryId}", Logger::LOG_WARNING);
						}
					}
				}
				return [
					'success' => true,
					'subCategories' => $subCategories,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Could not find a category with that text id.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide the text id to load sub categories for.',
			];
		}
	}

	function getBrowseCategoryInfo() {
		$textId = $this->getTextId();
		if ($textId == null) {
			return ['success' => false];
		}
		$response = ['success' => true];
		$response['textId'] = $textId;
		$subCategoryInfo = $this->getSubCategories($textId);
		if ($subCategoryInfo['success']) {
			$response['subcategories'] = $subCategoryInfo['subCategories'];
		} else {
			$response['subcategories'] = [];
		}


		$mainCategory = $this->getBrowseCategory($textId);

		if ($mainCategory != null) {
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
				$response['label'] = translate([
					'text' => $mainCategory->label,
					'isPublicFacing' => true,
				]);

				$subCategory = $this->getBrowseCategory($subCategoryTextId);
				if ($subCategory != null) {
					return [
						'success' => false,
						'message' => 'Could not find the sub category "' . $subCategoryTextId . '"',
					];
				} else {
					$this->getBrowseCategoryResults($subCategory, $response);
				}
			} else {
				$this->getBrowseCategoryResults($mainCategory, $response);
			}
		} else {
			return [
				'success' => false,
				'message' => 'Could not find the main category "' . $textId . '"',
			];
		}

		return $response;
	}

	/**
	 * @param null $textId Optional Id to set the object's textId to
	 * @return null         Return the object's textId value
	 */
	private function getTextId($textId = null) {
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
		} else {
			return null;
		}
	}

	const ITEMS_PER_PAGE = 24;

	private function getBrowseCategoryResults($browseCategory, &$response) {
		if (isset($_REQUEST['pageToLoad']) && is_numeric($_REQUEST['pageToLoad'])) {
			$pageToLoad = (int)$_REQUEST['pageToLoad'];
		} else {
			$pageToLoad = 1;
		}
		$pageSize = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : self::ITEMS_PER_PAGE;
		if ($browseCategory->textId == 'system_recommended_for_you') {
			$this->getSuggestionsBrowseCategoryResults($pageToLoad, $pageSize, $response);
		} elseif ($browseCategory->textId == 'system_saved_searches') {
			$this->getSavedSearchBrowseCategoryResults($pageToLoad, $pageSize, $response);
		} elseif ($browseCategory->textId == 'system_user_lists') {
			$this->getUserListBrowseCategoryResults($pageToLoad, $pageSize, $response);
		} else {
			if ($browseCategory->source == 'List') {
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$sourceList = new UserList();
				$sourceList->id = $browseCategory->sourceListId;
				if ($sourceList->find(true)) {
					$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize, $this->checkIfLiDA());
				} else {
					$records = [];
				}
				$response['searchUrl'] = '/MyAccount/MyList/' . $browseCategory->sourceListId;

				// Search Browse Category //
			} elseif ($browseCategory->source == 'CourseReserve') {
				require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
				$sourceList = new CourseReserve();
				$sourceList->id = $browseCategory->sourceCourseReserveId;
				if ($sourceList->find(true)) {
					$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
				} else {
					$records = [];
				}
				$response['searchUrl'] = '/CourseReserves/' . $browseCategory->sourceCourseReserveId;

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

	function getBreadcrumbs(): array {
		return [];
	}

	private function getSuggestionsBrowseCategoryResults(int $pageToLoad, int $pageSize, &$response = []) {
		if (!UserAccount::isLoggedIn()) {
			$response = [
				'success' => false,
				'message' => 'Your session has timed out, please login again to view suggestions',
			];
		} else {
			$response['label'] = translate([
				'text' => 'Recommended for you',
				'isPublicFacing' => true,
			]);
			$response['searchUrl'] = '/MyAccount/SuggestedTitles';

			require_once ROOT_DIR . '/sys/Suggestions.php';
			$suggestions = Suggestions::getSuggestions(-1, $pageToLoad, $pageSize);
			$records = [];
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

	private function getAppSuggestionsBrowseCategoryResults(int $pageToLoad, int $pageSize, &$response = []) {
		if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
			return [
				'success' => false,
				'message' => 'The username and password must be provided to load system recommendations.',
			];
		}

		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$user = UserAccount::validateAccount($username, $password);

		if ($user == false) {
			return [
				'success' => false,
				'message' => 'Sorry, we could not find a user with those credentials.',
			];
		}

		$response['label'] = translate([
			'text' => 'Recommended for you',
			'isPublicFacing' => true,
		]);
		$response['searchUrl'] = '/MyAccount/SuggestedTitles';

		require_once ROOT_DIR . '/sys/Suggestions.php';
		$suggestions = Suggestions::getSuggestions(-1, $pageToLoad, $pageSize, $user);
		$records = [];
		foreach ($suggestions as $suggestedItemId => $suggestionData) {
			$record = $suggestionData['titleInfo'];
			$formats = [];
			foreach($record['format'] as $format) {
				$splitFormat = explode('#', $format);
				if(!in_array($splitFormat[1], $formats)) {
					$formats[] = $splitFormat[1];
				}
			}
			$record['format'] = $formats;
			$formatCategories = [];
			foreach($record['format_category'] as $format) {
				$splitFormat = explode('#', $format);
				if(!in_array($splitFormat[1], $formatCategories)) {
					$formatCategories[] = $splitFormat[1];
				}
			}
			$record['format_category'] = $formatCategories;
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

	private function getSavedSearchBrowseCategoryResults(int $pageSize, $id = null, $appUser = null) {

		if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
			return [
				'success' => false,
				'message' => 'The username and password must be provided to load saved searches.',
			];
		}

		if ($appUser) {
			$user = UserAccount::login();
		} else {
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$user = UserAccount::validateAccount($username, $password);
		}

		if ($user == false) {
			return [
				'success' => false,
				'message' => 'Sorry, we could not find a user with those credentials.',
			];
		}

		if ($id) {
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

	private function getUserListBrowseCategoryResults(int $pageToLoad, int $pageSize, $id = null, $forLida = false) {
		if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
			return [
				'success' => false,
				'message' => 'The username and password must be provided to load lists.',
			];
		}

		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$user = UserAccount::validateAccount($username, $password);

		if ($user == false) {
			return [
				'success' => false,
				'message' => 'Sorry, we could not find a user with those credentials.',
			];
		}

		if (!empty($id)) {
			$label = explode('_', $id);
		} else {
			$label = explode('_', $_REQUEST['id']);
		}
		$id = $label[3];
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$sourceList = new UserList();
		$sourceList->id = $id;
		if ($sourceList->find(true)) {
			$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize, $forLida);
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

		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();
		$appUser = UserAccount::validateAccount($username, $password);

		/** @var BrowseCategoryGroupEntry[] $browseCategories */
		if ($activeLocation == null) {
			$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA(null, $appUser, false);
		} else {
			$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA(null, $appUser, false);
		}
		$formattedCategories = [];
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		foreach ($browseCategories as $curCategory) {
			$categoryResponse = [];
			$categoryInformation = new BrowseCategory();
			$categoryInformation->id = $curCategory->browseCategoryId;
			if ($categoryInformation->find(true)) {
				if ($categoryInformation->isValidForDisplay($appUser, false) && ($categoryInformation->source == 'GroupedWork' || $categoryInformation->source == 'List')) {
					if ($categoryInformation->textId == ('system_saved_searches')) {
						$savedSearches = $listApi->getSavedSearches($appUser->id);
						$allSearches = $savedSearches['searches'];
						foreach ($allSearches as $savedSearch) {
							require_once ROOT_DIR . '/sys/SearchEntry.php';
							$obj = new SearchEntry();
							$obj->id = $savedSearch['id'];
							if ($obj->find(true)) {
								$thisId = $categoryInformation->textId . '_' . $savedSearch['id'];
								$categoryResponse = [
									'key' => $thisId,
									'title' => $categoryInformation->label . ': ' . $savedSearch['title'],
									'source' => 'SavedSearch',
									'sourceId' => $obj->id,
									'isHidden' => $obj->isDismissed($appUser),
								];
								$formattedCategories[] = $categoryResponse;
							}
						}
					} elseif ($categoryInformation->textId == ('system_user_lists')) {
						$userLists = $listApi->getUserLists();
						$allUserLists = $userLists['lists'] ?? [];
						if (count($allUserLists) > 0) {
							foreach ($allUserLists as $userList) {
								if ($userList['id'] != 'recommendations') {
									$thisId = $categoryInformation->textId . '_' . $userList['id'];
									require_once ROOT_DIR . '/sys/UserLists/UserList.php';
									$obj = new UserList();
									$obj->id = $userList['id'];
									if ($obj->find(true)) {
										$categoryResponse = [
											'key' => $thisId,
											'title' => $categoryInformation->label . ': ' . $obj->title,
											'source' => 'List',
											'sourceId' => (string)$obj->id,
											'isHidden' => $obj->isDismissed($appUser),
										];
										$formattedCategories[] = $categoryResponse;
									}
								}
							}
						}
					} elseif ($categoryInformation->source == 'List' && $categoryInformation->textId != ('system_user_lists') && $categoryInformation->sourceListId != '-1' && $categoryInformation->sourceListId) {
						$categoryResponse = [
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'categoryId' => $categoryInformation->id,
							'source' => $categoryInformation->source,
							'sourceId' => (string)$categoryInformation->sourceListId,
							'isHidden' => $categoryInformation->isDismissed($appUser),
						];
						$count = 0;
						require_once(ROOT_DIR . '/sys/UserLists/UserList.php');
						require_once(ROOT_DIR . '/sys/UserLists/UserListEntry.php');
						$list = new UserList();
						$list->id = $categoryInformation->sourceListId;
						if ($list->find(true)) {
							$listEntry = new UserListEntry();
							$listEntry->listId = $list->id;
							$listEntry->find();
							do {
								if ($listEntry->source == 'Lists') {
									$count++;
								} elseif ($listEntry->sourceId) {
									$count++;
								}
							} while ($listEntry->fetch() && $count < 1);
						}

						if ($count != 0) {
							$formattedCategories[] = $categoryResponse;
						}
					} elseif ($categoryInformation->textId == ('system_recommended_for_you')) {
						if (empty($appUser) && UserAccount::isLoggedIn()) {
							$appUser = UserAccount::getActiveUserObj();
						}
						$categoryResponse = [
							'key' => $categoryInformation->textId,
							'title' => $categoryInformation->label,
							'source' => $categoryInformation->source,
							'isHidden' => $categoryInformation->isDismissed($appUser),
						];
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
	function getAppActiveBrowseCategories() {
		//Figure out which library or location we are looking at
		global $library;
		global $locationSingleton;
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listApi = new ListAPI();

		$includeSubCategories = false;
		if (isset($_REQUEST['includeSubCategories'])) {
			$includeSubCategories = ($_REQUEST['includeSubCategories'] == 'true') || ($_REQUEST['includeSubCategories'] == 1);
		}

		// check if we should limit the initial return
		$maxCategories = null;
		if (isset($_REQUEST['maxCategories'])) {
			$maxCategories = $_REQUEST['maxCategories'];
		}

		$isLiDARequest = false;
		if (isset($_REQUEST['LiDARequest'])) {
			$isLiDARequest = $_REQUEST['LiDARequest'];
		}

		//Check to see if we have an active location, will be null if we don't have a specific location
		//based off of url, branch parameter, or IP address
		$activeLocation = $locationSingleton->getActiveLocation();

		[
			$username,
			$password,
		] = $this->loadUsernameAndPassword();
		$appUser = UserAccount::validateAccount($username, $password);

		//Get a list of browse categories for that library / location
		/** @var BrowseCategoryGroupEntry[] $browseCategories */
		if ($activeLocation == null) {
			//We don't have an active location, look at the library
			if ($isLiDARequest) {
				$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA($maxCategories, $appUser);
			} else {
				$browseCategories = $library->getBrowseCategoryGroup()->getBrowseCategories();
			}
		} else {
			//We have a location get data for that
			if ($isLiDARequest) {
				$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategoriesForLiDA($maxCategories, $appUser);
			} else {
				$browseCategories = $activeLocation->getBrowseCategoryGroup()->getBrowseCategories();
			}
		}
		$formattedCategories = [];

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		//Format for return to the user, we want to return
		// - the text id of the category
		// - the display label
		// - Clickable link to load the category
		$numCategoriesProcessed = 0;
		foreach ($browseCategories as $curCategory) {
			$categoryResponse = [];
			$categoryInformation = new BrowseCategory();
			$categoryInformation->id = $curCategory->browseCategoryId;

			if ($categoryInformation->find(true)) {
				if ($categoryInformation->isValidForDisplay($appUser, false) && ($categoryInformation->source == "GroupedWork" || $categoryInformation->source == "List")) {
					if ($categoryInformation->textId == ("system_saved_searches")) {
						$savedSearches = $listApi->getSavedSearches($appUser->id);
						$allSearches = $savedSearches['searches'];
						foreach ($allSearches as $savedSearch) {
							require_once ROOT_DIR . '/sys/SearchEntry.php';
							$obj = new SearchEntry();
							$obj->id = $savedSearch['id'];
							if ($obj->find(true)) {
								if (!$obj->isDismissed($appUser)) {
									$thisId = $categoryInformation->textId . '_' . $savedSearch['id'];
									$savedSearchResults = $this->getAppBrowseCategoryResults($thisId, $appUser, 12);
									$formattedSavedSearchResults = [];
									if (count($savedSearchResults) > 0) {
										foreach ($savedSearchResults as $savedSearchResult) {
											$formattedSavedSearchResults[] = [
												'id' => $savedSearchResult['id'],
												'title_display' => $savedSearchResult['title'],
												'isNew' => $savedSearchResult['isNew'],
											];
										}
									}
									$categoryResponse = [
										'key' => $thisId,
										'title' => $categoryInformation->label . ': ' . $obj->title,
										'source' => 'SavedSearch',
										'sourceId' => $obj->id,
										'isHidden' => $obj->isDismissed($appUser),
										'records' => $formattedSavedSearchResults,
									];
									$formattedCategories[] = $categoryResponse;
									$numCategoriesProcessed++;
									if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories) {
										break;
									}
								}
							}
						}
					} elseif ($categoryInformation->textId == ("system_user_lists")) {
						$userLists = $listApi->getUserLists();
						$allUserLists = $userLists['lists'] ?? [];
						if (count($allUserLists) > 0) {
							foreach ($allUserLists as $userList) {
								if ($userList['id'] != "recommendations") {
									require_once ROOT_DIR . '/sys/UserLists/UserList.php';
									$obj = new UserList();
									$obj->id = $userList['id'];
									if ($obj->find(true)) {
										if (!$obj->isDismissed($appUser)) {
											$thisId = $categoryInformation->textId . '_' . $userList['id'];
											$categoryResponse = [
												'key' => $thisId,
												'title' => $categoryInformation->label . ': ' . $userList['title'],
												'source' => "List",
												'sourceId' => $userList['id'],
												'isHidden' => $categoryInformation->isDismissed($appUser),
												'records' => $this->getAppBrowseCategoryResults($thisId, null, 12),
											];
											$formattedCategories[] = $categoryResponse;
											$numCategoriesProcessed++;
											if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories) {
												break;
											}
										}
									}
								}
							}
						}
					} elseif ($categoryInformation->source == "List" && $categoryInformation->textId != ("system_user_lists") && $categoryInformation->sourceListId != "-1" && $categoryInformation->sourceListId) {
						if (!$categoryInformation->isDismissed($appUser)) {
							$categoryResponse = [
								'key' => $categoryInformation->textId,
								'title' => $categoryInformation->label,
								'id' => $categoryInformation->id,
								'source' => $categoryInformation->source,
								'listId' => (string)$categoryInformation->sourceListId,
								'isHidden' => $categoryInformation->isDismissed($appUser),
								'records' => [],
								'lists' => [],
							];

							require_once(ROOT_DIR . '/sys/UserLists/UserList.php');
							require_once(ROOT_DIR . '/sys/UserLists/UserListEntry.php');
							$list = new UserList();
							$list->id = $categoryInformation->sourceListId;
							if ($list->find(true)) {
								$listEntry = new UserListEntry();
								$listEntry->listId = $list->id;
								$listEntry->whereAdd("source <> 'Events'");
								$listEntry->find();
								$count = 0;
								do {
									if ($listEntry->source == 'Lists') {
										$categoryResponse['lists'][] = [
											'sourceId' => $listEntry->sourceId,
											'title' => $listEntry->title,
										];
										$count++;
									} elseif ($listEntry->source == 'Events') {
										// just to make sure events don't sneak in
										$categoryResponse['records'] = [];
									} else {
										if ($listEntry->sourceId) {
											$categoryResponse['records'][] = [
												'id' => $listEntry->sourceId,
												'title' => $listEntry->title,
											];
											$count++;
										}
									}
								} while ($listEntry->fetch() && $count < 12);

								if (count($categoryResponse['lists']) != 0 || count($categoryResponse['records']) != 0) {
									$formattedCategories[] = $categoryResponse;
									$numCategoriesProcessed++;
									if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories) {
										break;
									}
								}
							}

						}

					} elseif ($categoryInformation->textId == ("system_recommended_for_you")) {
						if (empty($appUser) && UserAccount::isLoggedIn()) {
							$appUser = UserAccount::getActiveUserObj();
						}

						if (!$categoryInformation->isDismissed($appUser)) {
							require_once(ROOT_DIR . '/sys/Suggestions.php');
							$suggestions = Suggestions::getSuggestions($appUser->id);

							$categoryResponse = [
								'key' => $categoryInformation->textId,
								'title' => $categoryInformation->label,
								'source' => $categoryInformation->source,
								'isHidden' => $categoryInformation->isDismissed($appUser),
								'records' => [],
							];

							if (count($suggestions) > 0) {
								foreach ($suggestions as $suggestion) {
									$categoryResponse['records'][] = [
										'id' => $suggestion['titleInfo']['id'],
										'title_display' => $suggestion['titleInfo']['title_display'],
									];
								}
							}
							$formattedCategories[] = $categoryResponse;
							$numCategoriesProcessed++;
							if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories) {
								break;
							}
						}
					} else {
						$subCategories = $categoryInformation->getSubCategories();
						if (count($subCategories) == 0 && !$categoryInformation->isDismissed($appUser)) {
							$records = $this->getAppBrowseCategoryResults($categoryInformation->textId, null, 12);
								if(count($records) > 0) {
									$categoryResponse = [
										'key' => $categoryInformation->textId,
										'title' => $categoryInformation->label,
										'source' => $categoryInformation->source,
										'isHidden' => $categoryInformation->isDismissed($appUser),
										'records' => $records,
									];
									$numCategoriesProcessed++;
									$formattedCategories[] = $categoryResponse;
								}
						}
						if ($includeSubCategories) {
							if (count($subCategories) > 0) {
								foreach ($subCategories as $subCategory) {
									$temp = new BrowseCategory();
									$temp->id = $subCategory->subCategoryId;
									if ($temp->find(true)) {
										if ($temp->isValidForDisplay($appUser)) {
											if ($temp->source != '') {
												$records = $this->getAppBrowseCategoryResults($temp->textId, null, 12);
												if(count($records) > 0) {
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
														'sourceId' => (string)$temp->sourceListId,
														'records' => $records,
													];
													$formattedCategories[] = $categoryResponse;
													$numCategoriesProcessed++;
													if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories) {
														break;
													}
												}
											}
										}
									}
									if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories) {
										break;
									}
								}
							}
						}
					}
					if ($maxCategories > 0 && $numCategoriesProcessed >= $maxCategories) {
						break;
					}
				}
			}
		}
		return $formattedCategories;
	}

	/** @noinspection PhpUnused */
	function getAppBrowseCategoryResults($id = null, $appUser = null, $pageSize = null) {
		if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
			$pageToLoad = (int)$_REQUEST['page'];
		} else {
			$pageToLoad = 1;
		}

		if (!$pageSize) {
			$pageSize = $_REQUEST['limit'] ?? self::ITEMS_PER_PAGE;
		}
		if ($id) {
			$thisId = $id;
		} else {
			$thisId = $_REQUEST['id'];
		}
		$response = [];

		if (strpos($thisId, "system_saved_searches") !== false) {
			if ($id) {
				$result = $this->getSavedSearchBrowseCategoryResults($pageSize, $id, $appUser);
			} else {
				$result = $this->getSavedSearchBrowseCategoryResults($pageSize);
			}
			if (!$id) {
				$response['key'] = $thisId;
			}
			if (isset($result['items'])) {
				$response['records'] = $result['items'];
			} else {
				//Error loading items
				$response['records'] = [];
			}
		} elseif (strpos($thisId, "system_user_lists") !== false) {
			if ($id) {
				$result = $this->getUserListBrowseCategoryResults($pageToLoad, $pageSize, $id, true);
			} else {
				$result = $this->getUserListBrowseCategoryResults($pageToLoad, $pageSize, null, true);
			}
			if (!$id) {
				$response['key'] = $thisId;
			}
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
							$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize, true);
						} else {
							$records = [];
						}

						// Search Browse Category //
					} elseif ($browseCategory->source == 'CourseReserve') {
						require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
						$sourceList = new CourseReserve();
						$sourceList->id = $browseCategory->sourceCourseReserveId;
						if ($sourceList->find(true)) {
							$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize);
						} else {
							$records = [];
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
					if (!$id) {
						$response['key'] = $browseCategory->textId;
					}
					$response['records'] = $records;
				}
			} else {
				$response = [
					'success' => false,
					'message' => 'Browse category not found',
				];
			}
		}

		if ($id) {
			return $response['records'];
		}

		return $response;
	}

	function getListResults() {
		if (!empty($_REQUEST['page'])) {
			$pageToLoad = $_REQUEST['page'];
		} else {
			$pageToLoad = 1;
		}

		if (!empty($_REQUEST['limit'])) {
			$pageSize = $_REQUEST['limit'];
		} else {
			$pageSize = self::ITEMS_PER_PAGE;
		}

		if (!empty($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
		} else {
			return [
				'success' => false,
				'message' => 'List id not provided',
			];
		}

		$isLida = $this->checkIfLiDA();

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$sourceList = new UserList();
		$sourceList->id = $id;
		if ($sourceList->find(true)) {
			$response['title'] = $sourceList->title;
			$response['id'] = $sourceList->id;
			$records = $sourceList->getBrowseRecordsRaw(($pageToLoad - 1) * $pageSize, $pageSize, $isLida);
		}
		$response['items'] = $records;

		return $response;
	}

	function getSavedSearchResults() {
		if (isset($_REQUEST['limit'])) {
			$pageSize = $_REQUEST['limit'];
		} else {
			$pageSize = self::ITEMS_PER_PAGE;
		}

		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
		} else {
			return [
				'success' => false,
				'message' => 'Search id not provided',
			];
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
	private function loadUsernameAndPassword(): array {
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
		return [
			$username,
			$password,
		];
	}

	protected function getUserForApiCall() {
		$user = false;
		[$username, $password] = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user !== false && $user->source == 'admin') {
			return false;
		}
		return $user;
	}

	/** @noinspection PhpUnused */
	function getAppSearchResults(): array {
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
				$iconName = $configArray['Site']['url'] . "/bookcover.php?id=" . $item['id'] . "&size=medium&type=grouped_work";
				$id = $item['id'];
				if ($ccode != '') {
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
						$itemList[] = [
							'id' => $relatedRecord->id,
							'name' => $relatedRecord->format,
							'source' => $relatedRecord->source,
						];
					} elseif (!in_array($relatedRecord->format, array_column($itemList, 'name'))) {
						$itemList[] = [
							'id' => $relatedRecord->id,
							'name' => $relatedRecord->format,
							'source' => $relatedRecord->source,
						];
					}
				}

				if (!empty($itemList)) {
					$results['items'][] = [
						'title' => trim($title),
						'author' => $author,
						'image' => $iconName,
						'format' => $format,
						'itemList' => $itemList,
						'key' => $id,
						'summary' => $summary,
						'language' => $language,
					];
				}

				$results['sortList'] = $searchResults['sortList'];
				$results['facetSet'] = $searchResults['facetSet'];
				$results['paging'] = $searchResults['paging'];
			}
		}

		if (empty($results['items'])) {
			$results['items'] = [];
			$results['count'] = 0;
			if ($page == 1) {
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

		$searchType = $_REQUEST['type'] ?? 'catalog';

		$results = [
			'success' => false,
			'type' => $searchType,
			'searchIndex' => 'Keyword',
			'searchSource' => 'local',
			'count' => 0,
			'totalResults' => 0,
			'lookfor' => $_REQUEST['lookfor'] ?? null,
			'title' => translate([
				'text' => 'No Results Found',
				'isPublicFacing' => true,
			]),
			'items' => [],
			'message' => translate([
				'text' => "Your search did not match any resources.",
				'isPublicFacing' => true,
			]),
		];

		$includeSortList = $_REQUEST['includeSortList'] ?? true;

		if($searchType == 'user_list') {
			if(!isset($_REQUEST['id'])) {
				return [
					'success' => false,
					'message' => 'The id of the list to load must be provided as the id parameter.',
					'count' => 0,
					'searchIndex' => 'lists',
					'totalResults' => 0,
					'items' => [],
					'lookfor' => null,
					'listId' => null,
				];
			}
			$id = $_REQUEST['id'];
			if(strpos($_REQUEST['id'], '_')  !== false) {
				$label = explode('_', $_REQUEST['id']);
				$id = $label[3];
			}
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$sourceList = new UserList();
			$sourceList->id = $id;
			if($sourceList->find(true)) {
				$results['listId'] = $sourceList->id;
				$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
				$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
				$startRecord = ($page - 1) * $recordsPerPage;
				if ($startRecord < 0) {
					$startRecord = 0;
				}
				$totalRecords = $sourceList->numValidListItems();
				$endRecord = $page * $recordsPerPage;
				if ($endRecord > $totalRecords) {
					$endRecord = $totalRecords;
				}
				$pageInfo = [
					'resultTotal' => $totalRecords,
					'startRecord' => $startRecord,
					'endRecord' => $endRecord,
					'perPage' => $recordsPerPage,
				];
				$records = $sourceList->getBrowseRecordsRaw($startRecord, $recordsPerPage);
				$items = [];
				foreach($records as $recordKey => $record) {
					$items[$recordKey]['key'] = $record['id'];
					$items[$recordKey]['title'] = $record['title_display'] ?? null;
					$items[$recordKey]['author'] = $record['author_display'] ?? null;
					$items[$recordKey]['image'] = $configArray['Site']['url'] . '/bookcover.php?id=' . $record['id'] . '&size=medium&type=grouped_work';
					$items[$recordKey]['language'] = $record['language'][0] ?? null;
					$items[$recordKey]['summary'] = null;
					$items[$recordKey]['itemList'] = [];
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					$groupedWorkDriver = new GroupedWorkDriver($record['id']);
					if ($groupedWorkDriver->isValid()) {
						$i = 0;
						$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
						foreach ($relatedManifestations as $relatedManifestation) {
							foreach ($relatedManifestation->getVariations() as $obj) {
								if(!array_key_exists($obj->manifestation->format, $items[$recordKey]['itemList'])) {
									$format = $obj->manifestation->format;
									$items[$recordKey]['itemList'][$format]['key'] = $i;
									$items[$recordKey]['itemList'][$format]['name'] = translate(['text' => $format, 'isPublicFacing' => true]);
									$i++;
								};
							}
						}
					}
				}
				$link = $_SERVER['REQUEST_URI'];
				if (preg_match('/[&?]page=/', $link)) {
					$link = preg_replace("/page=\\d+/", 'page=%d', $link);
				} elseif (strpos($link, '?') > 0) {
					$link .= '&page=%d';
				} else {
					$link .= '?page=%d';
				}
				$options = [
					'totalItems' => $pageInfo['resultTotal'],
					'perPage' => $pageInfo['perPage'],
					'fileName' => $link,
					'append' => false,
				];
				$results['searchIndex'] = $searchObject->getSearchIndex();
				$results['searchSource'] = $searchObject->getSearchSource();
				$results['defaultSearchIndex'] = $searchObject->getDefaultIndex();
				require_once ROOT_DIR . '/sys/Pager.php';
				$pager = new Pager($options);
				$results['totalResults'] = (int)$pager->getTotalItems();
				$results['count'] = (int)$pageInfo['resultTotal'];
				$results['page_current'] = (int)$pager->getCurrentPage();
				$results['page_total'] = (int)$pager->getTotalPages();
				$results['items'] = $items;
				$results['title'] = translate([
					'text' => 'List Results',
					'isPublicFacing' => true,
				]);
				$results['message'] = translate([
					'text' => 'Your list has %1% results',
					1 => $pageInfo['resultTotal'],
					'isPublicFacing' => true,
				]);
				$results['success'] = true;
			}
			return $results;
		}

		if($searchType == 'browse_category') {
			if(!isset($_REQUEST['id'])) {
				return [
					'success' => false,
					'message' => 'The textId of the browse category to load must be provided as the id parameter.',
					'count' => 0,
					'totalResults' => 0,
					'items' => [],
					'lookfor' => null,
					'browseCategoryId' => null,
				];
			}
			$records = $this->getAppBrowseCategoryResults($_REQUEST['id'], null, $_REQUEST['pageSize'] ?? 25);
			$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$startRecord = ($page - 1) * $recordsPerPage;
			if ($startRecord < 0) {
				$startRecord = 0;
			}
			$totalRecords = count($records);
			$endRecord = $page * $recordsPerPage;
			if ($endRecord > $totalRecords) {
				$endRecord = $totalRecords;
			}
			$pageInfo = [
				'resultTotal' => $totalRecords,
				'startRecord' => $startRecord,
				'endRecord' => $endRecord,
				'perPage' => $recordsPerPage,
			];
			$items = [];
			foreach($records as $recordKey => $record) {
				$items[$recordKey]['key'] = $record['id'];
				$items[$recordKey]['title'] = $record['title_display'];
				$items[$recordKey]['author'] = $record['author_display'];
				$items[$recordKey]['image'] = $configArray['Site']['url'] . '/bookcover.php?id=' . $record['id'] . '&size=medium&type=grouped_work';
				$items[$recordKey]['language'] = $record['language'][0];
				$items[$recordKey]['summary'] = '';
				$items[$recordKey]['itemList'] = [];
				require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
				$groupedWorkDriver = new GroupedWorkDriver($record['id']);
				if ($groupedWorkDriver->isValid()) {
					$i = 0;
					$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
					foreach ($relatedManifestations as $relatedManifestation) {
						foreach ($relatedManifestation->getVariations() as $obj) {
							if(!array_key_exists($obj->manifestation->format, $items[$recordKey]['itemList'])) {
								$format = $obj->manifestation->format;
								$items[$recordKey]['itemList'][$format]['key'] = $i;
								$items[$recordKey]['itemList'][$format]['name'] = translate(['text' => $format, 'isPublicFacing' => true]);
								$i++;
							};
						}
					}
				}
			}
			$link = $_SERVER['REQUEST_URI'];
			if (preg_match('/[&?]page=/', $link)) {
				$link = preg_replace("/page=\\d+/", 'page=%d', $link);
			} elseif (strpos($link, '?') > 0) {
				$link .= '&page=%d';
			} else {
				$link .= '?page=%d';
			}
			$options = [
				'totalItems' => $pageInfo['resultTotal'],
				'perPage' => $pageInfo['perPage'],
				'fileName' => $link,
				'append' => false,
			];
			$results['searchIndex'] = $searchObject->getSearchIndex();
			$results['searchSource'] = $searchObject->getSearchSource();
			$results['defaultSearchIndex'] = $searchObject->getDefaultIndex();
			require_once ROOT_DIR . '/sys/Pager.php';
			$pager = new Pager($options);
			$results['totalResults'] = (int)$pager->getTotalItems();
			$results['count'] = (int)$pageInfo['resultTotal'];
			$results['page_current'] = (int)$pager->getCurrentPage();
			$results['page_total'] = (int)$pager->getTotalPages();
			$results['items'] = $items;
			$results['title'] = translate([
				'text' => 'Browse Category Results',
				'isPublicFacing' => true,
			]);
			$results['message'] = translate([
				'text' => 'Browse category has %1% results',
				1 => $pageInfo['resultTotal'],
				'isPublicFacing' => true,
			]);
			$results['success'] = true;
			return $results;
		}


		$searchEngine = $_REQUEST['source'] ?? 'local';
		if($searchEngine == 'local' || $searchEngine == 'catalog') {
			$searchEngine = 'GroupedWork';
		}
		$searchEngine = ucfirst($searchEngine);

		// Include Search Engine Class
		if($searchEngine == 'Events') {
			require_once ROOT_DIR . '/sys/SolrConnector/EventsSolrConnector.php';
		} else {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		}
		$timer->logTime('Include search engine');

		// Initialise from the current search globals
		$searchObject = SearchObjectFactory::initSearchObject($searchEngine);
		$searchObject->init();

		if (isset($_REQUEST['pageSize']) && is_numeric($_REQUEST['pageSize'])) {
			$searchObject->setLimit($_REQUEST['pageSize']);
		}

		if($searchEngine == 'GroupedWork') {
			if (isset($_REQUEST['filter'])) {
				if (is_array($_REQUEST['filter'])) {
					$givenFilters = $_REQUEST['filter'];
					foreach ($givenFilters as $filter) {
						$filterSplit = explode(':', $filter);
						if($filterSplit[0] == 'availability_toggle') {
							$searchObject->removeFilterByPrefix('availability_toggle'); // clear anything previously set
							$searchObject->addFilter('availability_toggle:'.$filterSplit[1]);
						}
					}
				}
			} elseif (isset($_REQUEST['availability_toggle'])) {
				$searchObject->removeFilterByPrefix('availability_toggle'); // clear anything previously set
				$searchObject->addFilter('availability_toggle:' . $_REQUEST['availability_toggle']);
			} else {
				$searchLibrary = Library::getSearchLibrary(null);
				$searchLocation = Location::getSearchLocation(null);
				if ($searchLocation) {
					$availabilityToggleValue = $searchLocation->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
				} else {
					$availabilityToggleValue = $searchLibrary->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
				}
				$searchObject->removeFilterByPrefix('availability_toggle'); // clear anything previously set
				$searchObject->addFilter('availability_toggle:'.$availabilityToggleValue);
			}
		}

		$lmBypass = false;
		$commmunicoBypass = false;
		$springshareBypass = false;
		$lmAddToList = false;
		$communicoAddToList = false;
		$springshareAddToList = false;
		$libraryEventSettings = [];
		if($searchEngine == 'Events') {
			$searchLibrary = Library::getSearchLibrary(null);
			require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
			$libraryEventsSetting = new LibraryEventsSetting();
			$libraryEventsSetting->libraryId = $searchLibrary->libraryId;
			$libraryEventSettings = $libraryEventsSetting->fetchAll();

			foreach($libraryEventSettings as $setting) {
				$source = $setting->settingSource;
				$id = $setting->settingId;
				if($source == 'library_market') {
					require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
					$eventSetting = new LMLibraryCalendarSetting();
					$eventSetting->id = $id;
					if($eventSetting->find(true)) {
						$lmBypass = $eventSetting->bypassAspenEventPages;
						$lmAddToList = $eventSetting->eventsInLists;
					}
				} else if ($source == 'communico') {
					require_once ROOT_DIR . '/sys/Events/CommunicoSetting.php';
					$eventSetting = new CommunicoSetting();
					$eventSetting->id = $id;
					if($eventSetting->find(true)) {
						$commmunicoBypass = $eventSetting->bypassAspenEventPages;
						$commmunicoBypass = $eventSetting->eventsInLists;
					}
				} else if ($source == 'springshare') {
					require_once ROOT_DIR . '/sys/Events/SpringshareLibCalSetting.php';
					$eventSetting = new SpringshareLibCalSetting();
					$eventSetting->id = $id;
					if($eventSetting->find(true)) {
						$springshareBypass = $eventSetting->bypassAspenEventPages;
						$springshareBypass = $eventSetting->eventsInLists;
					}
				} else {
					// invalid event source
				}
			}
		}

		$searchObject->setSearchSource($_REQUEST['source'] ?? 'local');

		$searchObject->setFieldsToReturn('id,title_display,author_display,language,display_description,format');
		$timer->logTime('Setup Search');

		// Process Search
		if($searchType == 'saved_search') {
			if(!isset($_REQUEST['id'])) {
				return [
					'success' => false,
					'message' => 'The id of the list to load must be provided as the id parameter.',
					'count' => 0,
					'totalResults' => 0,
					'items' => [],
					'lookfor' => null,
					'savedSearchId' => null,
				];
			}
			$label = explode('_', $_REQUEST['id']);
			$id = $label[3];
			$searchObject = $searchObject->restoreSavedSearch($id, false, true);
		}

		$searchResults = $searchObject->processSearch(false, true);
		$timer->logTime('Process Search');

		// get facets and sorting info
		$appliedFacets = $searchObject->getFilterList();
		if ($includeSortList) {
			$sortList = $searchObject->getSortList();
		}

			// 'Finish' the search... complete timers and log search history.
		$searchObject->close();

		$results['searchIndex'] = $searchObject->getSearchIndex();
		$results['searchSource'] = $searchObject->getSearchSource();
		$results['defaultSearchIndex'] = $searchObject->getDefaultIndex();

		if ($searchObject->getResultTotal() < 1) {
			// No record found
			$timer->logTime('no hits processing');

			// try changing availability_toggle if not already global
			if(isset($_REQUEST['availability_toggle']) && $_REQUEST['availability_toggle'] != 'global') {
				$_REQUEST['availability_toggle'] = 'global';
				$this->searchLite();
			}
		} else {
			$timer->logTime('save search');
			$summary = $searchObject->getResultSummary();
			$results['id'] = $searchObject->getSearchId();
			$results['lookfor'] = $searchObject->displayQuery();
			$results['sort'] = $searchObject->getSort();
			// Process Paging
			$link = $searchObject->renderLinkPageTemplate();
			$options = [
				'totalItems' => $summary['resultTotal'],
				'fileName' => $link,
				'perPage' => $summary['perPage'],
			];
			$pager = new Pager($options);
			$results['totalResults'] = $pager->getTotalItems();
			$results['count'] = $summary['resultTotal'];
			$results['page_current'] = (int)$pager->getCurrentPage();
			$results['page_total'] = (int)$pager->getTotalPages();
			$timer->logTime('finish hits processing');
			$records = $searchObject->getResultRecordSet();
			$items = [];
			foreach ($records as $recordKey => $record) {
				if($searchEngine == 'Events') {
					if(str_starts_with($record['id'], 'lc')) {
						$eventSource = 'library_calendar';
						$bypass = $lmBypass;
						$addToList = $lmAddToList;
					} else if (str_starts_with($record['id'], 'communico')) {
						$eventSource = 'communico';
						$bypass = $commmunicoBypass;
						$addToList = $communicoAddToList;
					} else if (str_starts_with($record['id'], 'libcal')) {
						$eventSource = 'springshare_libcal';
						$bypass = $springshareBypass;
						$addToList = $springshareAddToList;
					} else {
						$eventSource = 'unknown';
						$bypass = false;
						$addToList = false;
					}

					$registrationRequired = false;
					if($record['registration_required'] == 'Yes' || $record['registration_required'] == 'yes') {
						$registrationRequired = true;
					}

					$locationInfo = null;
					if($record['branch']) {
						require_once ROOT_DIR . '/services/API/EventAPI.php';
						$eventApi = new EventAPI();
						$locationInfo = $eventApi->getDiscoveryBranchDetails($record['branch'][0]);
					}
					$items[$recordKey]['key'] = $record['id'];
					$items[$recordKey]['source'] = $eventSource;
					$items[$recordKey]['title'] = $record['title'];
					$items[$recordKey]['author'] = null;
					$items[$recordKey]['image'] = $configArray['Site']['url'] . '/bookcover.php?id=' . $record['id'] . '&size=medium&type=' . $eventSource . '_event';
					$items[$recordKey]['language'] = null;
					$items[$recordKey]['summary'] = strip_tags($record['description']);
					$items[$recordKey]['registration_required'] = $registrationRequired;
					$items[$recordKey]['event_day'] = $record['event_day'];
					$items[$recordKey]['location'] = $locationInfo;
					$items[$recordKey]['room'] = $record['room'] ?? null;

					$startDate = new DateTime($record['start_date']);
					$items[$recordKey]['start_date'] = $startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
					$endDate = new DateTime($record['end_date']);
					$items[$recordKey]['end_date'] = $endDate->setTimezone(new DateTimeZone(date_default_timezone_get()));

					$items[$recordKey]['url'] = $record['url'];
					$items[$recordKey]['bypass'] = $bypass;
					$items[$recordKey]['canAddToList'] = false;

					$user = $this->getUserForApiCall();
					if ($user && !($user instanceof AspenError)) {
						$source = $eventSource;
						if($eventSource == 'springshare_libcal') {
							$source = 'springshare';
						}
						$items[$recordKey]['canAddToList'] = $user->isAllowedToAddEventsToList($source);
					}

					$items[$recordKey]['itemList'] = [];
				} else {
					$items[$recordKey]['key'] = $record['id'];
					$items[$recordKey]['title'] = $record['title_display'];
					$items[$recordKey]['author'] = $record['author_display'];
					$items[$recordKey]['image'] = $configArray['Site']['url'] . '/bookcover.php?id=' . $record['id'] . '&size=medium&type=grouped_work';
					$items[$recordKey]['language'] = $record['language'][0];
					$items[$recordKey]['summary'] = $record['display_description'];
					$items[$recordKey]['itemList'] = [];
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					$groupedWorkDriver = new GroupedWorkDriver($record['id']);
					if ($groupedWorkDriver->isValid()) {
						$i = 0;
						$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
						foreach ($relatedManifestations as $relatedManifestation) {
							foreach ($relatedManifestation->getVariations() as $obj) {
								if (!array_key_exists($obj->manifestation->format, $items[$recordKey]['itemList'])) {
									$format = $obj->manifestation->format;
									$items[$recordKey]['itemList'][$format]['key'] = $i;
									$items[$recordKey]['itemList'][$format]['name'] = translate([
										'text' => $format,
										'isPublicFacing' => true
									]);
									$i++;
								};
							}
						}
					}
				}
			}

			// format facets and sorting options
			global $interface;
			$topFacetSet = $interface->getVariable('topFacetSet');
			$facets = $interface->getVariable('sideFacetSet');
			$options = [];
			$index = 0;

			if($topFacetSet) {
				$availabilityToggle = $topFacetSet['availability_toggle'];
				if ($availabilityToggle) {
					$key = translate([
						'text' => $availabilityToggle['label'],
						'isPublicFacing' => true
					]);
					$options[$key]['key'] = -1;
					$options[$key]['label'] = $key;
					$options[$key]['field'] = $availabilityToggle['field_name'];
					$options[$key]['hasApplied'] = true;
					$options[$key]['multiSelect'] = false;

					$i = 0;
					foreach ($availabilityToggle['list'] as $item) {
						$options[$key]['facets'][$i]['value'] = $item['value'];
						$options[$key]['facets'][$i]['display'] = translate([
							'text' => $item['display'],
							'isPublicFacing' => true
						]);
						$options[$key]['facets'][$i]['field'] = $availabilityToggle['field_name'];
						$options[$key]['facets'][$i]['count'] = $item['count'];
						$options[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
						$options[$key]['facets'][$i]['multiSelect'] = false;
						$i++;
					}
				}
			}

			if ($includeSortList) {
				$i = 0;
				$key = translate([
					'text' => 'Sort By',
					'isPublicFacing' => true,
				]);
				$options[$key]['key'] = 0;
				$options[$key]['label'] = $key;
				$options[$key]['field'] = 'sort_by';
				$options[$key]['hasApplied'] = true;
				$options[$key]['multiSelect'] = false;
				foreach ($sortList as $value => $sort) {
					$options[$key]['facets'][$i]['value'] = $value;
					$options[$key]['facets'][$i]['display'] = $sort['desc'];
					$options[$key]['facets'][$i]['field'] = 'sort_by';
					$options[$key]['facets'][$i]['count'] = 0;
					$options[$key]['facets'][$i]['isApplied'] = $sort['selected'];
					$options[$key]['facets'][$i]['multiSelect'] = false;
					$i++;
				}
			}

			foreach ($facets as $facet) {
				$index++;
				$i = 0;
				$key = translate([
					'text' => $facet['label'],
					'isPublicFacing' => true
				]);
				$options[$key]['key'] = $index;
				$options[$key]['label'] = $key;
				$options[$key]['field'] = $facet['field_name'];
				$options[$key]['hasApplied'] = $facet['hasApplied'];
				$options[$key]['multiSelect'] = false;
				if(isset($facet['multiSelect'])) {
					$options[$key]['multiSelect'] = (bool)$facet['multiSelect'];
				}
				if (isset($facet['sortedList'])) {
					foreach ($facet['sortedList'] as $item) {
						$options[$key]['facets'][$i]['value'] = $item['value'];
						$options[$key]['facets'][$i]['display'] = $item['display'];
						$options[$key]['facets'][$i]['field'] = $facet['field_name'];
						$options[$key]['facets'][$i]['count'] = $item['count'];
						$options[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
						if (isset($item['multiSelect'])) {
							$options[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
						} else {
							$options[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
						}
						$i++;
					}
				} else {
					foreach ($facet['list'] as $item) {
						$options[$key]['facets'][$i]['value'] = $item['value'];
						$options[$key]['facets'][$i]['display'] = $item['display'];
						$options[$key]['facets'][$i]['field'] = $facet['field_name'];
						$options[$key]['facets'][$i]['count'] = $item['count'];
						$options[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
						if (isset($item['multiSelect'])) {
							$options[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
						} else {
							$options[$key]['facets'][$i]['multiSelect'] = false;
							if(isset($facet['multiSelect'])) {
								$options[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
							}
						}
						$i++;
					}
				}

				if (array_key_exists($facet['label'], $appliedFacets)) {
					$key = translate(['text' => $facet['label'], 'isPublicFacing' => true]);
					$label = $facet['label'];
					$appliedFacetForKey = $options[$key]['facets'] ?? [];
					foreach($appliedFacets[$label] as $appliedFacet) {
						$id = array_search($appliedFacet['display'], array_column($appliedFacetForKey, 'display'));
						if (!$id && $id !== 0) {
							//$facet = $appliedFacets[$facet['label']][0];
							$options[$key]['facets'][$i]['value'] = $appliedFacet['value'];
							$options[$key]['facets'][$i]['display'] = $appliedFacet['display'];
							$options[$key]['facets'][$i]['field'] = $appliedFacet['field'];
							$options[$key]['facets'][$i]['count'] = null;
							$options[$key]['facets'][$i]['isApplied'] = true;
							$options[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
							$i++;
						}
					}
				}
			}


			$results['items'] = $items;
			$results['options'] = $options;
			$results['success'] = true;
			$results['time'] = round($searchObject->getTotalSpeed(), 2);
			$results['title'] = translate([
				'text' => 'Catalog Search',
				'isPublicFacing' => true,
			]);
			$results['message'] = translate([
				'text' => "Your search returned %1% results",
				1 => $results['count'],
				'isPublicFacing' => true,
			]);
			$timer->logTime('load result records');
			if ($results['page_current'] == $results['page_total']) {
				$results['message'] = "end of results";
			}
			if($searchType == 'saved_search') {
				$results['savedSearchId'] = $_REQUEST['searchId'];
			}
		}
		if (empty($results['items'])) {
			if (isset($_REQUEST['page']) && $_REQUEST['page'] != 1) {
				$results['message'] = "end of results";
			}
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	public function restoreSearch($id, $processSearch = true) {
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$search = new SearchEntry();
		$search->id = $id;
		if ($search->find(true)) {
			$minSO = unserialize($search->search_object);
			$storedSearch = SearchObjectFactory::deminify($minSO, $search);
			$searchObj = $storedSearch->restoreSavedSearch($id, false, true);
			if ($searchObj) {
				if ($processSearch) {
					$searchObj->processSearch(false, true);
				}
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
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'A valid search id not provided',
			];
		}
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$id = $_REQUEST['id'];
		$search = new SearchEntry();
		$search->id = $id;
		if ($search->find(true)) {
			$minSO = unserialize($search->search_object);
			$searchObj = SearchObjectFactory::deminify($minSO, $search);
			$sortList = $searchObj->getSortList();
			$items = [];
			$i = 0;
			$key = translate([
				'text' => 'Sort By',
				'isPublicFacing' => true,
			]);
			$items['key'] = 0;
			$items['label'] = $key;
			$items['field'] = 'sort_by';
			$items['hasApplied'] = true;
			$items['multiSelect'] = false;
			foreach ($sortList as $value => $sort) {
				$items['facets'][$i]['value'] = $value;
				$items['facets'][$i]['display'] = translate([
					'text' => $sort['desc'],
					'isPublicFacing' => true,
				]);
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
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'A valid search id not provided',
			];
		}
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$id = $_REQUEST['id'];
		$searchObj = $this->restoreSearch($id);
		if ($searchObj) {
			global $interface;
			$topFacetSet = $interface->getVariable('topFacetSet');
			$formatCategories = $topFacetSet['format_category'];
			$items = [];
			$i = 0;
			$items['key'] = 0;
			$items['label'] = translate(['text' => $formatCategories['label'], 'isPublicFacing' => true]);;
			$items['field'] = $formatCategories['field_name'];
			$items['hasApplied'] = $formatCategories['hasApplied'];
			$items['multiSelect'] = (bool)$formatCategories['multiSelect'];
			foreach ($formatCategories['list'] as $category) {
				$items['facets'][$i]['value'] = $category['value'];
				$items['facets'][$i]['display'] = translate(['text' => $category['display'], 'isPublicFacing' => true]);;
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
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'A valid search id not provided',
			];
		}
		$includeSortList = $_REQUEST['includeSortList'] ?? true;
		$id = $_REQUEST['id'];
		$searchObj = $this->restoreSearch($id);
		if ($searchObj) {
			global $interface;
			$topFacetSet = $interface->getVariable('topFacetSet');
			$facets = $interface->getVariable('sideFacetSet');
			//$facets = $searchObj->getFacetList();
			$appliedFacets = $searchObj->getFilterList();

			$items = [];
			$index = 0;

			$availabilityToggle = $topFacetSet['availability_toggle'];
			if($availabilityToggle) {
				$key = translate([
					'text' => $availabilityToggle['label'],
					'isPublicFacing' => true
				]);
				$items[$key]['key'] = -1;
				$items[$key]['label'] = $key;
				$items[$key]['field'] = $availabilityToggle['field_name'];
				$items[$key]['hasApplied'] = $availabilityToggle['hasApplied'];
				$items[$key]['multiSelect'] = $availabilityToggle['multiSelect'];

				$i = 0;
				foreach ($availabilityToggle['list'] as $item) {
					$items[$key]['facets'][$i]['value'] = $item['value'];
					$items[$key]['facets'][$i]['display'] = translate([
						'text' => $item['display'],
						'isPublicFacing' => true
					]);
					$items[$key]['facets'][$i]['field'] = $availabilityToggle['field_name'];
					$items[$key]['facets'][$i]['count'] = $item['count'];
					$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
					if (isset($item['multiSelect'])) {
						$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
					} else {
						$items[$key]['facets'][$i]['multiSelect'] = (bool)$items[$key]['multiSelect'];
					}
					$i++;
				}
			}

			if ($includeSortList) {
				$sortList = $searchObj->getSortList();
				$i = 0;
				$key = translate([
					'text' => 'Sort By',
					'isPublicFacing' => true,
				]);
				$items[$key]['key'] = 0;
				$items[$key]['label'] = $key;
				$items[$key]['field'] = 'sort_by';
				$items[$key]['hasApplied'] = true;
				$items[$key]['multiSelect'] = false;
				foreach ($sortList as $value => $sort) {
					$items[$key]['facets'][$i]['value'] = $value;
					$items[$key]['facets'][$i]['display'] = translate([
						'text' => $sort['desc'],
						'isPublicFacing' => true,
					]);
					$items[$key]['facets'][$i]['field'] = 'sort_by';
					$items[$key]['facets'][$i]['count'] = 0;
					$items[$key]['facets'][$i]['isApplied'] = $sort['selected'];
					$items[$key]['facets'][$i]['multiSelect'] = false;
					$i++;
				}
			}
			foreach ($facets as $facet) {
				$index++;
				$i = 0;
				$key = translate(['text' => $facet['label'], 'isPublicFacing' => true]);
				$items[$key]['key'] = $index;
				$items[$key]['label'] = $key;
				$items[$key]['field'] = $facet['field_name'];
				$items[$key]['hasApplied'] = $facet['hasApplied'];
				$items[$key]['multiSelect'] = (bool)$facet['multiSelect'];
				if (isset($facet['sortedList'])) {
					foreach ($facet['sortedList'] as $item) {
						$items[$key]['facets'][$i]['value'] = $item['value'];
						$items[$key]['facets'][$i]['display'] = translate(['text' => $item['display'], 'isPublicFacing' => true]);;
						$items[$key]['facets'][$i]['field'] = $facet['field_name'];
						$items[$key]['facets'][$i]['count'] = $item['count'];
						$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
						if (isset($item['multiSelect'])) {
							$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
						} else {
							$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
						}
						$i++;
					}
				} else {
					foreach ($facet['list'] as $item) {
						$items[$key]['facets'][$i]['value'] = $item['value'];
						$items[$key]['facets'][$i]['display'] = translate(['text' => $item['display'], 'isPublicFacing' => true]);
						$items[$key]['facets'][$i]['field'] = $facet['field_name'];
						$items[$key]['facets'][$i]['count'] = $item['count'];
						$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
						if (isset($item['multiSelect'])) {
							$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
						} else {
							$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
						}
						$i++;
					}
				}

				if (array_key_exists($facet['label'], $appliedFacets)) {
					$key = translate(['text' => $facet['label'], 'isPublicFacing' => true]);
					$label = $facet['label'];
					foreach($appliedFacets[$label] as $appliedFacet) {
						if (!in_array($appliedFacet['display'], $items[$key]['facets'])) {
							//$facet = $appliedFacets[$facet['label']][0];
							$items[$key]['facets'][$i]['value'] = $appliedFacet['value'];
							$items[$key]['facets'][$i]['display'] = translate([
								'text' => $appliedFacet['display'],
								'isPublicFacing' => true
							]);
							$items[$key]['facets'][$i]['field'] = $appliedFacet['field'];
							$items[$key]['facets'][$i]['count'] = null;
							$items[$key]['facets'][$i]['isApplied'] = true;
							$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
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
	function searchAvailableFacets() {
		$results = [
			'success' => false,
			'message' => 'Unable to restore search from id',
		];
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'Search id not provided',
			];
		}
		if (empty($_REQUEST['facet'])) {
			return [
				'success' => false,
				'message' => 'Facet name not provided',
			];
		}
		$id = $_REQUEST['id'];
		$facet = $_REQUEST['facet'];
		$term = $_REQUEST['term'] ?? '';
		$searchObj = $this->restoreSearch($id);
		if ($searchObj) {
			$items = [];
			$index = 0;
			if(array_key_exists($facet, $searchObj->getFacetConfig())) {
				/** @var SearchObject_SolrSearcher $newSearch */
				$newSearch = clone $searchObj;
				$newSearch->addFacetSearch($facet, $term);
				$newSearchResult = $newSearch->processSearch(false, true);
				$facetConfig = $newSearch->getFacetConfig()[$facet];
				if (is_object($facetConfig)) {
					$facetTitle = $facetConfig->displayName;
					$facetTitlePlural = $facetConfig->displayNamePlural;
					$isMultiSelect = $facetConfig->multiSelect;
				} else {
					$facetTitle = $facet;
					$facetTitlePlural = $facet;
					$isMultiSelect = false;
				}

				$appliedFacets = $searchObj->getFilterList();
				$appliedFacetValues = [];
				if (array_key_exists($facetTitle, $appliedFacets)) {
					$appliedFacetValues = $appliedFacets[$facetTitle];
					asort($appliedFacetValues);
				}

				$allFacets = $newSearch->getFacetList();
				if (isset($allFacets[$facet])) {
					$facet = $allFacets[$facet];
					asort($facet['list']);
					$index++;
					$i = 0;
					if ($facet['field_name'] == 'availability_toggle') {
						$availabilityToggle = $topFacetSet['availability_toggle'];
						$key = translate(['text' => $availabilityToggle['label'], 'isPublicFacing' => true]);
						$items[$key]['key'] = $index;
						$items[$key]['label'] = $key;
						$items[$key]['field'] = $availabilityToggle['field_name'];
						$items[$key]['hasApplied'] = $availabilityToggle['hasApplied'];
						$items[$key]['multiSelect'] = (bool)$availabilityToggle['multiSelect'];
						foreach ($availabilityToggle['list'] as $item) {
							$items[$key]['facets'][$i]['value'] = $item['value'];
							$items[$key]['facets'][$i]['display'] = translate(['text' => $item['display'], 'isPublicFacing' => true]);
							$items[$key]['facets'][$i]['field'] = $availabilityToggle['field_name'];
							$items[$key]['facets'][$i]['count'] = $item['count'];
							$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
							if (isset($item['multiSelect'])) {
								$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
							} else {
								$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
							}
							$i++;
						}
					} else {
						$key = translate(['text' => $facet['label'], 'isPublicFacing' => true]);
						$items[$key]['key'] = $index;
						$items[$key]['label'] = $key;
						$items[$key]['field'] = $facet['field_name'];
						$items[$key]['hasApplied'] = $facet['hasApplied'];
						$items[$key]['multiSelect'] = (bool)$facet['multiSelect'];
						if (isset($facet['sortedList'])) {
							foreach ($facet['sortedList'] as $item) {
								$items[$key]['facets'][$i]['value'] = $item['value'];
								$items[$key]['facets'][$i]['display'] = translate(['text' => $item['display'], 'isPublicFacing' => true]);;
								$items[$key]['facets'][$i]['field'] = $facet['field_name'];
								$items[$key]['facets'][$i]['count'] = $item['count'];
								$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
								if (isset($item['multiSelect'])) {
									$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
								} else {
									$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
								}
								$i++;
							}
						} else {
							foreach ($facet['list'] as $item) {
								$items[$key]['facets'][$i]['value'] = $item['value'];
								$items[$key]['facets'][$i]['display'] = translate(['text' => $item['display'], 'isPublicFacing' => true]);;
								$items[$key]['facets'][$i]['field'] = $facet['field_name'];
								$items[$key]['facets'][$i]['count'] = $item['count'];
								$items[$key]['facets'][$i]['isApplied'] = $item['isApplied'];
								if (isset($item['multiSelect'])) {
									$items[$key]['facets'][$i]['multiSelect'] = (bool)$item['multiSelect'];
								} else {
									$items[$key]['facets'][$i]['multiSelect'] = (bool)$facet['multiSelect'];
								}
								$i++;
							}
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
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'A valid search id not provided',
			];
		}
		$includeSort = $_REQUEST['includeSort'] ?? true;
		$id = $_REQUEST['id'];
		$searchObj = $this->restoreSearch($id);
		if ($searchObj) {
			global $interface;
			$facets = $interface->getVariable('sideFacetSet');
			//$facets = $searchObj->getFacetList();
			$items = array_keys($facets);
			if ($includeSort) {
				$items[] = 'sort_by';
			}

			$items[] = 'availability_toggle';

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
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'A valid search id not provided',
			];
		}
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$id = $_REQUEST['id'];
		$search = new SearchEntry();
		$search->id = $id;
		if ($search->find(true)) {
			$minSO = unserialize($search->search_object);
			$searchObj = SearchObjectFactory::deminify($minSO, $search);
			$filters = $searchObj->getFilterList();
			$items = [];

			$includeSort = $_REQUEST['includeSort'] ?? true;
			if ($includeSort) {
				$list = $searchObj->getSortList();
				$sort = [];
				foreach ($list as $index => $item) {
					if ($item['selected'] == true) {
						$sort = $item;
						$sort['value'] = $index;
						break;
					}
				}
				$i = 0;
				$key = translate([
					'text' => 'Sort By',
					'isPublicFacing' => true,
				]);
				$items[$key][$i]['value'] = $sort['value'];
				$items[$key][$i]['display'] = translate(['text' => $sort['desc'], 'isPublicFacing' => true]);
				$items[$key][$i]['field'] = 'sort_by';
				$items[$key][$i]['count'] = 0;
				$items[$key][$i]['isApplied'] = true;
			}

			foreach ($filters as $key => $filter) {
				$i = 0;
				foreach ($filter as $item) {
					if($item['field'] == 'availability_toggle') {
						$searchLibrary = Library::getSearchLibrary(null);
						$searchLocation = Location::getSearchLocation(null);
						if ($searchLocation) {
							$superScopeLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
							$localLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
							$localLabel = str_ireplace('{display name}', $searchLocation->displayName, $localLabel);
							$availableLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
							$availableLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableLabel);
							$availableOnlineLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
							$availableOnlineLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableOnlineLabel);
							$availabilityToggleValue = $searchLocation->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
						} else {
							$superScopeLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
							$localLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
							$localLabel = str_ireplace('{display name}', $searchLibrary->displayName, $localLabel);
							$availableLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
							$availableLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableLabel);
							$availableOnlineLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
							$availableOnlineLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableOnlineLabel);
							$availabilityToggleValue = $searchLibrary->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
						}

						if($item['value'] == 'global') {
							$items[$key][$i]['display'] = translate(['text' => $superScopeLabel, 'isPublicFacing' => true]);
						} else if ($item['value'] == 'local') {
							$items[$key][$i]['display'] = translate(['text' => $localLabel, 'isPublicFacing' => true]);
						} else if ($item['value'] == 'available') {
							$items[$key][$i]['display'] = translate(['text' => $localLabel, 'isPublicFacing' => true]);
						} else if ($item['value'] == 'available_online') {
							$items[$key][$i]['display'] = translate(['text' => $availableOnlineLabel, 'isPublicFacing' => true]);
						} else {
							$items[$key][$i]['display'] = translate(['text' => $item['display'], 'isPublicFacing' => true]);
						}
					} else {
						$items[$key][$i]['display'] = translate(['text' => $item['display'], 'isPublicFacing' => true]);
					}
					$items[$key][$i]['value'] = $item['value'];
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
	function getSearchSources() {
		global $library;
		global $location;

		require_once(ROOT_DIR . '/Drivers/marmot_inc/SearchSources.php');
		$searchSources = new SearchSources();
		[
			$enableCombinedResults,
			$showCombinedResultsFirst,
			$combinedResultsName,
		] = $searchSources::getCombinedSearchSetupParameters($location, $library);

		$validSearchSources = $searchSources->getSearchSources();

		return [
			'success' => true,
			'sources' => $validSearchSources
		];
	}

	/** @noinspection PhpUnused */
	function getSearchIndexes() {
		global $library;
		global $location;

		require_once(ROOT_DIR . '/Drivers/marmot_inc/SearchSources.php');
		$searchSources = new SearchSources();
		[
			$enableCombinedResults,
			$showCombinedResultsFirst,
			$combinedResultsName,
		] = $searchSources::getCombinedSearchSetupParameters($location, $library);

		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		$validSearchSources = $searchSources->getSearchSources();
		$activeSearchSource = 'catalog';
		if (isset($_REQUEST['searchSource'])) {
			$activeSearchSource = $_REQUEST['searchSource'];
		}
		$activeSearchObject = SearchSources::getSearcherForSource($activeSearchSource);
		if (!array_key_exists($activeSearchSource, $validSearchSources)) {
			$activeSearchSource = array_key_first($validSearchSources);
		}
		$activeSearchObject = SearchSources::getSearcherForSource($activeSearchSource);
		$searchIndexes = SearchSources::getSearchIndexesForSource($activeSearchObject, $activeSearchSource);

		return [
			'success' => true,
			'indexes' => [
				$activeSearchSource => $searchIndexes
			]
		];
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
		foreach ($obj as $facet) {
			$facets[$i]['value'] = $facet->facetName;
			$facets[$i]['display'] = translate(['text' => $facet->displayName, 'isPublicFacing' => true]);
			$facets[$i]['field'] = $facet->facetName;
			$facets[$i]['count'] = 0;
			$facets[$i]['isApplied'] = false;
			$facets[$i]['multiSelect'] = (bool)$facet->multiSelect;
			$i++;
		}

		$searchLibrary = Library::getSearchLibrary(null);
		$searchLocation = Location::getSearchLocation(null);
		if ($searchLocation) {
			$superScopeLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
			$localLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
			$localLabel = str_ireplace('{display name}', $searchLocation->displayName, $localLabel);
			$availableLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
			$availableLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableLabel);
			$availableOnlineLabel = $searchLocation->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
			$availableOnlineLabel = str_ireplace('{display name}', $searchLocation->displayName, $availableOnlineLabel);
			$availabilityToggleValue = $searchLocation->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
		} else {
			$superScopeLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelSuperScope;
			$localLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelLocal;
			$localLabel = str_ireplace('{display name}', $searchLibrary->displayName, $localLabel);
			$availableLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailable;
			$availableLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableLabel);
			$availableOnlineLabel = $searchLibrary->getGroupedWorkDisplaySettings()->availabilityToggleLabelAvailableOnline;
			$availableOnlineLabel = str_ireplace('{display name}', $searchLibrary->displayName, $availableOnlineLabel);
			$availabilityToggleValue = $searchLibrary->getGroupedWorkDisplaySettings()->defaultAvailabilityToggle;
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
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'A valid search id not provided',
			];
		}
		if (empty($_REQUEST['cluster'])) {
			return [
				'success' => false,
				'message' => 'A valid cluster field_name not provided',
			];
		}
		$id = $_REQUEST['id'];
		$key = $_REQUEST['cluster'];
		$searchObj = $this->restoreSearch($id);
		if ($searchObj) {
			$facets = $searchObj->getFacetList();
			$cluster = $facets[$key] ?? [];
			$results = [
				'success' => true,
				'id' => $id,
				'time' => round($searchObj->getQuerySpeed(), 2),
				'field' => $cluster['field_name'],
				'display' => translate(['text' => $cluster['label'], 'isPublicFacing' => true]),
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
		if (empty($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'A valid search id not provided',
			];
		}
		$id = $_REQUEST['id'];
		$term = $_REQUEST['term'];
		$searchObj = $this->restoreSearch($id);
		if ($searchObj) {
			// do something with the term
		}
		return $results;
	}

	function checkIfLiDA() {
		foreach (getallheaders() as $name => $value) {
			if ($name == 'User-Agent' || $name == 'user-agent') {
				if (strpos($value, "Aspen LiDA") !== false) {
					return true;
				}
			}
		}
		return false;
	}
}