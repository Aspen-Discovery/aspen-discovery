<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_02_00(): array {
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
		'force_reindex_of_all_titles_in_lists' => [
			'title' => 'Force Reindex of All Titles in Lists',
			'description' => 'Force Reindex of All Titles in Lists',
			'sql' => [
				"INSERT INTO grouped_work_scheduled_index (permanent_id, indexAfter) SELECT sourceId, UNIX_TIMESTAMP() from user_list_entry where source = 'GroupedWork' and length(sourceId) = 40"
			]
		], //force_reindex_of_all_titles_in_lists
		'admin_property_search_index' => [
			'title' => 'Admin Property Search Index',
			'description' => 'Create a table to store the admin property search index',
			'sql' => [
				"DROP TABLE IF EXISTS `admin_property_search_entries`",
				"CREATE TABLE IF NOT EXISTS admin_property_search_entries (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					module VARCHAR(100) NOT NULL,
					action VARCHAR(100) NOT NULL,
					toolTitle VARCHAR(100) NOT NULL,
					section VARCHAR(255) DEFAULT '',
					propertyName VARCHAR(100) NOT NULL,
					label VARCHAR(100) NOT NULL,
					keywords TEXT NOT NULL,
					requiredModule VARCHAR(100) NOT NULL DEFAULT '',
					requiredPermissions TEXT NOT NULL DEFAULT '',
					UNIQUE KEY property (module, action, propertyName)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
			]
		], //admin_property_search_index
		'force_regrouping_of_hoopla' => [
			'title' => 'Force Regrouping of Hoopla',
			'description' => 'Force Regrouping of Hoopla',
			'sql' => [
				"UPDATE hoopla_settings set regroupAllRecords = 1"
			]
		], //force_regrouping_of_hoopla

		//kirstien
		'aspen_lida_home_screen_links_permissions' => [
			'title' => 'Aspen LiDA Home Screen Link Permissions',
			'description' => 'Create permissions for managing Aspen LiDA Home Screen Links.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
				('Aspen LiDA', 'Administer All Aspen LiDA Home Screen Links', '', 160, 'Allows the user to manage Aspen LiDA Home Screen Links for all libraries.'),
				('Aspen LiDA', 'Administer Library Aspen LiDA Home Screen Links', '', 161, 'Allows the user to manage Aspen LiDA Home Screen Links for their home library.')
				"
			],
		],
		//aspen_lida_home_screen_links_permissions
		'aspen_lida_home_screen_links_role_permissions' => [
			'title' => 'Aspen LiDA Home Screen Link Role Permission',
			'description' => 'Assign Aspen LiDA Home Screen Link permission to OPAC Admin role.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Aspen LiDA Home Screen Links'))",
			],
		],
		//aspen_lida_home_screen_links_role_permissions
		'aspen_lida_home_screen_links_tables' => [
			'title' => 'Aspen LiDA Home Screen Link Settings Tables',
			'description' => 'Create tables to store Aspen LiDA Home Screen Link settings.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `aspen_lida_home_screen_link_group` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(50) NOT NULL,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
				"CREATE TABLE IF NOT EXISTS `aspen_lida_home_screen_link_group_entry` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`homeScreenLinkGroupId` int(11) NOT NULL,
				`homeScreenLinkId` int(11) NOT NULL,
				`weight` int(11) NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				KEY `homeScreenLinkGroupId` (`homeScreenLinkGroupId`),
				KEY `homeScreenLinkId` (`homeScreenLinkId`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
				"CREATE TABLE IF NOT EXISTS `aspen_lida_home_screen_link` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`title` varchar(100) NOT NULL,
				`textId` varchar(100) DEFAULT NULL,
				`userId` int(11) DEFAULT NULL,
				`sharing` enum('everyone','library','private') NOT NULL DEFAULT 'everyone',
				`libraryId` int(11) DEFAULT NULL,
				`typeOfIcon` enum('materialIcon','uploadIcon') NOT NULL DEFAULT 'materialIcon',
				`materialIcon` varchar(100) DEFAULT NULL,
				`uploadIcon` varchar(255) DEFAULT NULL,
				`linkType` enum('deepLink','externalLink') NOT NULL DEFAULT 'deepLink',
				`deepLinkPath` varchar(100) DEFAULT NULL,
				`deepLinkId` varchar(100) DEFAULT NULL,
				`linkUrl` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `userId` (`userId`),
				KEY `libraryId` (`libraryId`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			],
		],
		//aspen_lida_home_screen_links_tables
		'aspen_lida_home_screen_links_group_id_storage' => [
			'title' => 'Aspen LiDA Home Screen Links Group IDs to Libraries and Locations',
			'description' => 'Add columns for Aspen LiDA Home Screen Links Group IDs to Libraries and Locations.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE `library` ADD `lidaHomeScreenLinkGroupId` INT(11) DEFAULT -1;",
				"ALTER TABLE `location` ADD `lidaHomeScreenLinkGroupId` INT(11) DEFAULT -1;",
			],
		],
		//aspen_lida_home_screen_links_group_id_storage
		'add_require_confirmation_to_sco_custom_message' => [
			'title' => 'Add option to require confirmation to self-checkout completion messages',
			'description' => 'Add option to require patron to confirm the self-checkout completion message.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE self_check_completion_message ADD COLUMN requireConfirmation TINYINT DEFAULT 0",
			]
		],
		//add_require_confirmation_to_sco_custom_message
		'aspen_lida_home_screen_links_groups_permissions' => [
			'title' => 'Aspen LiDA Home Screen Link Groups Permissions',
			'description' => 'Create permissions for managing Aspen LiDA Home Screen Links Groups.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
				('Aspen LiDA', 'Administer Selected Aspen LiDA Home Screen Link Groups', '', 161, 'Allows the user to view and edit only the Aspen LiDA Home Screen Link Groups they are assigned to.')
				"
			],
		],
		//aspen_lida_home_screen_links_groups_permissions
		'aspen_lida_home_screen_link_group_users_table' => [
			'title' => 'Aspen LiDA Home Screen Link Group Users Table',
			'description' => 'Create tables to store users who can edit Home Screen Link Groups.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `aspen_lida_home_screen_link_group_users` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`homeScreenLinkGroupId` int(11),
				`userId` int(11),
				PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			],
		],
		//aspen_lida_home_screen_link_group_users_table


		//kodi
		'sierra_self_registration_form_no_comma' => [
			'title' => 'Update Sierra Self Registration Form - no comma',
			'description' => 'Add option for Sierra Self Registration Form - disabling the comma between city and state',
			'sql' => [
				"ALTER TABLE self_registration_form_sierra ADD COLUMN noCommaInAddress tinyint(1) DEFAULT 0",
			]
		], //sierra_self_registration_form_no_comma
		'create_cloudsource_table' => [
			'title' => 'Create CloudSource OA Table',
			'description' => 'Create DB table for CloudSource OA',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS cloudsource_setting (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255),
					baseUrl VARCHAR(255),
					accessToken VARCHAR(255),
					profileKey VARCHAR(255),
					showInExploreMore tinyint(1) DEFAULT 1
				) ENGINE=INNODB",
			]
		], //create_cloudsource_table
		'add_cloudsource_permissions' => [
			'title' => 'Add Cloud Source Permissions',
			'description' => 'Add Cloud Source Permissions',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('', 'Administer CloudSource OA', 'CloudSource', 40, 'Allows users to administer CloudSource OA settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer CloudSource OA'))",
			]
		], //add_cloudsource_permissions
		'add_cloudsource_module' => [
			'title' => 'Create CloudSource OA module',
			'description' => 'Setup module for CloudSource OA',
			'sql' => [
				"INSERT INTO modules (name) VALUES ('CloudSource')",
			],
		], //add_cloudsource_module
		'library_location_cloudsource_settings' => [
			'title' => 'Library Location CloudSource Settings',
			'description' => 'Create tables for library and location CloudSource OA settings',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS library_cloudsource_setting (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11),
					cloudsourceSettingId INT(11)
				) ENGINE=INNODB",
				"CREATE TABLE IF NOT EXISTS location_cloudsource_setting (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					locationId INT(11),
					cloudsourceSettingId INT(11)
				) ENGINE=INNODB",
			]
		], //library_location_cloudsource_settings
		'sierra_self_registation_form_city_dropdown' => [
			'title' => 'Update Sierra Self Registration Form - City Dropdown Selection',
			'description' => 'Add toggle to use cities defined in municipalities as a dropdown option in the Sierra Self Registration Form.',
			'sql' => [
				"ALTER TABLE self_registration_form_sierra ADD COLUMN cityDropdown tinyint(1) DEFAULT 0",
			]
		], //sierra_self_registation_form_city_dropdown
		'fix_cloudsource_permissions' => [
			'title' => 'Add Cloud Source Permission Section',
			'description' => 'Add Cloud Source permission section so it does not appear under a blank accordion',
			'sql' => [
				"UPDATE permissions SET sectionName = 'CloudSource OA' WHERE name = 'Administer CloudSource OA'",
			]
		], //add_cloudsource_permissions

		//yanjun
		'overdrive_qr_sessions' => [
			'title' => 'OverDrive QR Sessions Table',
			'description' => 'Store QR code authentication tokens for OverDrive/Sora users.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS overdrive_qr_sessions (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT NOT NULL,
					settingId INT NOT NULL,
					accessToken TEXT,
					refreshToken TEXT,
					tokenType VARCHAR(50),
					scope TEXT,
					expiresAt INT,
					created INT,
					updated INT,
					UNIQUE KEY user_setting (userId, settingId),
					INDEX settingId (settingId),
					CONSTRAINT fk_overdrive_qr_user FOREIGN KEY (userId) REFERENCES user(id) ON DELETE CASCADE,
					CONSTRAINT fk_overdrive_qr_setting FOREIGN KEY (settingId) REFERENCES overdrive_settings(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
			]
		], //overdrive_qr_sessions
		'overdrive_settings_enable_qr' => [
			'title' => 'Enable QR authentication flag',
			'description' => 'Add flag to OverDrive settings to toggle QR code authentication features.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE overdrive_settings ADD COLUMN enableQRCodeAuth TINYINT(1) DEFAULT 0"
			]
		], //overdrive_settings_enable_qr

		//imani
		'add_configuration_for_index_process' => [
			'title' => 'Add configuration for solr soft commits',
			'description' => 'Add additional configuration for how records are processed into solr',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN indexCommitInterval INT DEFAULT 10000",
			]
		],
		//galen

		//alexander
		'add_admin_view_permission_to_community_engagement' => [
			'title' => 'Add Admin View Permission to Community Engagement',
			'description' => 'Add a new permission for admin view page for commnuity engagement',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Community Engagement', 'View Community Engagement Admin View', 'Community Engagement', 200, 'Allows the user to view the Community Engagement Admin View.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES 
					((SELECT roleId FROM roles WHERE name='opacAdmin'), 
						(SELECT id FROM permissions WHERE name='View Community Engagement Admin View'))"
			]
		],// add_admin_view_permission_to_community_engagement
		'community_engagement_section_rename' => [
			'title' => 'Community Engagement - Move Permissions to Community Engagement Section',
			'description' => 'Updates the sectionName for Community Engagement permissions from their current sections to Community Engagement',
			'continueOnError' => false,
			'sql' => [
				"UPDATE permissions SET sectionName = 'Community Engagement' WHERE name = 'View Community Engagement Dashboard'",
				"UPDATE permissions SET sectionName = 'Community Engagement' WHERE name = 'Administer Community Engagement Module'"
			]
		], //community_engagement_section_rename

		//chloe

		//mark j
		'offer_immediate_hold_freeze' => [
			'title' => 'Library - Add the Ability to Freeze Holds Immediately',
			'description' => 'Within Library Settings, libraries can choose to offer patrons the ability to freeze their holds immediately.',
			'sql' => [
				"ALTER TABLE library ADD COLUMN offerImmediateHoldFreeze tinyint(1) NOT NULL DEFAULT 0",
			]
		],
		'prompt_to_freeze_holds_immediately' => [
			'title' => 'User - Add the Choice to Have a Prompt to Freeze Holds Immediately',
			'description' => 'Patrons will gain the choice within their Account Settings to have the system prompt them to freeze their holds immediately. (Requires that the library first offers this setting.)',
			'sql' => [
				"ALTER TABLE user ADD COLUMN promptToFreezeHoldsImmediately tinyint(1) NOT NULL DEFAULT 0",
			]
		], //offer_immediate_hold_freeze
		'allow_focus_color_set_for_themes' => [
			'title' => 'Theme - Add the Ability to Set a Focus Color',
			'description' => 'Within themes, libraries can now set a color that will be used for focus states. This would be useful for accessibility purposes and if the patron is using keyboard navigation.',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN focusColor char(7) DEFAULT '#3174AF'",
				"ALTER TABLE themes ADD COLUMN focusColorDefault TINYINT(1) DEFAULT 1",
				"ALTER TABLE themes ADD COLUMN focusBorderWidth varchar(6) DEFAULT NULL",
			]
		], //allow_focus_color_set_for_themes
		'share_tools_add_granularity' => [
			'title' => 'Library and Locations - Add more granularity to the sharing tools (Facebook, X, etc.)',
			'description' => 'Within Library Settings (and location settings), libraries can now choose specifically which social platforms they allow their customers to share on.',
			'sql' => [
				"ALTER TABLE library CHANGE COLUMN showShareOnExternalSites showShareOnX TINYINT DEFAULT 1",
				"ALTER TABLE location CHANGE COLUMN showShareOnExternalSites showShareOnX TINYINT DEFAULT 1",
				"ALTER TABLE library ADD showShareOnFacebook TINYINT DEFAULT 1",
				"UPDATE library SET showShareOnFacebook = IF(showShareOnX = 1, 1, 0)",
				"ALTER TABLE location ADD showShareOnFacebook TINYINT DEFAULT 1",
				"UPDATE location SET showShareOnFacebook = IF(showShareOnX = 1, 1, 0)",
				"ALTER TABLE library ADD showShareOnPinterest TINYINT DEFAULT 1",
				"UPDATE library SET showShareOnPinterest = IF(showShareOnX = 1, 1, 0)",
				"ALTER TABLE location ADD showShareOnPinterest TINYINT DEFAULT 1",
				"UPDATE location SET showShareOnPinterest = IF(showShareOnX = 1, 1, 0)",
				"ALTER TABLE library ADD showShareOnLink TINYINT DEFAULT 1",
				"UPDATE library SET showShareOnLink = IF(showShareOnX = 1, 1, 0)",
				"ALTER TABLE location ADD showShareOnLink TINYINT DEFAULT 1",
				"UPDATE location SET showShareOnLink = IF(showShareOnX = 1, 1, 0)",
			]
		],  //share_tools_add_granularity

		//lucas
		'nightly_index_trigger_tracking' => [
			'title' => 'Nightly Index Trigger Tracking',
			'description' => 'Add a column to system_variables to track what triggered the nightly reindex.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN nightlyIndexTrigger TEXT DEFAULT NULL",
			]
		], //nightly_index_trigger_tracking


		//tomas

		//other


	];
}
