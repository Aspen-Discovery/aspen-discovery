<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_04_00(): array {
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
		'show_palace_project_link_for_checkouts' => [
			'title' => 'Show Palace Project Link For Checkouts',
			'description' => 'Track whether a checkout should have a link to access it in Palace Project',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN showPalaceProjectLink TINYINT DEFAULT 0',
			]
		], //show_palace_project_link_for_checkouts
		'allow_disabling_palace_project_links' => [
			'title' => 'Allow Disabling Palace Project Links',
			'description' => 'Allow disabling Project Links for other eContent',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE palace_project_settings ADD COLUMN showPalaceProjectLinks TINYINT DEFAULT 1',
			]
		], //allow_disabling_palace_project_links
		'user_list_custom_name' => [
			'title' => 'User List Custom Name',
			'description' => 'Allow definining custom names for searchable lists',
			'sql' => [
				'ALTER TABLE user_list ADD COLUMN customAuthorName varchar(256)'
			]
		], //user_list_custom_name
		'user_list_facets' => [
			'title' => 'User List Facets',
			'description' => 'Setup tables to define facets for use when searching lists',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('User Lists', 'Administer User List Facet Settings', '', 40, 'Allows users to administer facets for user list searches.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer User List Facet Settings'))",
				"CREATE TABLE `user_list_facet_groups` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(255) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `name` (`name`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",
				"INSERT INTO user_list_facet_groups (id, name) VALUES (1, 'default')",
				"CREATE TABLE `user_list_facet` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`facetGroupId` int(11) NOT NULL,
					`displayName` varchar(50) NOT NULL,
					`displayNamePlural` varchar(50) DEFAULT NULL,
					`facetName` varchar(50) NOT NULL,
					`weight` int(11) NOT NULL DEFAULT 0,
					`numEntriesToShowByDefault` int(11) NOT NULL DEFAULT 5,
					`showAsDropDown` tinyint(4) NOT NULL DEFAULT 0,
					`sortMode` enum('alphabetically','num_results') NOT NULL DEFAULT 'num_results',
					`collapseByDefault` tinyint(4) DEFAULT 1,
					`useMoreFacetPopup` tinyint(4) DEFAULT 1,
					`translate` tinyint(4) DEFAULT 0,
					`multiSelect` tinyint(4) DEFAULT 0,
					`canLock` tinyint(4) DEFAULT 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `groupFacet` (`facetGroupId`,`facetName`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",
				"CREATE TABLE library_user_list_facet_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT NOT NULL,
					userListFacetGroupId INT NOT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				"INSERT INTO library_user_list_facet_setting (libraryId, userListFacetGroupId) SELECT DISTINCT libraryId, 1 FROM library;"
			]
		], //user_list_facets
		'add_num_skpped_to_website_index_log' => [
			'title' => 'Add Num Skipped to Website Index Log',
			'description' => 'Add Num Skipped to Website Index Log',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE website_index_log ADD COLUMN numSkipped TINYINT(1) NOT NULL DEFAULT 0',
			]
		], //add_num_skpped_to_website_index_log
		'num_skipped_fix_data_type' => [
			'title' => 'Fix Num Skipped data type in Website Index Log',
			'description' => 'Fix Num Skipped data type in Website Index Log',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE website_index_log CHANGE COLUMN numSkipped numSkipped INT(11) NOT NULL DEFAULT 0',
			]
		], //num_skipped_fix_data_type
		'add_additional_item_information' => [
			'title' => 'Add Additional Item Information',
			'description' => 'Add additional item information to database',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE grouped_work_record_items ADD COLUMN barcode TINYTEXT',
				'ALTER TABLE grouped_work_record_items ADD COLUMN note TEXT',
				'ALTER TABLE grouped_work_record_items ADD COLUMN dueDate TINYTEXT',
			]
		], //add_additional_item_information

		//kirstien
		'add_user_app_request_logging_option' => [
			'title' => 'Add LiDA Request Logging Option for Users',
			'description' => 'Add option to log a users LiDA API requests for debugging',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user ADD COLUMN allowAppRequestLogging TINYINT(1) NOT NULL DEFAULT 0',
			]
		],
		//add_user_app_request_logging_option
		'add_user_app_request_log' => [
			'title' => 'Add LiDA Request Log for Users',
			'description' => 'Add logging for users who have enabled allow API logging for LiDA requests',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_app_request_log (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT NOT NULL, 
					action VARCHAR(25), 
					method VARCHAR(25), 
					queryString TEXT NOT NULL,
					version VARCHAR(255),
					time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				) ENGINE = InnoDB'
			]
		],
		//add_user_app_request_log

		'list_transfer_permission' => [
			'title' => 'Add list transfer permission',
			'description' => 'Create permission for allowing transfer of list ownership.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('User Lists', 'Transfer Lists', '', 6, 'Allows the user to transfer a list to another staff.')
				",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Transfer Lists'))",
			],
		],
		//list_transfer_permission

		//kodi
		'include_econtent_in_shelf_locations_facet' => [
			'title' => 'Add Setting for Including/Excluding eContent in Shelf Locations Facet',
			'description' => 'Add Setting for Including/Excluding eContent in Shelf Locations Facet',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN includeEContentInShelvingLocations TINYINT(1) DEFAULT 1"
			]
		], //include_econtent_in_shelf_locations_facet
		'bypass_aspen_cloudsource_page' => [
			'title' => 'Add Option to Bypass Aspen CloudSource Record Page',
			'description' => 'Add option in cloudsource settings to bypass aspen cloudsource record pages',
			'sql' => [
				"ALTER TABLE cloudsource_setting ADD COLUMN bypassAspenCloudSourcePage TINYINT(1) DEFAULT 0"
			]
		], //bypass_aspen_cloudsource_page
		'where_is_it_display_style' => [
			'title' => 'Where Is It Display Style',
			'description' => 'Add where is it display style to grouped work display settings',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN whereIsItDisplayStyle TINYINT(1) DEFAULT 1"
			]
		], //where_is_it_display_style

		//yanjun

		//imani

		//galen

		//chloe
		'update_aspenEventsToInclude_default' => [
			'title' => 'Update AspenEventsToInclude Default',
			'description' => 'Have aspenEventsToInclude default to 0 (do not display events as a search source)',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library MODIFY COLUMN aspenEventsToInclude INT DEFAULT 0",
			],
		], //update_aspenEventsToInclude_default
		'migrate_event_field_select_values_to_codes' => [
			'title' => 'Migrate Event Field Select Values to Codes',
			'description' => 'Converts stored integers for select list event fields to codes (camelCase string values).',
			'continueOnError' => false,
			'sql' => [
				'migrateEventFieldSelectValuesToCamelCase',
			]
		], //migrate_event_field_select_values_to_codes
		'migrate_sendgrid_url_to_settings' => [
			'title' => 'Migrate SendGrid URL to Settings',
			'description' => 'The URL for sendGrid should be customisable as it is region specific',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE sendgrid_settings ADD COLUMN baseUrl VARCHAR(255) DEFAULT null",
			],
		], //migrate_sendgrid_url_to_settings

		//pedro
		'drop_control_display_of_user_dropdown_in_community_engagement_admin_view' => [
			'title' => 'Drop Control User Select Type in Admin View',
			'description' => 'Drop options for how to select users in the admin view  (only search exists now)',
			'sql' => [
				"ALTER TABLE library DROP COLUMN communityEngagementAdminUserSelect",
			],
		], //control_display_of_user_dropdown_in_community_engagement_admin_view

		//mark j
		'add_pageViewsFromPlacard_to_web_builder_resource_usage' => [
			'title' => 'Track web resource page views from a placard',
			'description' => 'Add new column to keep track of page views from a placard in the Web Builder Resource Usage table.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE web_builder_resource_usage ADD COLUMN pageViewsFromPlacard INT NOT NULL DEFAULT 0',
			]
		], //add_pageViewsFromPlacard_to_web_builder_resource_usage
		'create_placard_usage_table' => [
			'title' => 'Create placard usage table',
			'description' => 'Create placard usage table for tracking things like number of times shown and clicks on placards.',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS placard_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					instance VARCHAR(100),
					year INT,
					month INT,
					placardName VARCHAR(255),
					pageViews INT DEFAULT 0,
					pageViewsByAuthenticatedUsers INT DEFAULT 0,
					pageViewsInLibrary INT DEFAULT 0,
					timesShown INT DEFAULT 0
				) ENGINE=INNODB',
			]
		], //create_placard_usage_table
		'add_index_to_placard_usage' => [
			'title' => 'Add index to placard usage table',
			'description' => 'Add index to placard usage table to improve performance of queries.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE placard_usage ADD UNIQUE INDEX placard_usage_unique (instance, year, month, placardName);",
			]
		], //add_index_to_placard_usage
		'add_series_option_to_search_interpreter_settings' => [
			'title' => 'Add Series Option to Search Interpreter Settings',
			'description' => 'Add a setting to control whether or not the search interpreter defaults to a series search when the "series" keyword is used.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE search_interpreter_settings ADD COLUMN triggerSeriesSearch TINYINT(1) DEFAULT 0",
			]
		], //add_series_option_to_search_interpreter_settings

		//lucas

		//tomas

		// stephen
			'change_user_page_defaults.pageSize_to_varchar' => [
			'title' => 'Change user_page_defaults.pageSize column to varchar',
			'description' => 'Modifies the pageSize column to allow the value "all"',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_page_defaults CHANGE COLUMN pageSize pageSize VARCHAR(10) NULL',
			],
		], //change_user_page_defaults.pageSize_to_varchar


		//pedro
		'update_community_engagement_schema_v2' => [
			'title' => 'Normalize Community Engagement to allow multiple milestone/extra credit instances',
			'description' => 'Shifts progress tracking from (Campaign+Item) ID pairs to a single Junction ID (Instance) to allow duplicate items within one campaign.',
			'sql' => [
				// --- 1. MILESTONES: Add the new instance-based foreign keys ---
				"ALTER TABLE `ce_campaign_milestone_users_progress` ADD COLUMN `ce_campaign_milestone_id` int(11) DEFAULT NULL AFTER `ce_milestone_id`",
				"ALTER TABLE `ce_campaign_milestone_progress_entries` ADD COLUMN `ce_campaign_milestone_id` int(11) DEFAULT NULL AFTER `ce_milestone_id`",
				"ALTER TABLE `ce_user_completed_milestones` ADD COLUMN `ce_campaign_milestone_id` int(11) DEFAULT NULL AFTER `milestoneId`",

				// --- 2. MILESTONES: Data Migration (Map coordinates to unique instance IDs) ---
				"UPDATE `ce_campaign_milestone_users_progress` up
					JOIN `ce_campaign_milestones` cm ON up.ce_campaign_id = cm.campaignId AND up.ce_milestone_id = cm.milestoneId
					SET up.ce_campaign_milestone_id = cm.id",
				"UPDATE `ce_campaign_milestone_progress_entries` pe
					JOIN `ce_campaign_milestones` cm ON pe.ce_campaign_id = cm.campaignId AND pe.ce_milestone_id = cm.milestoneId
					SET pe.ce_campaign_milestone_id = cm.id",
				"UPDATE `ce_user_completed_milestones` ucm
					JOIN `ce_campaign_milestones` cm ON ucm.campaignId = cm.campaignId AND ucm.milestoneId = cm.milestoneId
					SET ucm.ce_campaign_milestone_id = cm.id",

				// --- 3. MILESTONES: Cleanup and Constraint Enforcement ---
				"ALTER TABLE `ce_campaign_milestone_users_progress`
					DROP COLUMN `ce_campaign_id`,
					DROP COLUMN `ce_milestone_id`",
				"ALTER TABLE `ce_campaign_milestone_progress_entries`
					DROP COLUMN `ce_campaign_id`,
					DROP COLUMN `ce_milestone_id`",
				"ALTER TABLE `ce_user_completed_milestones`
					DROP COLUMN `campaignId`,
					DROP COLUMN `milestoneId`",
				"ALTER TABLE `ce_milestone` DROP COLUMN `campaignId`",

			]
		],

		//other

	];
}

function migrateEventFieldSelectValuesToCamelCase(): void {
	require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
	global $aspen_db;

	$result = $aspen_db->query("
		SELECT eef.id, eef.value, ef.allowableValues
		FROM event_event_field eef
		INNER JOIN event_field ef ON eef.eventFieldId = ef.id
		WHERE ef.allowableValues IS NOT NULL
		AND ef.allowableValues != ''
		AND eef.value REGEXP '^[0-9]+$'
	");

	if (!$result) {
		return;
	}

	$stmt = $aspen_db->prepare("UPDATE event_event_field SET value = :value WHERE id = :id");

	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$allowableValues = array_map('trim', explode("\n", $row['allowableValues']));
		$index = (int)$row['value'];
		if (isset($allowableValues[$index])) {
			$camelCaseValue = StringUtils::toCamelCase($allowableValues[$index]);
			$stmt->execute([':value' => $camelCaseValue, ':id' => $row['id']]);
		}
	}
}
