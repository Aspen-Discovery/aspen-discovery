<?php


class SystemVariables extends DataObject {
	public $__table = 'system_variables';
	public $id;
	public $errorEmail;
	public $ticketEmail;
	public $searchErrorEmail;
	public $loadCoversFrom020z;
	public $currencyCode;
	public $runNightlyFullIndex;
	public $regroupAllRecordsDuringNightlyIndex;
	public $processEmptyGroupedWorks;
	public $allowableHtmlTags;
	public $allowHtmlInMarkdownFields;
	public $useHtmlEditorRatherThanMarkdown;
	public $enableGrapesEditor;
	public $storeRecordDetailsInSolr;
	public $storeRecordDetailsInDatabase;
	public $deletionCommitInterval;
	public $waitAfterDeleteCommit;
	public $indexVersion;
	public $searchVersion;
	public $enableNovelistSeriesIntegration;
	public $greenhouseUrl;
	public $communityContentUrl;
	public $libraryToUseForPayments;
	public $solrConnectTimeout;
	public $solrQueryTimeout;
	public $catalogStatus;
	public $offlineMessage;
	public $appScheme;
	public $enableBrandedApp;
	public $supportingCompany;
	public $googleBucket;
	public $trackIpAddresses;
	public $disableIpSpammyControl;
	public $monitorAntivirus;
	public $useOriginalCoverUrls;


	static function getObjectStructure($context = ''): array {
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
			'errorEmail' => [
				'property' => 'errorEmail',
				'type' => 'text',
				'label' => 'Error Email Address',
				'description' => 'Email Address to send errors to',
				'maxLength' => 128,
			],
			'ticketEmail' => [
				'property' => 'ticketEmail',
				'type' => 'text',
				'label' => 'Ticket Email Address',
				'description' => 'Email Address to send tickets from administrators to',
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
				'type' => 'enum',
				'values' => [
					'USD' => 'USD',
					'CAD' => 'CAD',
					'EUR' => 'EUR',
					'GBP' => 'GBP',
				],
				'label' => 'Currency Code',
				'description' => 'Currency code to use when formatting money',
				'required' => true,
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
					'waitAfterDeleteCommit' => [
						'property' => 'waitAfterDeleteCommit',
						'type' => 'checkbox',
						'label' => 'Wait after delete commit',
						'description' => 'Whether or not to wait for Solr to finish processing the commit before deleting more records',
						'default' => false,
					]
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
			'offlineMessage' => [
				'property' => 'offlineMessage',
				'type' => 'html',
				'label' => 'Offline Message',
				'description' => 'A message to be displayed while Aspen is offline.',
				'default' => 'The catalog is down for maintenance, please check back later.',
				'hideInLists' => true,
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
			'useOriginalCoverUrls' => [
				'property' => 'useOriginalCoverUrls',
				'type' => 'checkbox',
				'label' => 'Use Original Cover URLs',
				'description' => 'Determine whether or not original cover URLs should be used.',
				'note' => "After changing this setting, users should clear their browser's cache to ensure updated cover URLs take effect immediately. Existing cached covers may otherwise remain visible until the cache expires.",
				'default' => false,
			],
		];

		if (!UserAccount::getActiveUserObj()->isAspenAdminUser()) {
			$objectStructure['indexingSection']['properties']['storeRecordDetailsInSolr']['type'] = 'hidden';
			$objectStructure['indexingSection']['properties']['storeRecordDetailsInDatabase']['type'] = 'hidden';
			$objectStructure['indexingSection']['properties']['indexVersion']['type'] = 'hidden';
			$objectStructure['indexingSection']['properties']['searchVersion']['type'] = 'hidden';
		}

		return $objectStructure;
	}

	public static function forceNightlyIndex() {
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			if ($variables->runNightlyFullIndex == 0) {
				$variables->runNightlyFullIndex = 1;
				$variables->update();
			}
		}
	}

	/** @var null|SystemVariables */
	protected static $_systemVariables = null;

	/**
	 * @return SystemVariables|false
	 */
	public static function getSystemVariables() {
		if (SystemVariables::$_systemVariables == null) {
			SystemVariables::$_systemVariables = new SystemVariables();
			if (!SystemVariables::$_systemVariables->find(true)) {
				SystemVariables::$_systemVariables = false;
			}
		}
		return SystemVariables::$_systemVariables;
	}

	public function update($context = '') {
		if ($this->trackIpAddresses == 0) {
			//Delete all previously stored usage stats.
			$usageByIP = new UsageByIPAddress();
			$usageByIP->delete(true);
		}
		return parent::update($context);
	}
}