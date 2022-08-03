<?php
/** @noinspection PhpUnused */
function getUpdates22_08_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'account_profile_oauth_client_secret_length' => [
			'title' => 'Adjust length for oAuth Client Secret in Account Profile',
			'description' => 'Adjust length for oAuth Client Secret in Account Profile',
			'sql' => [
				"ALTER TABLE account_profiles CHANGE COLUMN oAuthClientSecret oAuthClientSecret VARCHAR(50)",
			]
		], //account_profile_oauth_client_secret_length
		'greenhouse_wait_time_monitoring' => [
			'title' => 'Wait Time monitoring in Greenhouse',
			'description' => 'Add tracking of wait time within the Greenhouse',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_site_wait_time (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					aspenSiteId INT(11) NOT NULL,
					waitTime FLOAT NOT NULL,
					timestamp INT(11),
					UNIQUE (aspenSiteId, timestamp)
				) ENGINE INNODB',
			]
		],//greenhouse wait time monitoring
		'add_displayHoldsOnCheckout' => [
			'title' => 'Add displayHoldsOnCheckout option to library systems',
			'description' => 'Add option to show if patron checkouts have holds on them',
			'sql' => [
				"ALTER TABLE library ADD COLUMN displayHoldsOnCheckout TINYINT(1) DEFAULT 0"
			]
		],//add_displayHoldsOnCheckout
		'aspen_site_lastOfflineTime' => [
			'title' => 'Offline site monitoring in Greenhouse',
			'description' => 'Add tracking of when Greenhouse is unable to connect to an Aspen site',
			'sql' => [
				"ALTER TABLE aspen_sites ADD COLUMN lastOfflineTime INT",
			]
		],//aspen_site_lastOfflineTime
		'hoopla_bingepass' => [
			'title' => 'Add scoping info for Hoopla Binge Pass',
			'description' => 'Add scoping info for Hoopla Binge Pass',
			'sql' => [
				"ALTER TABLE hoopla_scopes ADD COLUMN includeBingePass TINYINT DEFAULT 1",
				"ALTER TABLE hoopla_scopes ADD COLUMN maxCostPerCheckoutBingePass FLOAT DEFAULT 5",
			]
		],//hoopla_bingepass
		'saved_search_log' => [
			'title' => 'Create Log for Updating Saved Searches',
			'description' => 'Create Log for Updating Saved Searches',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS search_update_log (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					startTime INT(11) NOT NULL, 
					endTime INT(11) NULL, 
					lastUpdate INT(11) NULL, 
					numErrors INT(11) NOT NULL DEFAULT 0,
					numSearches INT(11) NOT NULL DEFAULT 0,
					numUpdated INT(11) NOT NULL DEFAULT 0,
					notes TEXT
				) ENGINE = InnoDB'
			]
		],//saved_search_log
		'saved_search_hasNewResults' => [
			'title' => 'Store if a saved search has new results',
			'description' => 'Store if a saved search has new results',
			'sql' => [
				"ALTER TABLE search ADD COLUMN hasNewResults TINYINT DEFAULT 0",
			]
		],//saved_search_hasNewResults
		'account_summary_hasUpdatedSavedSearches' => [
			'title' => 'Store if a user has saved searches with updates in account summary',
			'description' => 'Store if a user has saved searches with updates in account summary',
			'sql' => [
				"ALTER TABLE user_account_summary ADD COLUMN hasUpdatedSavedSearches TINYINT DEFAULT 0",
			]
		],//account_summary_hasUpdatedSavedSearches
		'overdrive_enable_allow_large_deletes' => [
			'title' => 'OverDrive - Enable Allow Large Deletes',
			'description' => 'Allow the OverDrive process to delete more than 500 records or 5% of the collection',
			'sql' => [
				'ALTER TABLE overdrive_settings CHANGE COLUMN allowLargeDeletes allowLargeDeletes TINYINT(1) DEFAULT 1'
			]
		],
		'aspen_site_lastOfflineTimeB' => [
			'title' => 'More offline site monitoring in Greenhouse',
			'description' => 'Add tracking of why Greenhouse is unable to connect to an Aspen site and when it reconnects',
			'sql' => [
				"ALTER TABLE aspen_sites ADD COLUMN lastOfflineNote VARCHAR(255)",
				"ALTER TABLE aspen_sites ADD COLUMN lastOnlineTime INT",
			]
		],//aspen_site_lastOfflineTimeB
	];
}
