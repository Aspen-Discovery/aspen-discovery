<?php /** @noinspection PhpMissingFieldTypeInspection */


class SystemVariables extends DataObject {
	public $__table = 'system_variables';
	public $id;
	public $errorEmail;
	public $searchErrorEmail;
	public $loadCoversFrom020z;
	public $currencyCode;
	public $runNightlyFullIndex;
	public $nightlyIndexTrigger;
	public $regroupAllRecordsDuringNightlyIndex;
	/** @noinspection PhpUnused */
	public $processEmptyGroupedWorks;
	public $allowableHtmlTags;
	public $allowHtmlInMarkdownFields;
	public $useHtmlEditorRatherThanMarkdown;
	public $enableGrapesEditor;
	/** @noinspection PhpUnused */
	public $storeRecordDetailsInSolr;
	public $storeRecordDetailsInDatabase;
	/** @noinspection PhpUnused */
	public $deletionCommitInterval;
	/** @noinspection PhpUnused */
	public $indexCommitInterval;
	/** @noinspection PhpUnused */
	public $solrThreadCount;
	/** @noinspection PhpUnused */
	public $solrQueueSize;
	/** @noinspection PhpUnused */
	public $waitAfterDeleteCommit;
	/** @noinspection PhpUnused */
	public $indexVersion;
	public $searchVersion;
	public $titleSearchBehavior;
	public $customGroupedWorkSearchSpecs; //Path to custom grouped work search specs YAML file or the YAML itself
	public $enableNovelistSeriesIntegration;
	public $greenhouseUrl;
	public $communityContentUrl;
	public $libraryToUseForPayments;
	public $solrConnectTimeout;
	public $solrQueryTimeout;
	public $spellcheckMaxCollationTries;
	public $catalogStatus;
	public $scheduledOfflineStart;
	public $scheduledOfflineEnd;
	public $scheduledEcontentAccess;
	public $offlineMessage;
	public $appScheme;
	public $enableBrandedApp;
	public $supportingCompany;
	public $googleBucket;
	public $trackIpAddresses;
	public $disableIpSpammyControl;
	public $monitorAntivirus;
	public $monitorWaitTime;
	public $useOriginalCoverUrls;
	public $lidaGitHubRepository;
	/** @noinspection PhpUnused */
	public $numBoundlessSettingsToProcessInParallel;
	/** @noinspection PhpUnused */
	public $numPalaceProjectIndexingThreads;
	/** @noinspection PhpUnused */
	public $removeTheWordSeriesFromEndOfSeries;
	public $disable_user_agent_logging;
	public $logFrequentCrons;
	public $hooplaVersion;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		require_once ROOT_DIR . '/services/Admin/CronRunner.php';
		$frequentJobs = Admin_CronRunner::getFrequentCronJobs();

