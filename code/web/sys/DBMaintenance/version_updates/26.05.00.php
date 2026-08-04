<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_05_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n
		'municipality_extend_registration' => [
			'title' => 'Allow Extending Registration In Sierra Municipality',
			'description' => 'Convert array to traditional syntax',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE self_reg_municipality_values_sierra ADD COLUMN extendExpirationToMonthEnd TINYINT(1) DEFAULT 0',
			]
		], //municipality_extend_registration
		'create_plugin_table' => [
			'title' => 'Create Plugin Table',
			'description' => 'Create the plugin table for storing plugin information and configuration',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE plugin (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					version VARCHAR(20) NOT NULL,
					description TEXT,
					author VARCHAR(100),
					enabled TINYINT(1) NOT NULL DEFAULT 1,
					updateDate INT(11),
					minAspenVersion VARCHAR(20) COMMENT 'Minimum required Aspen Discovery version',
					INDEX idx_plugin_slug (name, enabled)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
			]
		], //create_plugin_table
		'create_plugin_permission' => [
			'title' => 'Create Plugin Administration Permission',
			'description' => 'Add permission to administer plugins',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Administer Plugins', '', 80, 'Controls if the user can administer plugins.')"
			]
		], //create_plugin_permission
		'add_html_body_to_email_templates' => [
			'title' => 'Add HTML Body to Email Templates',
			'description' => 'Add HTML Body to Email Templates',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE email_template ADD COLUMN htmlBody TEXT"
			]
		], //add_html_body_to_email_templates
		'setup_default_saved_search_email_template' => [
			'title' => 'Setup Default Saved Search Email Template',
			'description' => 'Add permission to administer plugins',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO email_template (name, templateType, languageCode, subject, plainTextBody, htmlBody) VALUES ('Default Saved Search Alert', 'savedSearchAlert', 'en', 'New Library Materials Match Your Saved Searches', 'The library has added new materials to its collection that may be of interest based on your saved searches (%searchHistory.url%). You may view and request the material via the link(s) below.\r\n\r\n%searchHistory.updatedSearchesWithSampleTitles%', '<p>The library has added new materials to its collection that may be of interest based on your <a href=\'%searchHistory.url%\'>saved searches</a>. You may view and request the material via the link(s) below.</p><div>%searchHistory.updatedSearchesWithSampleTitlesHtml%</div>')"
			]
		], //create_plugin_permission
		'self_check_completion_message_name' => [
			'title' => 'Add a name to Self Check Completion Message',
			'description' => 'Add permission to administer plugins',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE self_check_completion_message ADD COLUMN name TEXT"
			]
		], //self_check_completion_message_name
		'sort_search_interpreter_terms' => [
			'title' => 'Sort Search Interpreter Special Terms',
			'description' => 'Sort Search Interpreter Special Terms',
			'sql' => [
				'ALTER TABLE search_interpreter_special_terms ADD COLUMN weight int(11) NOT NULL DEFAULT 0',
				'UPDATE search_interpreter_special_terms set weight = id'
			]
		], //sort_search_interpreter_terms
		'26_05_index_improvements' => [
			'title' => '26.05 Index Improvements',
			'description' => 'Index improvements to improve indexing performance',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE author_authority_alternative ADD index normalized_id (normalized, authorId)',
				'ALTER TABLE author_authority ADD index id_normalized (id, normalized)',
				'ALTER TABLE user_reading_history_work DROP index groupedWorkPermanentId',
				'ALTER TABLE user_reading_history_work ADD index groupedWork_userId (groupedWorkPermanentId, userId)',
				'ALTER TABLE user_work_review DROP index userId',
				'ALTER TABLE user_work_review DROP index groupedRecordPermanentId',
				'ALTER TABLE user_work_review ADD index groupedWork_userId (groupedRecordPermanentId, userId)',
				'ALTER TABLE grouped_work_alternate_titles DROP index alternateTitle',
				'ALTER TABLE grouped_work_alternate_titles ADD INDEX alternateInfo(alternateTitle, alternateAuthor, alternateGroupingCategory)'
			]
		], //26_05_index_improvements
		'26_05_index_improvements_2' => [
			'title' => 'Index improvements for 26.05 part 2',
			'description' => 'Improve indexes for some tables for 26.05 part 2',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE session add index remember_me_last(remember_me, last_used);',
				'ALTER TABLE ip_lookup DROP index startIpVal;',
				'ALTER TABLE ip_lookup DROP index endIpVal;',
				'ALTER TABLE ip_lookup DROP index startIpVal_2;',
				'ALTER TABLE ip_lookup DROP index endIpVal_2;',
				'ALTER TABLE ip_lookup ADD index ip_range(startIpVal, endIpVal);',
			]
		], //26_05_index_improvements_2

		//kirstien

		//kodi
		'materials_request_title' => [
			'title' => 'Add Materials Request Title table',
			'description' => 'Add table to facilitate grouping materials requests by title.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS materials_request_title  (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					title varchar(255),
					author varchar(255),
					format varchar(25),
					formatId int(10),
					isbn varchar(15),
					upc varchar(15),
					issn varchar(8),
					comments varchar(255),
					hasExistingRecord tinyint(1),
					lastCheckForExistingRecord int(11),
					existingRecordUrl tinytext,
					dateFirstRequested int(11),
					dateLastRequested int(11)
				) ENGINE = InnoDB',
			]
		], //materials_request_title
		'materials_request_title_id' => [
			'title' => 'Add Column for Materials Request Title ID to Materials Request Table',
			'description' => 'Add column materialsRequestTitleId to materials_request to connect materials_request to materials_request_title.',
			'sql' => [
				"ALTER TABLE materials_request ADD COLUMN materialsRequestTitleId INT(11)"
			]
		], //materials_request_title_id
		'move_materials_request_info' => [
			'title' => 'Migrate Materials Request Info',
			'description' => 'Migrate data from materials_request to materials_request_title.',
			'continueOnError' => false,
			'sql' => [
				'migrateMaterialsRequestTitleData',
			]
		], //move_materials_request_info
		'indexed_duration' => [
			'title' => 'Add indexed_duration Table',
			'description' => 'Add table for indexing duration of grouped work variations (audiobooks).',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS indexed_duration  (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					duration int(11)
				) ENGINE = InnoDB',
			]
		], //indexed_duration
		'indexed_duration_id' => [
			'title' => 'Add durationId Column',
			'description' => 'Add durationId column to grouped_work_records.',
			'sql' => [
				'ALTER TABLE grouped_work_records ADD COLUMN durationId int(11)'
			]
		], // indexed_duration_id

		'update_cloudsource_urls' => [
			'title' => 'Update Cloud Source URLs',
			'description' => 'Update Cloud Source URL variable names for API vs Patron url.',
			'sql' => [
				'ALTER TABLE cloudsource_setting CHANGE baseUrl apiUrl VARCHAR(255)',
				'ALTER TABLE cloudsource_setting ADD COLUMN patronUrl VARCHAR(255)',
			]
		], //update_cloudsource_urls

		//yanjun
		'add_hoopla_flex_batch_size' => [
			'title' => 'Add Hoopla Flex Batch Size',
			'description' => 'Add a batch size for Hoopla Flex availability updates',
			'sql' => [
				"ALTER TABLE hoopla_settings ADD COLUMN hooplaFlexBatchSize int(3) DEFAULT 50",
			]
		], //add_hoopla_flex_batch_size
		'migrate_old_mpaa_rating_to_content_rating' => [
			'title' => 'Migrate old mpaa_rating to content_rating',
			'description' => 'Migrate old mpaa_rating to content_rating',
			'sql' => [
				"UPDATE grouped_work_facet SET facetName = 'content_rating', displayName = 'Content Rating', displayNamePlural = 'Content Ratings' WHERE facetName = 'mpaa_rating';",
			],
		], //migrate_old_mpaa_rating_to_content_rating
		'extend_holiday_table' => [
			'title' => 'Extend Holiday Table',
			'description' => 'Extend Holiday Table, add special hours fields',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE holiday ADD COLUMN locationId INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE holiday ADD COLUMN closed TINYINT(1) NOT NULL DEFAULT 1",
				"ALTER TABLE holiday ADD COLUMN open varchar(10) DEFAULT NULL",
				"ALTER TABLE holiday ADD COLUMN close varchar(10) DEFAULT NULL",
				"ALTER TABLE holiday DROP INDEX LibraryDate",
			]
		], //extend_holiday_table
		'add_show_in_holiday_hours_table_to_location_table' => [
			'title' => 'Add Show In Holiday Hours table to Location Table',
			'description' => 'Add a column to the location table to indicate whether the library uses the holiday hours table',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE location ADD COLUMN showInHolidayHoursTable TINYINT(1) NOT NULL DEFAULT 1',
			]
		], //add_use_holiday_hours_table_to_location_table
		'holiday_table_migration' => [
			'title' => 'Holiday Table Migration',
			'description' => 'Backfill old library-level holiday rows into per-location rows',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO holiday (libraryId, date, name, locationId, closed, `open`, `close`) SELECT h.libraryId, h.date, h.name, l.locationId, h.closed, h.`open`, h.`close` FROM holiday h INNER JOIN location l ON h.libraryId = l.libraryId WHERE h.locationId = -1 AND l.showInHolidayHoursTable = 1",
				"DELETE FROM holiday WHERE locationId = -1",
				"ALTER TABLE holiday ADD UNIQUE KEY LocationDate (locationId, date)",
				"ALTER TABLE holiday ADD INDEX LibraryDate (libraryId, date)",
			]
		], //holiday_table_migration

		//imani
		// Aspen Progressive Web Application(PWA) updates moved
		'create_aspen_pwa_module' => [
			'title' => 'Create Aspen Progressive Web Application(PWA) Module',
			'description' => 'Setup Aspen Progressive Web Application(PWA) (Progressive Web Application) module',
			'sql' => [
				"INSERT IGNORE INTO modules (name, indexName, backgroundProcess) VALUES ('Aspen Progressive Web Application(PWA)', '', '')",
			],
		],
		'create_aspen_pwa_settings' => [
			'title' => 'Create Aspen Progressive Web Application(PWA) Settings',
			'description' => 'Create database table for Aspen Progressive Web Application(PWA) settings',
            'continueOnError' => true,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS aspen_pwa_settings (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name varchar(50) NOT NULL,
					shortName varchar(50) NOT NULL,
					description varchar(200) NOT NULL,
					themeId int(11) NOT NULL,
					manifestID varchar(50) NOT NULL,
					startURL  varchar(50) DEFAULT '/',
					slug  varchar(50) NOT NULL,
					sha256CertFingerprint  varchar(200) NOT NULL,
					firebaseAPIKey varchar(50) NOT NULL,
					firebaseAuthDomain varchar(50) NOT NULL,
					firebaseProjectID varchar(50) NOT NULL,
					firebaseStorageBucket varchar(50) NOT NULL,
					firebaseMessagingSenderID varchar(50) NOT NULL,
					firebaseAppID varchar(50) NOT NULL,
					vapidKey varchar(100) NOT NULL,
					serviceAccount varchar(5000) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
			]
			],
		'alter_user_notification_token' => [
			'title' => 'User Notification Token Update',
			'description' => 'Adding tokenType field to user notification Token',
			'sql' => [
				"SELECT count(*)
					INTO @exist
					FROM information_schema.columns 
					WHERE table_schema = database()
					and COLUMN_NAME = 'tokenType'
					AND table_name = 'user_notification_tokens';

					set @query = IF(@exist <= 0, 'alter table user_notification_tokens add column tokenType varchar(16) default \'expo\'', 'select \'Column Exists\' status');

					prepare stmt from @query;

					EXECUTE stmt;
					DEALLOCATE PREPARE stmt;",
			],
		],
		'alter_library_add_setting' => [
			'title' => 'Add Aspen Progressive Web Application(PWA) Setting Id',
			'description' => 'update library to include Aspen Progressive Web Application(PWA) setting ID to link to Aspen Progressive Web Application(PWA) settings',
			'sql' => [
				"ALTER TABLE library add column `AspenPWASettingId` int(11) Default -1;"
			],
		],
		'insert_aspen_pwa_permissions' => [
			'title' => 'Add Aspen Progressive Web Application(PWA) permissions',
			'description' => 'Add permisions for administering Aspen Progressive Web Application(PWA) and sending notifications',
			'sql' => [
				"INSERT IGNORE into `permissions` (name, sectionName, requiredModule, weight, description) VALUES ('Administer Aspen Progressive Web Application(PWA) Settings','Aspen Progressive Web Application(PWA)', 'Aspen Progressive Web Application(PWA)', 10, 'Controls if the user can change Aspen Progressive Web Application(PWA) Settings.');",
				"INSERT IGNORE into `permissions` (name, sectionName, requiredModule, weight, description) VALUES ('Send Aspen Progressive Web Application(PWA) Notifications to All Libraries','Aspen Progressive Web Application(PWA)', 'Aspen Progressive Web Application(PWA)', 6, 'Controls if the user can send notifications to Aspen Progressive Web Application(PWA) users from all libraries.');",
				"INSERT IGNORE into `permissions` (name, sectionName, requiredModule, weight, description) VALUES ('Send Aspen Progressive Web Application(PWA) Notifications to All Locations','Aspen Progressive Web Application(PWA)', 'Aspen Progressive Web Application(PWA)', 6, 'Controls if the user can send notifications to Aspen Progressive Web Application(PWA) users from all locations.');",
				"INSERT IGNORE into `permissions` (name, sectionName, requiredModule, weight, description) VALUES ('Send Aspen Progressive Web Application(PWA) Notifications to Home Library','Aspen Progressive Web Application(PWA)', 'Aspen Progressive Web Application(PWA)', 6, 'Controls if the user can send notifications to Aspen Progressive Web Application(PWA) users from their home library.');",
				"INSERT IGNORE into `permissions` (name, sectionName, requiredModule, weight, description) VALUES ('Send Aspen Progressive Web Application(PWA) Notifications to Home Location','Aspen Progressive Web Application(PWA)', 'Aspen Progressive Web Application(PWA)', 6, 'Controls if the user can send notifications to Aspen Progressive Web Application(PWA) users from their home location.');",
				"INSERT IGNORE into `permissions` (name, sectionName, requiredModule, weight, description) VALUES ('Send Aspen PWA Notifications to Home Library Locations','Aspen Progressive Web Application(PWA)', 'Aspen Progressive Web Application(PWA)', 6, 'Controls if the user can send notifications to Aspen Progressive Web Application(PWA) users for all locations that are part of their home library.');",
			],
		],
		//galen

		//chloe
		'pay360_rename_wsldUrl_to_wsdlUrl' => [
			'title' => 'Rename wsldUrl to wsdlUrl in Pay360 Settings',
			'description' => 'Corrects a Typo in the Column Name (WSLD → WSDL)',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE pay360_setting CHANGE COLUMN wsldUrl wsdlUrl VARCHAR(255)",
			],
		], // pay360_rename_wsldUrl_to_wsdlUrl
		'pay360_drop_identifier_column' => [
			'title' => 'Remove Unused Identifier Column from Pay360 Settings',
			'description' => 'Removes an Unused Column from the pay360_setting Table',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE pay360_setting DROP COLUMN identifier",
			],
		], // pay360_drop_identifier_column
		'pay360_drop_request_parameter_table' => [
			'title' => 'Remove Unused Pay360 Request Parameter Table',
			'description' => 'Drops the pay360_request_parameter Table Which is Not Used by the Pay360 Integration',
			'continueOnError' => true,
			'sql' => [
				"DROP TABLE IF EXISTS pay360_request_parameter",
			],
		], // pay360_drop_request_parameter_table

		//chloe (submitting on behalf of alexander)
		'add_ability_for_admin_to_control_whether_holds_can_be_grouped' => [
			'title' => 'Add Ability for Admin to Control Whether Holds Can Be Grouped',
			'description' => 'Allow admin to control whether holds can be grouped',
			'sql' => [
				"ALTER TABLE library ADD COLUMN allowHoldsToBeGrouped TINYINT(1) DEFAULT 0",
			],
		], //add_ability_for_admin_to_control_whether_holds_can_be_grouped
		'add_grouped_hold_id_to_user_hold' => [
			'title' => 'Add Grouped Hold Id To User Hold',
			'description' => 'Add grouped hold id and visual hold group id to user hold',
			'sql' => [
				"ALTER TABLE user_hold ADD COLUMN holdGroupId INT(11) DEFAULT NULL",
				"ALTER TABLE user_hold ADD COLUMN visualHoldGroupId VARCHAR(50) DEFAULT NULL",
			],
		], //add_grouped_hold_id_to_user_hold

		//pedro

		//mark j
		'create_explore_more_source_tables' => [
			'title' => 'Create Explore More Tables',
			'description' => 'Adds tables to control Explore More sources with sorting and visibility by library and location.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS explore_more_source (
				id INT(11) NOT NULL AUTO_INCREMENT,
				source VARCHAR(50) NOT NULL,
				showInExploreMore TINYINT(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (id),
				UNIQUE KEY (source)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
				"CREATE TABLE IF NOT EXISTS explore_more_source_library (
				id INT(11) NOT NULL AUTO_INCREMENT,
				exploreMoreSourceId INT(11) NOT NULL,
				libraryId INT(11) NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY (exploreMoreSourceId, libraryId),
				FOREIGN KEY (exploreMoreSourceId) REFERENCES explore_more_source(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
				"CREATE TABLE IF NOT EXISTS explore_more_source_location (
				id INT(11) NOT NULL AUTO_INCREMENT,
				exploreMoreSourceId INT(11) NOT NULL,
				locationId INT(11) NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY (exploreMoreSourceId, locationId),
				FOREIGN KEY (exploreMoreSourceId) REFERENCES explore_more_source(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
				"CREATE TABLE IF NOT EXISTS explore_more_source_group (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(100) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
				"CREATE TABLE IF NOT EXISTS explore_more_source_entry (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				exploreMoreSourceGroupId INT NOT NULL,
				exploreMoreSourceId INT NOT NULL,
				weight INT NOT NULL DEFAULT 0,
				FOREIGN KEY (exploreMoreSourceGroupId) REFERENCES explore_more_source_group(id) ON DELETE CASCADE,
				FOREIGN KEY (exploreMoreSourceId) REFERENCES explore_more_source(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
			],
		], //create_explore_more_source_tables
		'add_explore_more_permissions' => [
			'title' => 'Add Explore More Permissions',
			'description' => 'Adds permissions needed to allow administration of Explore More.',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Local Enrichment', 'Administer All Explore More', '', 40, 'Allows users to administer Explore More sources.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Explore More'))",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Local Enrichment', 'Administer Library Explore More', '', 40, 'Allows users to administer Explore More sources for their library.')",
			],
		], //add_explore_more_permissions
		'insert_default_explore_more_sources' => [
			'title' => 'Insert Default Explore More Sources',
			'description' => 'Populate the explore_more_source table with the default sources.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Catalog', 1);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('EBSCO EDS', 5);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('EBSCOhost', 5);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Summon', 5);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Gale', 5);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('CloudSource', 5);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Events', 4);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Web Indexer', 6);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Lists', 3);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Open Archives', 7);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Series', 2);",
				"INSERT INTO explore_more_source (source, showInExploreMore) VALUES ('Genealogy', 8);",
			],
		], //insert_default_explore_more_sources
		'user_agent_consolidation' => [
			'title' => 'Consolidate User Agents and Stats',
			'description' => 'Consolidating user agents and their corresponding stats to remove duplicates that only differ by version details. This will allow for cleaner reporting and bot detection.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_agent DROP INDEX userAgent, ADD INDEX userAgent (userAgent(512))",
				"UPDATE user_agent SET userAgent = SUBSTRING_INDEX(userAgent, '/', 1) WHERE userAgent LIKE '%/%'",
				"CREATE TABLE user_agent_temp LIKE user_agent",
				"INSERT INTO user_agent_temp (userAgent, isBot, blockAccess)
				 SELECT userAgent,
						MAX(isBot),
						MIN(blockAccess)
				 FROM user_agent
				 GROUP BY userAgent",
				"CREATE TABLE usage_by_user_agent_temp LIKE usage_by_user_agent",
				"INSERT INTO usage_by_user_agent_temp (userAgentId, year, month, instance, numRequests, numBlockedRequests)
				 SELECT consolidated_user_agent.id,
						usage_by_user_agent.year,
						usage_by_user_agent.month,
						usage_by_user_agent.instance,
						SUM(usage_by_user_agent.numRequests),
						SUM(usage_by_user_agent.numBlockedRequests)
				 FROM usage_by_user_agent
				 INNER JOIN user_agent original_user_agent ON usage_by_user_agent.userAgentId = original_user_agent.id
				 INNER JOIN user_agent_temp consolidated_user_agent ON consolidated_user_agent.userAgent <=> original_user_agent.userAgent
				 GROUP BY consolidated_user_agent.id, usage_by_user_agent.year, usage_by_user_agent.month, usage_by_user_agent.instance",
				"DROP TABLE usage_by_user_agent",
				"RENAME TABLE usage_by_user_agent_temp TO usage_by_user_agent",
				"DROP TABLE user_agent",
				"RENAME TABLE user_agent_temp TO user_agent",
				"ALTER TABLE user_agent DROP INDEX userAgent, ADD UNIQUE INDEX userAgent (userAgent(512))"
			]
		], //user_agent_consolidation
		'default_is_bot_for_user_agents' => [
			'title' => 'Setup Default Is Bot For User Agents',
			'description' => 'Setup Default Is Bot For User Agents after consolidating user agents.',
			'sql' => [
				"UPDATE user_agent SET isBot = 1 WHERE userAgent REGEXP 'Bot|Spider|Spyder|Crawl|SearchHelper|Aspen Discovery'",
			]
		], //default_is_bot_for_user_agents
		'web_resource_show_in_explore_more' => [
			'title' => 'Add Option to Web Resources to Show in Explore More',
			'description' => 'Add option in web builder resource settings to show in explore more',
			'sql' => [
				"ALTER TABLE web_builder_resource ADD COLUMN showInExploreMore TINYINT(1) DEFAULT 1"
			]
		], //web_resource_show_in_explore_more

		//lucas

		//tomas
		'custom_grouped_work_search_specs' => [
			'title' => 'Custom Grouped Work Search Specs',
			'description' => 'Add customGroupedWorkSearchSpecs setting to system variables for grouped work search specs configuration',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN IF NOT EXISTS customGroupedWorkSearchSpecs TEXT DEFAULT NULL COMMENT "Path to custom grouped work search specs YAML file"'
			]
		], //custom_grouped_work_search_specs

		// stephen


		//pedro

		//other
		'remove_site_active_ticket_feed' => [
			 'title' => 'Remove Active Ticket Feed',
			 'description' => 'Deletes the Active Ticket Feed field from the Greenhouse Site List settings.',
			 'continueOnError' => false,
			 'sql' => [
				 'ALTER TABLE aspen_sites DROP COLUMN IF EXISTS activeTicketFeed'
			 ]
		 ], //remove_site_active_ticket_feed
	];
}

