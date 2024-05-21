<?php

/** @noinspection PhpUnused */
function getUpdates24_05_00(): array {
	/** @noinspection SqlWithoutWhere */
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - ByWater
		'remove_individual_marc_path' => [
			'title' => 'Remove Individual MARC Path',
			'description' => 'Remove Individual MARC Path',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE indexing_profiles DROP COLUMN individualMarcPath',
				'ALTER TABLE indexing_profiles DROP COLUMN numCharsToCreateFolderFrom',
				'ALTER TABLE indexing_profiles DROP COLUMN createFolderFromLeadingCharacters',
				'ALTER TABLE sideloads DROP COLUMN individualMarcPath',
				'ALTER TABLE sideloads DROP COLUMN numCharsToCreateFolderFrom',
				'ALTER TABLE sideloads DROP COLUMN createFolderFromLeadingCharacters',
			]
		], //remove_individual_marc_path
		'force_regrouping_all_works_24_05' => [
			'title' => 'Force Regrouping All Works 24.05',
			'description' => 'Force Regrouping All Works',
			'sql' => [
				"UPDATE system_variables set regroupAllRecordsDuringNightlyIndex = 1",
			],
		], //force_regrouping_all_works_24_05
		'toggle_novelist_series' => [
			'title' => 'Toggle NoveList Series',
			'description' => 'Allow NoveList series data to be toggled on or off',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN enableNovelistSeriesIntegration TINYINT DEFAULT 1",
			],
		], //toggle_novelist_series
		'alternate_grouping_category' => [
			'title' => 'Alternate Grouping Category',
			'description' => 'Add Alternate Grouping Category to Grouped Work Alternate Titles',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE grouped_work_alternate_titles ADD COLUMN alternateGroupingCategory VARCHAR(5)",
				'updateAlternateGroupingCategories'
			],
		], //alternate_grouping_format

		//kirstien - ByWater

		//kodi - ByWater
		'permissions_create_events_assabet' => [
			'title' => 'Alters permissions for Events',
			'description' => 'Create permissions for Assabet',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Assabet Settings', 'Events', 20, 'Allows the user to administer integration with Assabet for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Assabet Settings'))",
			],
		],
		// permissions_create_events_assabet
		'assabet_settings' => [
			'title' => 'Define events settings for Assabet integration',
			'description' => 'Initial setup of the Assabet integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS assabet_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					baseUrl VARCHAR(255) NOT NULL,
    				eventsInLists tinyint(1) default 1,
    				bypassAspenEventPages tinyint(1) default 0,
    				registrationModalBody mediumtext,
    				registrationModalBodyApp varchar(500),
    				numberOfDaysToIndex INT DEFAULT 365
				) ENGINE INNODB',
			],
		],

		// assabet_settings
		'assabet_events' => [
			'title' => 'Assabet Events Data',
			'description' => 'Setup tables to store events data for Assabet',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS assabet_events (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingsId INT NOT NULL,
					externalId varchar(150) NOT NULL,
					title varchar(255) NOT NULL,
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					deleted TINYINT default 0,
					UNIQUE (settingsId, externalId)
				)',
			],
		],
		// assabet_events
		'allow_masquerade_with_username' => [
			'title' => 'Allow/Disallow Masquerade Using Username',
			'description' => 'Adds a masquerade setting that will allow libraries to disallow using a Username for masquerading even if the ILS has the ability to allow it.',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowMasqueradeWithUsername TINYINT NOT NULL DEFAULT 1',
			]
		], //allow_masquerade_with_username
		'username_field' => [
			'title' => 'Username Field (Sierra)',
			'description' => 'Adds an option to define what field is used for patron username in Sierra.',
			'sql' => [
				"ALTER TABLE library ADD COLUMN usernameField varchar(1) NOT NULL DEFAULT 'w';",
			],
		], //username_field

		//other
		//jacob - PTFS Europe
		'snippet_contains_analytics_cookies' => [
			 'title' => 'JS Snippet Contains Analytics Cookies',
			 'description' => 'Add a toggle for if a JS snippet contains analytics cookies or not.',
			 'continueOnError' => true,
			 'sql' => [
				 'ALTER TABLE javascript_snippets ADD COLUMN containsAnalyticsCookies TINYINT(1)'
			 ]
		 ], //Snippet_Contains_Marketing_Cookies

		 
	];
}

/** @noinspection PhpUnused */
function updateAlternateGroupingCategories(&$update) {
	require_once ROOT_DIR . '/sys/Grouping/GroupedWorkAlternateTitle.php';
	require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
	$groupedWorkAlternateTitle = new GroupedWorkAlternateTitle();
	$groupedWorkAlternateTitle->find();
	$numTitlesUpdated = 0;
	$groupedWorkAlternateTitles = $groupedWorkAlternateTitle->fetchAll();
	foreach ($groupedWorkAlternateTitles as $groupedWorkAlternateTitle) {
		if (empty($groupedWorkAlternateTitle->alternateGroupingCategory)) {
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $groupedWorkAlternateTitle->permanent_id;
			if ($groupedWork->find(true)) {
				$groupedWorkAlternateTitle->alternateGroupingCategory = $groupedWork->grouping_category;
				$groupedWorkAlternateTitle->update();
				$numTitlesUpdated++;
			}
			$groupedWork->__destruct();
		}
	}

	$update['status'] = "Finished updating Alternate Grouping Categories, updated $numTitlesUpdated";
	$update['success'] = true;
}