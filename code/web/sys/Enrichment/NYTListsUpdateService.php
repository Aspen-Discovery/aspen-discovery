<?php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../bootstrap_aspen.php';

require_once ROOT_DIR . '/services/API/ListAPI.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
require_once ROOT_DIR . '/sys/NYTApi.php';
require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';

/**
 * Class NYTListsUpdateService
 *
 * Handles updating New York Times best seller lists
 * Can be used both from command line scripts and directly from PHP
 */
class NYTListsUpdateService {
	/** @var NYTUpdateLogEntry */
	private $nytUpdateLog;

	/** @var bool */
	private $success = false;

	/** @var string */
	private $message = '';

	/** @var int|null */
	private $logId = null;

	/**
	 * Check if this update has been requested to halt
	 *
	 * @return bool True if a halt has been requested
	 */
	private function isHaltRequested() {
		if ($this->nytUpdateLog) {
			// Reload the log entry to check for halt flag
			$logEntry = new NYTUpdateLogEntry();
			$logEntry->id = $this->logId;
			if ($logEntry->find(true)) {
				return ($logEntry->haltRequested == 1);
			}
		}
		return false;
	}

	/**
	 * Run the NYT lists update process
	 *
	 * @return array Result with success status, message, and log ID
	 */
	public function update(): array
	{
		try {
			// Create a NYTUpdateLogEntry
			$this->nytUpdateLog = new NYTUpdateLogEntry();
			$this->nytUpdateLog->startTime = time();
			$this->nytUpdateLog->insert();
			$this->logId = $this->nytUpdateLog->id;
			$this->nytUpdateLog->addNote("Starting NYT list update process.");

			// Check for halt requests periodically throughout the process
			set_time_limit(0);

			global $configArray;
			$nytSettings = new NewYorkTimesSetting();
			if (!$nytSettings->find(true)) {
				$this->nytUpdateLog->addError("No settings found, not updating lists.");
				$this->message = "No settings found, not updating lists.";
				return $this->getResult();
			} else {
				$this->nytUpdateLog->addExtensiveNote("Found NYT settings, API key: " . substr($nytSettings->booksApiKey, 0, 4) . "...");

				// Check if we're forcing a full update.
				$forceFullUpdate = $nytSettings->runFullUpdate;
				if ($forceFullUpdate) {
					$this->nytUpdateLog->addNote("Force Full Update enabled; all lists will be completely rebuilt regardless of last modified date.");
				}

				// Check if extensive logging is enabled.
				$extensiveLoggingEnabled = $nytSettings->enableExtensiveLogging;
				if ($extensiveLoggingEnabled) {
					$this->nytUpdateLog->addNote("Extensive Logging enabled; more detailed logs will be generated during this update.");
				}

				// Pass the log entry to the API, so we can update it there.
				$nyt_api = new NYTApi($nytSettings->booksApiKey, $this->nytUpdateLog);

				$retry = true;
				$numTries = 0;
				$availableLists = null;
				while ($retry == true) {
					// Check if a halt has been requested.
					if ($this->isHaltRequested()) {
						$this->nytUpdateLog->addNote("Update halted by user request, stopping processing.");
						$this->message = "Update halted by user request";
						$this->nytUpdateLog->endTime = time();
						$this->nytUpdateLog->update();
						return $this->getResult();
					}

					$retry = false;
					$numTries++;
					$this->nytUpdateLog->addExtensiveNote("Retrieving available NYT lists (attempt $numTries).");
					// Get the raw response from the API with a list of all the names.
					try {
						$availableListsRaw = $nyt_api->get_list('names');
						//Convert into an object that can be processed
						$availableLists = json_decode($availableListsRaw);
						if (empty($availableLists->status) || $availableLists->status != "OK") {
							if (!empty($availableLists->fault)) {
								if (strpos($availableLists->fault->faultstring, 'quota violation')) {
									$retry = ($numTries <= 3);
									if ($retry) {
										$this->nytUpdateLog->addExtensiveNote("Hit quota limit, retrying after sleep (attempt $numTries)");
										sleep(rand(60, 300));
									} else {
										if ($this->nytUpdateLog != null) {
											$this->nytUpdateLog->addError("Did not get a good response from the API. {$availableLists->fault->faultstring}");
											$this->message = "API quota violation: {$availableLists->fault->faultstring}";
										}
									}
								} else {
									if ($this->nytUpdateLog != null) {
										$this->nytUpdateLog->addError("Did not get a good response from the API. {$availableLists->fault->faultstring}");
										$this->message = "API error: {$availableLists->fault->faultstring}";
									}
								}
							} else {
								if ($this->nytUpdateLog != null) {
									$this->nytUpdateLog->addError("Did not get a good response from the API");
									$this->message = "Invalid API response";
								}
							}
						} else {
							$this->nytUpdateLog->addNote("Successfully retrieved " . count($availableLists->results) . " available NYT lists");
						}
					} catch (Exception $e) {
						$this->nytUpdateLog->addError("Error retrieving lists from the API: " . $e->getMessage());
						$this->message = "Error retrieving lists: " . $e->getMessage();
						$availableLists = null;
						break;
					}
				}

				$listAPI = new ListAPI();

				if ($availableLists != null && isset($availableLists->results)) {
					$prevYear = date("Y-m-d", strtotime("-1 year"));
					$allListsNames = [];
					foreach ($availableLists->results as $availableList) {
						if ($availableList->newest_published_date > $prevYear) {
							$allListsNames[] = $availableList->list_name_encoded;
							$this->nytUpdateLog->addExtensiveNote("List '{$availableList->display_name}' (encoded: {$availableList->list_name_encoded}) is current with newest date: {$availableList->newest_published_date}.");
						} else {
							$this->nytUpdateLog->addExtensiveNote("Skipping list '{$availableList->display_name}' (encoded: {$availableList->list_name_encoded}) as it's older than one year. Newest date: {$availableList->newest_published_date}.");
						}
					}
					$this->nytUpdateLog->numLists = count($allListsNames);
					$this->nytUpdateLog->update();
					$this->nytUpdateLog->addNote("Processing " . count($allListsNames) . " NYT lists that are newer than $prevYear.");

					// Final check before starting list processing
					if ($this->isHaltRequested()) {
						$this->nytUpdateLog->addNote("Update halted by user request before list processing began.");
						$this->message = "Update halted by user request";
						$this->nytUpdateLog->endTime = time();
						$this->nytUpdateLog->update();
						return $this->getResult();
					}

					foreach ($allListsNames as $listName) {
						// Check if a halt has been requested
						if ($this->isHaltRequested()) {
							$this->nytUpdateLog->addNote("Update halted by user request, stopping processing.");
							break;
						}

						$this->nytUpdateLog->addExtensiveNote("Starting update for list: $listName");
						try {
							$result = $listAPI->createUserListFromNYT($listName, $this->nytUpdateLog, $forceFullUpdate);
							$this->nytUpdateLog->addNote("Finished update for list: $listName - Success: " . ($result['success'] ? 'Yes' : 'No') . ", Message: " . $result['message']);
						} catch (Exception $e) {
							$this->nytUpdateLog->addError("Error updating $listName " . $e->getMessage());
						}
						$this->nytUpdateLog->lastUpdate = time();
						$this->nytUpdateLog->update();

						// Check if a halt has been requested after processing each list
						if ($this->isHaltRequested()) {
							$this->nytUpdateLog->addNote("Update halted by user request - stopping processing");
							break;
						}

						//Make sure we don't hit our quota.  Wait between updates
						sleep(7);
					}

					// Set success flag
					$this->success = true;
					$this->message = "Successfully processed " . count($allListsNames) . " NYT lists";
				} else {
					$this->nytUpdateLog->addExtensiveError("No available lists found or invalid response structure.");
					$this->message = "No available lists found or invalid response structure.";
				}

				$nyt_api = null;

				// Reset the force full update flag if it was enabled
				if ($forceFullUpdate) {
					$nytSettings->runFullUpdate = 0;
					$nytSettings->update();
					$this->nytUpdateLog->addNote("Force Full Update setting has been reset.");
				}

				// Reset the extensive logging flag if it was enabled
				if ($extensiveLoggingEnabled) {
					$nytSettings->enableExtensiveLogging = 0;
					$nytSettings->update();
					$this->nytUpdateLog->addNote("Extensive Logging setting has been reset.");
				}
			}

			$this->nytUpdateLog->addNote("Finished updating lists.");
			$this->nytUpdateLog->endTime = time();
			$this->nytUpdateLog->update();

			$nytSettings->__destruct();
			$nytSettings = null;

			$this->nytUpdateLog->__destruct();
			$this->nytUpdateLog = null;

			global $aspen_db;
			$aspen_db = null;

		} catch (Exception $e) {
			// Log any uncaught exceptions
			if (isset($this->nytUpdateLog)) {
				$this->nytUpdateLog->addError("Uncaught exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
				$this->nytUpdateLog->endTime = time();
				$this->nytUpdateLog->update();
			} else {
				// If we couldn't even create the log entry, write to error_log
				error_log("NYT Update Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			}
			$this->message = "Exception: " . $e->getMessage();
			return $this->getResult();
		}

		return $this->getResult();
	}

	/**
	 * Get the result array for this update operation
	 *
	 * @return array Result with success status, message, and log ID
	 */
	private function getResult(): array
	{
		return [
			'success' => $this->success,
			'message' => $this->message,
			'logId' => $this->logId
		];
	}

	/**
	 * Check if an update is currently running
	 *
	 * @return array Information about the running update or null if none is running
	 */
	public static function isUpdateRunning(): array
	{
		$logEntry = new NYTUpdateLogEntry();
		$logEntry->whereAdd('endTime IS NULL'); // Look for unfinished updates
		$logEntry->whereAdd('startTime > ' . (time() - 3600)); // Started in the last hour
		$logEntry->orderBy('id DESC');
		$logEntry->limit(0, 1);

		$result = ['isRunning' => false];

		if ($logEntry->find(true)) {
			// Auto-cleanup for updates running too long.
			// If no activity for 5 minutes (300 seconds), consider it stalled
			$staleThreshold = 300;
			$lastActivity = $logEntry->lastUpdate ?? $logEntry->startTime;
			$timeSinceLastActivity = time() - $lastActivity;

			if ($timeSinceLastActivity > $staleThreshold) {
				$logEntry->addNote("Automatically halted stalled update - no activity for " .
					floor($timeSinceLastActivity / 60) . " minutes");
				$logEntry->endTime = time();
				$logEntry->update();
			} else {
				// Active update within the threshold
				$result = [
					'isRunning' => true,
					'logId' => $logEntry->id,
					'startTime' => $logEntry->startTime,
					'elapsedTime' => time() - $logEntry->startTime,
					'lastUpdate' => $logEntry->lastUpdate,
					'numLists' => $logEntry->numLists,
					'lastActivity' => $lastActivity,
					'timeSinceLastActivity' => $timeSinceLastActivity
				];
			}
		}

		return $result;
	}

	/**
	 * Attempt to halt a currently running update
	 *
	 * @param int $logId The ID of the update log entry to halt
	 * @return bool Whether the update was successfully halted
	 */
	public static function haltUpdate(int $logId): bool
	{
		require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';

		$logEntry = new NYTUpdateLogEntry();
		$logEntry->id = $logId;

		if ($logEntry->find(true)) {
			if ($logEntry->endTime === null) {
				// Set the halt flag instead of trying to kill processes
				$logEntry->haltRequested = 1;
				$logEntry->addExtensiveNote("Update halt requested by user via Greenhouse web interface.");
				$logEntry->update();

				return true;
			}
		}

		return false;
	}
}