function normalizeAuthorTitleString(string $value): string {
	//remove extra/leading/trailing spaces and special characters
	$value = trim($value);
	$value = preg_replace('/[^a-zA-Z0-9 ]/', '', $value);
	$value = preg_replace('/\s+/', ' ', $value);
	return strtolower($value);
}

function migrateMaterialsRequestTitleData(): void {
	global $aspen_db;

	$titleColumns = [
		'title', 'author', 'format', 'formatId', 'isbn', 'upc', 'issn',
		'hasExistingRecord', 'lastCheckForExistingRecord', 'existingRecordUrl'
	];
	$columnList = implode(', ', $titleColumns);

	$requests = $aspen_db->query(
		"SELECT id, dateCreated, $columnList FROM materials_request WHERE materialsRequestTitleId IS NULL"
	);
	$rows = $requests->fetchAll(PDO::FETCH_ASSOC);

	if (empty($rows)) {
		echo "Nothing to migrate.\n";
		return;
	}

	foreach ($rows as $row) {
		try {
			$requestId   = $row['id'];
			$dateCreated = isset($row['dateCreated']) && $row['dateCreated'] !== '' ? (int)$row['dateCreated'] : null;
			$titleId = null;

			// --- Step 1: Match on isbn, upc, or issn ---
			$identifiers = [];
			$params      = [];

			foreach (['isbn', 'upc', 'issn'] as $field) {
				$val = trim($row[$field] ?? '');
				if ($val !== '') {
					$identifiers[] = "$field = :$field";
					$params[":$field"] = $val;
				}
			}

			if (!empty($identifiers)) {
				$whereClause = implode(' OR ', $identifiers);
				$stmt = $aspen_db->prepare(
					"SELECT id FROM materials_request_title WHERE $whereClause LIMIT 1"
				);
				$stmt->execute($params);
				$existing = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($existing) {
					$titleId = $existing['id'];
				}
			}

			// --- Step 2: Fallback — title + author + format ---
			if ($titleId === null
				&& !empty(trim($row['title'] ?? ''))
				&& !empty(trim($row['author'] ?? ''))
				&& !empty(trim($row['format'] ?? ''))
			) {
				$stmt = $aspen_db->prepare(
					"SELECT id FROM materials_request_title
						WHERE LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(title,  '[^a-zA-Z0-9 ]+', ''), '\\\\s+', ' '))) = :title
						AND LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(author, '[^a-zA-Z0-9 ]+', ''), '\\\\s+', ' '))) = :author
						AND format = :format
						LIMIT 1"
				);
				$stmt->execute([
					':title'  => normalizeAuthorTitleString($row['title']),
					':author' => normalizeAuthorTitleString($row['author']),
					':format' => $row['format'],
				]);
				$existing = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($existing) {
					$titleId = $existing['id'];
				}
			}

			// --- Step 3: Fallback — title + author ---
			if ($titleId === null
				&& !empty(trim($row['title'] ?? ''))
				&& !empty(trim($row['author'] ?? ''))
			) {
				$stmt = $aspen_db->prepare(
					"SELECT id FROM materials_request_title 
					WHERE LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(title,  '[^a-zA-Z0-9 ]+', ''), '\\\\s+', ' '))) = :title 
					AND LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(author, '[^a-zA-Z0-9 ]+', ''), '\\\\s+', ' '))) = :author
					LIMIT 1"
				);
				$stmt->execute([
					':title'  => normalizeAuthorTitleString($row['title']),
					':author' => normalizeAuthorTitleString($row['author']),
				]);
				$existing = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($existing) {
					$titleId = $existing['id'];
				}
			}

			// --- Step 4: Fallback — title only ---
			if ($titleId === null && !empty(trim($row['title'] ?? ''))) {
				$stmt = $aspen_db->prepare(
					"SELECT id FROM materials_request_title
					WHERE LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(title, '[^a-zA-Z0-9 ]+', ''), '\\\\s+', ' '))) = :title
					LIMIT 1"
				);
				$stmt->execute([':title' => normalizeAuthorTitleString($row['title'])]);
				$existing = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($existing) {
					$titleId = $existing['id'];
				}
			}

			// --- Step 5: Update matched title row ---
			if ($titleId !== null) {
				$updates      = [];
				$updateParams = [':id' => $titleId];

				foreach ($titleColumns as $col) {
					$val = $row[$col] ?? null;
					if ($val !== null && $val !== '') {
						$updates[]             = "$col = COALESCE($col, :$col)";
						$updateParams[":$col"] = $val;
					}
				}

				if ($dateCreated !== null) {
					// Fetch current date values from the matched title row
					$dateStmt = $aspen_db->prepare(
						"SELECT dateFirstRequested, dateLastRequested FROM materials_request_title WHERE id = :id"
					);
					$dateStmt->execute([':id' => $titleId]);
					$currentDates = $dateStmt->fetch(PDO::FETCH_ASSOC);

					$currentFirst = $currentDates['dateFirstRequested'];
					$currentLast  = $currentDates['dateLastRequested'];

					if ($currentFirst === null || $dateCreated < $currentFirst) {
						$updates[] = "dateFirstRequested = :dateFirstRequested";
						$updateParams[':dateFirstRequested'] = $dateCreated;
					}

					if ($currentLast === null || $dateCreated > $currentLast) {
						$updates[] = "dateLastRequested = :dateLastRequested";
						$updateParams[':dateLastRequested'] = $dateCreated;
					}
				}

				if (!empty($updates)) {
					$updateSql = "UPDATE materials_request_title SET "
						. implode(', ', $updates)
						. " WHERE id = :id";
					$aspen_db->prepare($updateSql)->execute($updateParams);
				}
			}

			// --- Step 6: No match — insert a new title row ---
			if ($titleId === null) {
				$insertValues = [];
				$insertParams = [];

				foreach ($titleColumns as $col) {
					$insertValues[]        = ":$col";
					$insertParams[":$col"] = $row[$col] ?? null;
				}

				$insertParams[':dateFirstRequested'] = $dateCreated;
				$insertParams[':dateLastRequested']  = $dateCreated;

				$insertSql = "INSERT INTO materials_request_title ($columnList, dateFirstRequested, dateLastRequested)
								VALUES (" . implode(', ', $insertValues) . ", :dateFirstRequested, :dateLastRequested)";
				$stmt = $aspen_db->prepare($insertSql);
				$stmt->execute($insertParams);
				$titleId = $aspen_db->lastInsertId();
			}

			// --- Step 7: Link the request to the title row ---
			$aspen_db->prepare(
				"UPDATE materials_request SET materialsRequestTitleId = :titleId WHERE id = :requestId"
			)->execute([':titleId' => $titleId, ':requestId' => $requestId]);

		} catch (Throwable $e) {
			echo "Skipped request ID {$row['id']}: " . $e->getMessage() . "\n";
		}
	}

	echo "Migration complete. " . count($rows) . " request(s) processed.\n";
}