		$objectStructure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'greenhouseUrl' => [
				'property' => 'greenhouseUrl',
				'type' => 'url',
				'label' => 'Greenhouse URL',
				'description' => 'URL of the Greenhouse for LiDA connections and system health reporting',
				'maxLength' => 128,
			],
			'communityContentUrl' => [
				'property' => 'communityContentUrl',
				'type' => 'url',
				'label' => 'Community Content URL',
				'description' => 'URL of the community content server',
				'maxLength' => 128,
			],
			'lidaGitHubRepository' => [
				'property' => 'lidaGitHubRepository',
				'type' => 'url',
				'label' => 'LiDA GitHub Repository URL',
				'description' => 'URL of the GitHub repository where Aspen LiDA is deployed from',
				'maxLength' => 255,
			],
			'errorEmail' => [
				'property' => 'errorEmail',
				'type' => 'text',
				'label' => 'Error Email Address',
				'description' => 'Email Address to send errors to',
				'maxLength' => 128,
			],
			'searchErrorEmail' => [
				'property' => 'searchErrorEmail',
				'type' => 'text',
				'label' => 'Search Error Email Address',
				'description' => 'Email Address to send errors to',
				'maxLength' => 128,
			],
			'googleBucket' => [
				'property' => 'googleBucket',
				'type' => 'text',
				'label' => 'Google Bucket',
				'description' => 'Google bucket to store backups',
				'maxLength' => 128,
			],
			'currencyCode' => [
				'property' => 'currencyCode',
				'type' => 'text',
				'suggestions' => [
					'USD',
					'CAD',
					'EUR',
					'GBP',
				],
				'label' => 'Currency Code',
				'description' => 'Currency code to use when formatting money',
				'required' => true,
				'maxLength' => 3,
				'default' => 'USD',
			],
			'indexingSection' => [
				'property' => 'indexingSection',
				'type' => 'section',
				'label' => 'Indexing Settings',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'runNightlyFullIndex' => [
						'property' => 'runNightlyFullIndex',
						'type' => 'checkbox',
						'label' => 'Run full index tonight',
						'description' => 'Whether or not a full index should be run in the middle of the night',
						'default' => false,
					],
					'regroupAllRecordsDuringNightlyIndex' => [
						'property' => 'regroupAllRecordsDuringNightlyIndex',
						'type' => 'checkbox',
						'label' => 'Regroup all records during nightly index',
						'description' => 'Whether or not all records should be regrouped during the nightly index',
						'default' => false,
					],
					'processEmptyGroupedWorks' => [
						'property' => 'processEmptyGroupedWorks',
						'type' => 'checkbox',
						'label' => 'Process Empty Grouped Works',
						'description' => 'Whether or not grouped works with no records should be processed during the nightly index',
						'default' => false,
					],
					'storeRecordDetailsInSolr' => [
						'property' => 'storeRecordDetailsInSolr',
						'type' => 'checkbox',
						'label' => 'Store Record Details In Solr',
						'description' => 'Whether or not a record details should be stored in solr (for backwards compatibility with 21.07)',
						'default' => false,
					],
					'storeRecordDetailsInDatabase' => [
						'property' => 'storeRecordDetailsInDatabase',
						'type' => 'checkbox',
						'label' => 'Store Record Details in Database',
						'description' => 'Whether or not a record details should be stored in the database',
						'default' => true,
					],
					'indexVersion' => [
						'property' => 'indexVersion',
						'type' => 'enum',
						'values' => [
							1 => 'Version 1 (No edition information)',
							2 => 'Version 2 (Edition information)',
						],
						'label' => 'Grouped Work Indexing Version',
						'description' => 'The Solr Core Version to index with.  In 22.06 and above this should be version 2 in most cases.',
						'required' => true,
						'default' => 2,
					],
					'searchVersion' => [
						'property' => 'searchVersion',
						'type' => 'enum',
						'values' => [
							1 => 'Version 1 (No edition information)',
							2 => 'Version 2 (Edition information)',
						],
						'label' => 'Grouped Work Search Version',
						'description' => 'The Solr Core Version to search with.  In 22.06 and above this should be version 2 in most cases.',
						'required' => true,
						'default' => 2,
					],
					'titleSearchBehavior' => [
						'property' => 'titleSearchBehavior',
						'label' => 'Title Search Behavior',
						'type' => 'enum',
						'values' => [
							1 => 'Exclude Alternate Titles',
							2 => 'Include Alternate Titles',
						]
					],
					'customGroupedWorkSearchSpecs' => [
						'property' => 'customGroupedWorkSearchSpecs',
						'type' => 'textarea',
						'label' => 'Custom Grouped Work Search Specs',
						'description' => 'Path to custom grouped work search specs YAML file (e.g., /data/aspen-discovery/custom/groupedWorkSearchSpecs.yaml). Overrides default catalog search field configuration. Leave empty to use default search specs. If you do not have access to the server you can also put the yaml directly into this field instead.',
						'hideInLists' => true,
						//'size' => 100,
						'warning' => 'Warning: Adding a custom file here can cause searches to fail, and can have a large impact on the relevancy of results. Larger sites may find a performance boost, but this file should only be provided by a trusted source.',
					],
					'loadCoversFrom020z' => [
						'property' => 'loadCoversFrom020z',
						'type' => 'checkbox',
						'label' => 'Load covers from cancelled & invalid ISBNs (020$z)',
						'description' => 'Whether or not covers can be loaded from the 020z',
						'default' => false,
					],
					'deletionCommitInterval' => [
						'property' => 'deletionCommitInterval',
						'type' => 'integer',
						'label' => 'Deletion Commit Interval (# of records)',
						'description' => 'Based on this setting, Aspen will call a solr commit after the specified number of records are marked for deletion',
						'required' => true,
						'default' => 1000,
						'min' => 250,
					],
					'indexCommitInterval' => [
						'property' => 'indexCommitInterval',
						'type' => 'integer',
						'label' => 'Indexing Commit Interval (# of records)',
						'description' => 'Based on this setting, Aspen will call a solr commit after the specified number of records are processed',
						'required' => true,
						'default' => 10000,
						'min' => 10000,
					],
					'solrThreadCount' => [
						'property' => 'solrThreadCount',
						'type' => 'integer',
						'label' => 'Solr Thread Count',
						'description' => 'The number of solr threads to use while indexing. Servers with more CPU can handle more threads.',
						'default' => 1,
						'min' => 1,
						'max' => 4,
					],
					'solrQueueSize' => [
						'property' => 'solrQueueSize',
						'type' => 'integer',
						'label' => 'Solr Queue Size',
						'description' => 'The number of documents that are added to the solr queue. This is based on memory as well as processors and document size to ensure too many documents are not loaded causing a timeout.',
						'default' => 25,
						'min' => 25,
						'max' => 1000,
					],
					'waitAfterDeleteCommit' => [
						'property' => 'waitAfterDeleteCommit',
						'type' => 'checkbox',
						'label' => 'Wait after delete commit',
						'description' => 'Whether or not to wait for Solr to finish processing the commit before deleting more records',
						'default' => false,
					],
					'numBoundlessSettingsToProcessInParallel' => [
						'property' => 'numBoundlessSettingsToProcessInParallel',
						'type' => 'integer',
						'label' => 'Number of Boundless Settings to process in parallel',
						'description' => 'Allows multiple Boundless Settings to be processed in parallel to improve the speed of indexing, but this must be balanced against the performance of your server.',
						'default' => 1,
					],
					'numPalaceProjectIndexingThreads' => [
						'property' => 'numPalaceProjectIndexingThreads',
						'type' => 'integer',
						'label' => 'Number of Palace Project Settings to process in parallel',
						'description' => 'Allows multiple Palace Project Settings to be processed in parallel to improve the speed of indexing, but this must be balanced against the performance of your server.',
						'default' => 1,
					],
					'removeTheWordSeriesFromEndOfSeries' => [
						'property' => 'removeTheWordSeriesFromEndOfSeries',
						'type' => 'checkbox',
						'label' => 'Remove the word "series" from the end of series',
						'description' => 'Whether to remove the word "series" from the end of series names',
						'default' => true,
					],
					'hooplaVersion' => [
						'property' => 'hooplaVersion',
						'type' => 'enum',
						'values' => [
							1 => 'Version 1 (Old Integration)',
							2 => 'Version 2 (New Integration)',
						],
						'label' => 'Hoopla Exporter Version',
						'description' => 'The version of Hoopla Exporter to use, version 2 has the new endpoint',
						'note' => 'If you switch to Version 2, run DB Maintenance before nightly reindexer runs. Perform the switch outside business hours. Once the database updates are complete, you can NOT switch back to Version 1.',
						'default' => 1,
						'required' => true,
					],
				],
			],

			'enableNovelistSeriesIntegration' => [
				'property' => 'enableNovelistSeriesIntegration',
				'type' => 'checkbox',
				'label' => 'Enable NoveList Series Integration',
				'description' => 'Whether NoveList series data is used within Aspen',
				'default' => true,
				'forcesReindex' => true,
			],
			'webBuilderSection' => [
				'property' => 'webBuilderSection',
				'type' => 'section',
				'label' => 'Web Builder Settings',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'allowableHtmlTags' => [
						'property' => 'allowableHtmlTags',
						'type' => 'text',
						'label' => 'Allowable HTML Tags',
						'description' => 'HTML Tags to allow in HTML and Markdown fields',
						'maxLength' => 512,
						'default' => 'p|em|i|strong|b|span|style|a|table|ul|ol|li|h1|h2|h3|h4|h5|h6|pre|code|hr|table|tbody|tr|th|td|caption|img|br|div|span',
						'hideInLists' => true,
					],
					'allowHtmlInMarkdownFields' => [
						'property' => 'allowHtmlInMarkdownFields',
						'type' => 'checkbox',
						'label' => 'Allow HTML in Markdown fields',
						'description' => 'Whether administrators can add HTML to a Markdown field, if disabled, all tags will be stripped',
						'default' => true,
					],
					'useHtmlEditorRatherThanMarkdown' => [
						'property' => 'useHtmlEditorRatherThanMarkdown',
						'type' => 'checkbox',
						'label' => 'Use HTML Editor rather than Markdown',
						'description' => 'Changes all Markdown fields to HTML fields',
						'default' => true,
					],
					'enableGrapesEditor' => [
						'property' => 'enableGrapesEditor',
						'type' => 'checkbox',
						'label' => 'Enable Grapes Editor',
						'description' => 'Allows the Grapes Editor to be used within Aspen',
						'default' => true,
					],
				],
			],
			'libraryToUseForPayments' => [
				'property' => 'libraryToUseForPayments',
				'type' => 'enum',
				'values' => [
					0 => 'Patron Home Library',
					1 => 'Active Catalog',
				],
				'label' => 'Library to use for fine payments',
				'description' => 'What library settings should be used when making fine payments',
				'default' => 0,
			],
			'solrConnectTimeout' => [
				'property' => 'solrConnectTimeout',
				'type' => 'integer',
				'label' => 'Solr Connect Timeout in seconds',
				'required' => true,
				'default' => 2,
				'min' => 1,
			],
			'solrQueryTimeout' => [
				'property' => 'solrQueryTimeout',
				'type' => 'integer',
				'label' => 'Solr Query Timeout in seconds',
				'required' => true,
				'default' => 10,
				'min' => 1,
			],
			'spellcheckMaxCollationTries' => [
				'property' => 'spellcheckMaxCollationTries',
				'type' => 'integer',
				'label' => 'Spellcheck Max Collation Tries',
				'description' => 'Maximum number of collation attempts for spellcheck queries (lower values improve performance)',
				'required' => true,
				'default' => 25,
				'min' => 1,
				'max' => 50,
			],
			'offlineModeSection' => [
				'property' => 'offlineModeSection',
				'type' => 'section',
				'label' => 'Catalog Online/Offline',
				'hideInLists' => true,
				'expandByDefault' => false,
				'properties' => [
					'catalogStatus' => [
						'property' => 'catalogStatus',
						'type' => 'enum',
						'values' => [
							0 => 'Catalog Online',
							1 => 'Catalog Offline, no login allowed',
							2 => 'Catalog Offline, login allowed with eContent active',
							],
						'label' => 'Catalog Online/Offline',
						'description' => 'Allows Aspen to be placed in offline mode for use during migrations and upgrade processes',
						'default' => 0,
					],
					'scheduledOfflineStart' => [
						'property' => 'scheduledOfflineStart',
						'type' => 'timestamp',
						'label' => 'Schedule Offline Start',
						'description' => 'Schedule a time to start the catalog offline mode.',
					],
					'scheduledOfflineEnd' => [
						'property' => 'scheduledOfflineEnd',
						'type' => 'timestamp',
						'label' => 'Schedule Offline End',
						'description' => 'Schedule a time to end the catalog offline mode.',
					],
					'scheduledEcontentAccess' => [
						'property' => 'scheduledEcontentAccess',
						'type' => 'checkbox',
						'label' => 'Allow Login with eContent Active for Scheduled Offline Mode',
					],
					'offlineMessage' => [
						'property' => 'offlineMessage',
						'type' => 'html',
						'label' => 'Offline Message',
						'description' => 'A message to be displayed while Aspen is offline.',
						'default' => 'The catalog is down for maintenance, please check back later.',
						'hideInLists' => true,
					],
				],
			],
			'appScheme' => [
				'property' => 'appScheme',
				'type' => 'text',
				'label' => 'App Scheme',
				'description' => 'Scheme used for creating deep links into the app',
			],
			'enableBrandedApp' => [
				'property' => 'enableBrandedApp',
				'type' => 'checkbox',
				'label' => 'Enable Branded App Settings',
				'description' => 'Whether or not the library can configure branded Aspen LiDA',
				'default' => false,
			],
			'supportingCompany' => [
				'property' => 'supportingCompany',
				'type' => 'text',
				'label' => 'Support Company',
				'description' => 'Sets supporting company name in footer',
				'default' => '',
			],
			'ipAddressesSection' => [
				'property' => 'ipAddressesSection',
				'type' => 'section',
				'label' => 'IP Addresses',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [
					'trackIpAddresses' => [
						'property' => 'trackIpAddresses',
						'type' => 'checkbox',
						'label' => 'Track IP Addresses',
						'description' => 'Determine if IP Addresses should be tracked for each page view',
						'default' => false,
					],
					'disableIpSpammyControl' => [
						'property' => 'disableIpSpammyControl',
						'type' => 'checkbox',
						'label' => 'Disable IPs Spammy Control',
						'description' => "Prevent Aspen from internally checking and blocking spam IP addresses",
						'default' => false,
					]
				],
			],
			'monitorAntivirus' => [
				'property' => 'monitorAntivirus',
				'type' => 'checkbox',
				'label' => 'Monitor Antivirus',
				'description' => 'Determine whether or not Antivirus logs should be monitored',
				'default' => true,
			],
			'monitorWaitTime' => [
				'property' => 'monitorWaitTime',
				'type' => 'checkbox',
				'label' => 'Monitor Wait Time',
				'description' => 'Determine whether or not Wait Time should be monitored',
				'default' => true,
			],
			'useOriginalCoverUrls' => [
				'property' => 'useOriginalCoverUrls',
				'type' => 'checkbox',
				'label' => 'Use Original Cover URLs',
				'description' => 'Determine whether or not original cover URLs should be used.',
				'note' => "After changing this setting, users should clear their browser's cache to ensure updated cover URLs take effect immediately. Existing cached covers may otherwise remain visible until the cache expires.",
				'default' => false,
			],
			'disable_user_agent_logging' => [
				'property' => 'disable_user_agent_logging',
				'type' => 'checkbox',
				'label' => 'Disable User Agent Tracking',
				'description' => 'When enabled, disables all user agent tracking including logging, spam detection, and blocking.',
				'default' => false,
			],
			'logFrequentCrons' => [
				'property' => 'logFrequentCrons',
				'type' => 'checkbox',
				'label' => 'Log Frequent Cron Jobs',
				'description' => 'Whether or not to log frequently running cron jobs (e.g., runs every few minutes).',
				'note' => 'Frequent jobs include: ' . implode(', ', $frequentJobs) . '.',
				'default' => false,
			],
		];

		if (!UserAccount::isLoggedIn() || !UserAccount::getActiveUserObj()->isAspenAdminUser()) {
			$objectStructure['indexingSection']['properties']['storeRecordDetailsInSolr']['type'] = 'hidden';
			$objectStructure['indexingSection']['properties']['storeRecordDetailsInDatabase']['type'] = 'hidden';
			$objectStructure['indexingSection']['properties']['indexVersion']['type'] = 'hidden';
			$objectStructure['indexingSection']['properties']['searchVersion']['type'] = 'hidden';
			$objectStructure['indexingSection']['properties']['hooplaVersion']['type'] = 'hidden';
		}

		global $enabledModules;
		if (!array_key_exists('Axis 360', $enabledModules)) {
			unset($objectStructure['indexingSection']['properties']['numBoundlessSettingsToProcessInParallel']);
		}

		self::$_objectStructure[$context] = $objectStructure;
		return self::$_objectStructure[$context];
	}

	public static function forceNightlyIndex(string $triggerSource = 'unknown') : void {
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			$variables->__set('runNightlyFullIndex', 1);
			$timestamp = date('Y-m-d H:i:s');
			$newTrigger = "$triggerSource (Triggered at $timestamp)";
			if (!empty($variables->nightlyIndexTrigger)) {
				$variables->__set('nightlyIndexTrigger', $variables->nightlyIndexTrigger . "\n" . $newTrigger);
			} else {
				$variables->__set('nightlyIndexTrigger', $newTrigger);
			}
			$variables->update();
		}
	}

	public static function forceRegrouping(): void {
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			if ($variables->regroupAllRecordsDuringNightlyIndex == 0) {
				$variables->regroupAllRecordsDuringNightlyIndex = 1;
				$variables->update();
			}
		}
	}

	/** @var null|SystemVariables */
	protected static $_systemVariables = null;

	/**
	 * @return SystemVariables|false
	 */
	public static function getSystemVariables() : SystemVariables|bool {
		if (SystemVariables::$_systemVariables == null) {
			SystemVariables::$_systemVariables = new SystemVariables();
			if (!SystemVariables::$_systemVariables->find(true)) {
				SystemVariables::$_systemVariables = false;
			}
		}
		return SystemVariables::$_systemVariables;
	}

	public function update(string $context = '') : int|bool {
		if ($this->trackIpAddresses == 0) {
			//Delete all previously stored usage stats.
			$usageByIP = new UsageByIPAddress();
			$usageByIP->delete(true);
		}
		if ($this->disable_user_agent_logging == 1) {
			//Delete all previously stored user agent stats
			$usageByUserAgent = new UsageByUserAgent();
			$usageByUserAgent->delete(true);
			$userAgent = new UserAgent();
			$userAgent->delete(true);
		}
		$existingSystemVariables = new SystemVariables();
		if ($existingSystemVariables->find(true)) {
			if ($this->hooplaVersion == 2 && $existingSystemVariables->hooplaVersion != 2) {
				$this->__set('runNightlyFullIndex', 1);
				$this->_changedFields[] = 'runNightlyFullIndex';
				$timestamp = date('Y-m-d H:i:s');
				$newTrigger = "Hoopla V2 Migration (Triggered at $timestamp)";
				if (!empty($this->nightlyIndexTrigger)) {
					$this->__set('nightlyIndexTrigger', $this->nightlyIndexTrigger . "\n" . $newTrigger);
				} else {
					$this->__set('nightlyIndexTrigger', $newTrigger);
				}
				$this->_changedFields[] = 'nightlyIndexTrigger';
			}
			if ($this->runNightlyFullIndex == 1 && $existingSystemVariables->runNightlyFullIndex == 0) {
				// Only add "Admin UI" trigger if nightlyIndexTrigger wasn't already modified
				// by forceNightlyIndex() — which would mean the 0→1 transition was programmatic, not from the UI
				$triggerUnchanged = ($this->nightlyIndexTrigger ?? '') === ($existingSystemVariables->nightlyIndexTrigger ?? '');
				if ($triggerUnchanged) {
					$timestamp = date('Y-m-d H:i:s');
					$newTrigger = "Admin UI (Triggered at $timestamp)";
					if (!empty($this->nightlyIndexTrigger)) {
						$this->__set('nightlyIndexTrigger', $this->nightlyIndexTrigger . "\n" . $newTrigger);
					} else {
						$this->__set('nightlyIndexTrigger', $newTrigger);
					}
					$this->_changedFields[] = 'nightlyIndexTrigger';
				}
			}
		}
		return parent::update($context);
	}
}
