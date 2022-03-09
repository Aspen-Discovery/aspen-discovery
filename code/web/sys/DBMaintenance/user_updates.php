<?php /** @noinspection SqlResolve */

function getUserUpdates()
{
	return array(
		'roles_2' => array(
			'title' => 'Roles 2',
			'description' => 'Add new role for locationReports',
			'sql' => array(
				"INSERT INTO roles (name, description) VALUES ('locationReports', 'Allows the user to view reports for their location.')",
			),
		),

		'user_display_name' => array(
			'title' => 'User display name',
			'description' => 'Add displayName field to User table to allow users to have aliases',
			'sql' => array(
				"ALTER TABLE user ADD displayName VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_phone' => array(
			'title' => 'User phone',
			'description' => 'Add phone field to User table to allow phone numbers to be displayed for Materials Requests',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD phone VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_phone_length' => array(
			'title' => 'Increase User phone length',
			'description' => 'Increase length of the user phone',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user CHANGE COLUMN phone phone VARCHAR(190) NOT NULL DEFAULT ''",
			),
		),

		'user_ilsType' => array(
			'title' => 'User Type',
			'description' => 'Add patronType field to User table to allow for functionality to be controlled based on the type of patron within the ils',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD patronType VARCHAR( 30 ) NOT NULL DEFAULT ''",
			),
		),

		'user_overdrive_email' => array(
			'title' => 'User OverDrive Email',
			'description' => 'Add overdriveEmail field to User table to allow for patrons to use a different email fo notifications when their books are ready',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD overdriveEmail VARCHAR( 250 ) NOT NULL DEFAULT ''",
				"ALTER TABLE user ADD promptForOverdriveEmail TINYINT DEFAULT 1",
				"UPDATE user SET overdriveEmail = email WHERE overdriveEmail = ''"
			),
		),

		'user_preferred_library_interface' => array(
			'title' => 'User Preferred Library Interface',
			'description' => 'Add preferred library interface to ',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD preferredLibraryInterface INT(11) DEFAULT NULL",
			),
		),

		'user_track_reading_history' => array(
			'title' => 'User Track Reading History',
			'description' => 'Add Track Reading History ',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD trackReadingHistory TINYINT DEFAULT 0",
				"ALTER TABLE user ADD initialReadingHistoryLoaded TINYINT DEFAULT 0",
			),
		),

		'user_preference_review_prompt' => array(
			'title' => 'User Preference Prompt for Reviews',
			'description' => 'Users may opt out of doing a review after giving a rating permanently',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE `user` ADD `noPromptForUserReviews` TINYINT(1) DEFAULT 0",
			),
		),

		'user_account' => array(
			'title' => 'User Account Source',
			'description' => 'Store the source of a user account so we can accommodate multiple ILSs',
			'sql' => array(
				"ALTER TABLE `user` ADD `source` VARCHAR(50) DEFAULT 'ils'",
				"ALTER TABLE `user` DROP INDEX `username`",
				"ALTER TABLE `user` ADD UNIQUE username(`source`, `username`)",
			),
		),

		'user_linking' => array(
			'title' => 'Setup linking of user accounts',
			'description' => 'Setup linking of user accounts.  This is a one way link.',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS `user_link` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`primaryAccountId` int(11),
					`linkedAccountId` int(11),
					PRIMARY KEY (`id`),
					UNIQUE KEY `user_link` (`primaryAccountId`, `linkedAccountId`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8",
			),
		),

		'user_linking_1' => array(
			'title' => 'Fix User Linking Table Settings',
			'description' => 'Set Id columns to require a value (can not be null).',
			'sql' => array(
				"ALTER TABLE `user_link` 
					CHANGE COLUMN `primaryAccountId` `primaryAccountId` INT(11) NOT NULL,
					CHANGE COLUMN `linkedAccountId` `linkedAccountId` INT(11) NOT NULL;",
			),
		),

		'user_linking_disable_link' => array(
			'title' => 'Allow user links to be temporarily disabled',
			'description' => 'Allow user links to be temporarily disabled.',
			'sql' => array(
				"ALTER TABLE `user_link` ADD COLUMN `linkingDisabled` TINYINT(1) DEFAULT 0",
			),
		),

		'user_link_blocking' => array(
			'title' => 'Setup blocking controls for the linking of user accounts',
			'description' => 'Setup for the blocking of linking user accounts. Either an account can not link to any account, or a specific account can link to a specific account.',
			'sql' => array(
				"CREATE TABLE `user_link_blocks` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`primaryAccountId` INT UNSIGNED NOT NULL,
					`blockedLinkAccountId` INT UNSIGNED NULL COMMENT 'A specific account primaryAccountId will not be linked to.',
					`blockLinking` TINYINT UNSIGNED NULL COMMENT 'Indicates primaryAccountId will not be linked to any other accounts.',
					PRIMARY KEY (`id`))
					ENGINE = InnoDB
					DEFAULT CHARACTER SET = utf8;"
			),
		),

		'user_reading_history_index_source_id' => array(
			'title' => 'Index source Id in user reading history',
			'description' => 'Index source Id in user reading history',
			'sql' => array(
				"ALTER TABLE user_reading_history_work ADD INDEX sourceId(sourceId)"
			),
		),

		'user_reading_history_index' => [
			'title' => 'Add Reading History Index',
			'description' => 'Add index for userid and grouped work',
			'sql' => [
				'ALTER TABLE user_reading_history_work ADD INDEX user_work(userId, groupedWorkPermanentId)'
			]
		],

		'user_reading_history_work_index' => [
			'title' => 'Add Reading History Index',
			'description' => 'Add index for userid and grouped work',
			'sql' => [
				'ALTER TABLE user_reading_history_work ADD INDEX groupedWorkPermanentId(groupedWorkPermanentId)'
			]
		],

		'user_hoopla_confirmation_checkout_prompt' => array(
			'title' => 'Hoopla Checkout Confirmation Prompt',
			'description' => 'Stores user preference whether or not to prompt for confirmation before checking out a title from Hoopla',
			'sql' => array(
				"ALTER TABLE `user` ADD COLUMN `hooplaCheckOutConfirmation` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;"
			),
		),

		'user_remove_default_created' => array(
			'title' => 'Remove default for user created field',
			'description' => 'Remove default for user created field (not correct for later versions of MySQL',
			'sql' => array(
				"ALTER TABLE `user` CHANGE COLUMN created created DATETIME not null;"
			),
		),

		'user_add_rbdigital_id' => array(
			'title' => 'User RBdigital Id',
			'description' => 'Stores user rbdigital id for a user',
			'sql' => array(
				"ALTER TABLE user ADD COLUMN rbdigitalId INT(11) DEFAULT -1;",
				"ALTER TABLE user ADD COLUMN rbdigitalLastAccountCheck INT(11)",
			),
		),

		'user_add_rbdigital_username_password' => array(
			'title' => 'User RBdigital Username and Password',
			'description' => 'Stores rbdigital username and password for a user for automatic login',
			'sql' => array(
				"ALTER TABLE user ADD COLUMN rbdigitalUsername VARCHAR(50);",
				"ALTER TABLE user ADD COLUMN rbdigitalPassword VARCHAR(50)",
			),
		),

		'user_languages' => [
			'title' => 'User Language Preferences',
			'description' => 'Stores information about preferences for the user related to language',
			'sql' => [
				"ALTER TABLE user ADD COLUMN interfaceLanguage VARCHAR(3) DEFAULT 'en'",
				"ALTER TABLE user ADD COLUMN searchPreferenceLanguage TINYINT(1) DEFAULT '-1'",
			],
		],

		'user_rememberHoldPickupLocation' => [
			'title' => 'User Remember Hold Pickup Location',
			'description' => 'Add a switch to determine if the user\'s hold pickup location should be remembered',
			'sql' => [
				"ALTER TABLE user ADD COLUMN rememberHoldPickupLocation TINYINT(1) DEFAULT '0'",
				"ALTER TABLE user ADD COLUMN alwaysHoldNextAvailable TINYINT(1) DEFAULT '0'",
			],
		],

		'user_messages' => [
			'title' => 'User messaging',
			'description' => 'Add the ability to send messages to users',
			'sql' => [
				'CREATE TABLE user_messages (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					userId INT(11),
					messageType VARCHAR(50),
					messageLevel ENUM (\'success\', \'info\', \'warning\', \'danger\') DEFAULT \'info\',
					message MEDIUMTEXT,
					isDismissed TINYINT(1) DEFAULT 0,
					INDEX (userId, isDismissed)
				)'
			]
		],

		'user_message_actions' => [
			'title' => 'Add actions to user messaging',
			'description' => 'Let users take actions (other than dismissing) on messages',
			'sql' => [
				'ALTER TABLE user_messages ADD COLUMN action1 VARCHAR(255)',
				'ALTER TABLE user_messages ADD COLUMN action1Title VARCHAR(50)',
				'ALTER TABLE user_messages ADD COLUMN action2 VARCHAR(255)',
				'ALTER TABLE user_messages ADD COLUMN action2Title VARCHAR(50)',
			]
		],

		'user_overdrive_auto_checkout' => [
			'title' => 'Remove OverDrive Automatic Checkout',
			'description' => 'Remove OverDrive auto checkout now that it has been deprecated',
			'sql' => [
				'ALTER TABLE user DROP COLUMN overdriveAutoCheckout',
			]
		],

		'user_locked_filters' => [
			'title' => 'User Locked Filters',
			'description' => 'Add a column to store locked filters/facets to the interface',
			'sql' => [
				'ALTER TABLE user ADD COLUMN lockedFacets TEXT',
			]
		],

		'user_payments' => [
			'title' => 'User Payments',
			'description' => 'Add a table to store information about payments that have been submitted through Aspen Discovery',
			'sql' => [
				'CREATE TABLE user_payments (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					userId INT(11),
					paymentType VARCHAR(20),
					orderId VARCHAR(50),
					completed TINYINT(1),
					finesPaid VARCHAR(255),
					totalPaid FLOAT,
					INDEX (userId, paymentType, completed),
					INDEX (paymentType, orderId)
				)'
			]
		],

		'user_payments_carlx' => [
			'title' => 'User payments CarlX',
			'description' => 'Add columns to user_payments to support CarlX credit card processing',
			'sql' => [
				'ALTER TABLE user_payments ADD COLUMN transactionDate INT(11)',
			]
		],

		'user_payments_finesPaid' => [
			'title' => 'User payments finesPaid embiggening',
			'description' => 'Increase finesPaid column space to 8K',
			'sql' => [
				"ALTER TABLE user_payments CHANGE finesPaid finesPaid VARCHAR(8192) NOT NULL DEFAULT ''",
			]
		],

		'user_display_name_length' => array(
			'title' => 'User display name length',
			'description' => 'Increase displayName field in the User table',
			'sql' => array(
				"ALTER TABLE user CHANGE displayName displayName VARCHAR( 60 ) NOT NULL DEFAULT ''",
			),
		),

		'user_last_name_length' => array(
			'title' => 'User last name length',
			'description' => 'Increase lastName field in the User table',
			'sql' => array(
				"ALTER TABLE user CHANGE lastname lastname VARCHAR( 100 ) NOT NULL DEFAULT ''",
			),
		),

		'make_nyt_user_list_publisher' => [
			'title' => 'Make NYT User a list publisher',
			'description' => 'Make NYT User a list publisher so results show in search',
			'sql' => array(
				'makeNytUserListPublisher',
			),
		],

		'user_list_entry_add_additional_types' => [
			'title' => 'Add additional types to list entries',
			'description' => 'Allow any type of resource to be added to user lists',
			'sql' => [
				'ALTER TABLE user_list_entry CHANGE COLUMN groupedWorkPermanentId sourceId VARCHAR(36)',
				"ALTER TABLE user_list_entry ADD COLUMN source VARCHAR(20) NOT NULL default 'GroupedWork'",
				"ALTER TABLE user_list_entry ADD INDEX source(source, sourceId)"
			]
		],

		'user_list_import_information' => [
			'title' => 'User List Import Information',
			'description' => 'Add information about where list information was imported from',
			'sql' => [
				'ALTER TABLE user_list ADD COLUMN importedFrom VARCHAR(20)',
				'ALTER TABLE user_list_entry ADD COLUMN importedFrom VARCHAR(20)',
			]
		],

		'user_last_list_used' => [
			'title' => 'User Last Used List',
			'description' => 'Store the last list the user edited',
			'sql' => [
				"ALTER TABLE user ADD COLUMN lastListUsed INT(11) DEFAULT -1",
			]
		],

		'user_last_login_validation' => [
			'title' => 'User Last Login Validation',
			'description' => 'Store when the user was last validated so we don\'t need to constantly revalidate in the app',
			'sql' => [
				'ALTER TABLE user ADD COLUMN lastLoginValidation INT(11) DEFAULT -1',
			]
		],

		'user_secondary_library_card' => [
			'title' => 'User Secondary Library Card',
			'description' => 'Add the ability to define a secondary library card for a user',
			'sql' => [
				"ALTER TABLE user ADD COLUMN alternateLibraryCard VARCHAR(50) DEFAULT ''",
				"ALTER TABLE user ADD COLUMN alternateLibraryCardPassword VARCHAR(256) DEFAULT ''",
				"ALTER TABLE user CHANGE COLUMN cat_password cat_password VARCHAR(256) DEFAULT ''",
			]
		],

		'user_password_length' => [
			'title' => 'User Password length',
			'description' => 'Increase maximum password length to match cat_password',
			'sql' => [
				"ALTER TABLE user CHANGE COLUMN password password VARCHAR(60) DEFAULT ''",
			]
		],

		'user_update_messages' => [
			'title' => 'User Update Messages',
			'description' => 'Add a field to store user update messages to avoid storing them within a session',
			'sql' => [
				'ALTER TABLE user ADD COLUMN updateMessage TEXT',
				'ALTER TABLE user ADD COLUMN updateMessageIsError TINYINT(0)'
			]
		],

		'user_permissions' => [
			'title' => 'User Permissions',
			'description' => 'Setup permissions table and create discrete permissions for Aspen',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE permissions (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(75) NOT NULL UNIQUE ,
					sectionName VARCHAR(75) NOT NULL,
					requiredModule VARCHAR(50) NOT NULL DEFAULT '',
					weight INT NOT NULL DEFAULT 0,
					description VARCHAR(250) NOT NULL
				) ENGINE INNODB",
				'CREATE TABLE role_permissions (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					roleId INT(11) NOT NULL,
					permissionId INT(11) NOT NULL,
					INDEX roleId(roleId),
					UNIQUE (roleId, permissionId)
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					 ('System Administration', 'Administer Modules', '', 0, 'Allow information about Aspen Discovery Modules to be displayed and enabled or disabled.')
					,('System Administration', 'Administer Users', '', 10, 'Allows configuration of who has administration privileges within Aspen Discovery. <i>Give to trusted users, this has security implications.</i>')
					,('System Administration', 'Administer Permissions', '', 15, 'Allows configuration of the roles within Aspen Discovery and what each role can do. <i>Give to trusted users, this has security implications.</i>')
					,('System Administration', 'Run Database Maintenance', '', 20, 'Controls if the user can run database maintenance or not.')
					,('System Administration', 'Administer SendGrid', '', 30, 'Controls if the user can change SendGrid settings. <em>This has potential security and cost implications.</em>')
					,('System Administration', 'Administer System Variables','', 40, 'Controls if the user can change system variables.')
					,('Reporting', 'View System Reports', '', 0, 'Controls if the user can view System Reports that show how Aspen Discovery performs and how background tasks are operating. Includes Indexing Logs and Dashboards.')
					,('Reporting', 'View Indexing Logs', '', 10, 'Controls if the user can view Indexing Logs for the ILS and eContent.')
					,('Reporting', 'View Dashboards', '', 20, 'Controls if the user can view Dashboards showing usage information.')
					,('Theme & Layout', 'Administer All Themes', '', 0, 'Allows the user to control all themes within Aspen Discovery.')
					,('Theme & Layout', 'Administer Library Themes', '', 10, 'Allows the user to control theme for their home library within Aspen Discovery.')
					,('Theme & Layout', 'Administer All Layout Settings', '', 20, 'Allows the user to view and change all layout settings within Aspen Discovery.')
					,('Theme & Layout', 'Administer Library Layout Settings', '', 30, 'Allows the user to view and change layout settings for their home library within Aspen Discovery.')
					,('Primary Configuration', 'Administer All Libraries', '', 0, 'Allows the user to control settings for all libraries within Aspen Discovery.')
					,('Primary Configuration', 'Administer Home Library', '', 10, 'Allows the user to control settings for their home library')
					,('Primary Configuration', 'Administer All Locations', '', 20, 'Allows the user to control settings for all locations.')
					,('Primary Configuration', 'Administer Home Library Locations', '', 30, 'Allows the user to control settings for all locations that are part of their home library.')
					,('Primary Configuration', 'Administer Home Location', '', 40, 'Allows the user to control settings for their home location.')
					,('Primary Configuration', 'Administer IP Addresses', '', 50, 'Allows the user to administer IP addresses for Aspen Discovery. <em>This has potential security implications</em>')
					,('Primary Configuration', 'Administer Patron Types', '', 60, 'Allows the user to administer how patron types in the ILS are handled within for Aspen Discovery. <i>Give to trusted users, this has security implications.</i>')
					,('Primary Configuration', 'Administer Account Profiles', '', 70, 'Allows the user to administer patrons are loaded from the ILS and/or the database. <i>Give to trusted users, this has security implications.</i>')
					,('Primary Configuration', 'Block Patron Account Linking', '', 80, 'Allows the user to prevent users from linking to other users.')
					,('Materials Requests', 'Manage Library Materials Requests', '', 0, 'Allows the user to update and process materials requests for patrons.')
					,('Materials Requests', 'Administer Materials Requests', '', 10, 'Allows the user to configure the materials requests system for their library.')
					,('Materials Requests', 'View Materials Requests Reports', '', 20, 'Allows the user to view reports about the materials requests system for their library.')
					,('Materials Requests', 'Import Materials Requests', '', 30, 'Allows the user to import materials requests from older systems. <em>Not recommended in most cases unless an active conversion is being done.</em>')
					,('Languages and Translations', 'Administer Languages', '', 0, 'Allows the user to control which languages are available for the Aspen Discovery interface.')
					,('Languages and Translations', 'Translate Aspen', '', 10, 'Allows the user to translate the Aspen Discovery interface.')
					,('Cataloging & eContent', 'Manually Group and Ungroup Works', '', 0, 'Allows the user to manually group and ungroup works.')
					,('Cataloging & eContent', 'Set Grouped Work Display Information', '', 10, 'Allows the user to override title, author, and series information for a grouped work.')
					,('Cataloging & eContent', 'Force Reindexing of Records', '', 20, 'Allows the user to force individual records to be indexed.')
					,('Cataloging & eContent', 'Upload Covers', '', 30, 'Allows the user to upload covers for a record.')
					,('Cataloging & eContent', 'Upload PDFs', '', 40, 'Allows the user to upload PDFs for a record.')
					,('Cataloging & eContent', 'Upload Supplemental Files', '', 50, 'Allows the user to upload supplemental for a record.')
					,('Cataloging & eContent', 'Download MARC Records', '', 52, 'Allows the user to download MARC records for individual records.')
					,('Cataloging & eContent', 'View ILS records in native OPAC', '', 55, 'Allows the user to view ILS records in the native OPAC for the ILS if available.')
					,('Cataloging & eContent', 'View ILS records in native Staff Client', '', 56, 'Allows the user to view ILS records in the staff client for the ILS if available.')
					,('Cataloging & eContent', 'Administer Indexing Profiles', '', 60, 'Allows the user to administer Indexing Profiles to define how record from the ILS are indexed in Aspen Discovery.')
					,('Cataloging & eContent', 'Administer Translation Maps', '', 70, 'Allows the user to administer how fields within the ILS are mapped to Aspen Discovery.')
					,('Cataloging & eContent', 'Administer Loan Rules', '', 80, 'Allows the user to administer load loan rules and loan rules into Aspen Discovery (Sierra & Millenium only).')
					,('Cataloging & eContent', 'View Offline Holds Report', '', 90, 'Allows the user to see any holds that were entered while the ILS was offline.')
					,('Cataloging & eContent', 'Administer Axis 360', 'Axis 360', 100, 'Allows the user configure Axis 360 integration for all libraries.')
					,('Cataloging & eContent', 'Administer Cloud Library', 'Cloud Library', 110, 'Allows the user configure Cloud Library integration for all libraries.')
					,('Cataloging & eContent', 'Administer EBSCO EDS', 'EBSCO EDS', 120, 'Allows the user configure EBSCO EDS integration for all libraries.')
					,('Cataloging & eContent', 'Administer Hoopla', 'Hoopla', 130, 'Allows the user configure Hoopla integration for all libraries.')
					,('Cataloging & eContent', 'Administer OverDrive', 'OverDrive', 140, 'Allows the user configure OverDrive integration for all libraries.')
					,('Cataloging & eContent', 'View OverDrive Test Interface', 'OverDrive', 150, 'Allows the user view OverDrive API information and call OverDrive for specific records.')
					,('Cataloging & eContent', 'Administer RBdigital', 'RBdigital', 160, 'Allows the user configure RBdigital integration for all libraries.')
					,('Cataloging & eContent', 'Administer Side Loads', 'Side Loads', 170, 'Controls if the user can administer side loads.')
					,('Grouped Work Display', 'Administer All Grouped Work Display Settings', '', 0, 'Allows the user to view and change all grouped work display settings within Aspen Discovery.')
					,('Grouped Work Display', 'Administer Library Grouped Work Display Settings', '', 10, 'Allows the user to view and change grouped work display settings for their home library within Aspen Discovery.')
					,('Grouped Work Display', 'Administer All Grouped Work Facets', '', 20, 'Allows the user to view and change all grouped work facets within Aspen Discovery.')
					,('Grouped Work Display', 'Administer Library Grouped Work Facets', '', 30, 'Allows the user to view and change grouped work facets for their home library within Aspen Discovery.')
					,('Local Enrichment', 'Administer All Browse Categories', '', 0, 'Allows the user to view and change all browse categories within Aspen Discovery.')
					,('Local Enrichment', 'Administer Library Browse Categories', '', 10, 'Allows the user to view and change browse categories for their home library within Aspen Discovery.')
					,('Local Enrichment', 'Administer All Collection Spotlights', '', 20, 'Allows the user to view and change all collection spotlights within Aspen Discovery.')
					,('Local Enrichment', 'Administer Library Collection Spotlights', '', 30, 'Allows the user to view and change collection spotlights for their home library within Aspen Discovery.')
					,('Local Enrichment', 'Administer All Placards', '', 40, 'Allows the user to view and change all placards within Aspen Discovery.')
					,('Local Enrichment', 'Administer Library Placards', '', 50, 'Allows the user to view and change placards for their home library within Aspen Discovery.')
					,('Local Enrichment', 'Moderate User Reviews', '', 60, 'Allows the delete any user review within Aspen Discovery.')
					,('Third Party Enrichment', 'Administer Third Party Enrichment API Keys', '', 0, 'Allows the user to define connection to external enrichment systems like Content Cafe, Syndetics, Google, Novelist etc.')
					,('Third Party Enrichment', 'Administer Wikipedia Integration', '', 10, 'Allows the user to control how authors are matched to Wikipedia entries.')
					,('Third Party Enrichment', 'View New York Times Lists', '', 20, 'Allows the user to view and update lists loaded from the New York Times.')
					,('Open Archives', 'Administer Open Archives', 'Open Archives', 0, 'Allows the user to administer integration with Open Archives repositories for all libraries.')
					,('Events', 'Administer Library Calendar Settings', 'Events', 10, 'Allows the user to administer integration with Library Calendar for all libraries.')
					,('Website Indexing', 'Administer Website Indexing Settings', 'Web Indexer', 0, 'Allows the user to administer the indexing of websites for all libraries.')
					,('Aspen Discovery Help', 'View Help Manual', '', 0, 'Allows the user to view the help manual for Aspen Discovery.')
					,('Aspen Discovery Help', 'View Release Notes', '', 10, 'Allows the user to view release notes for Aspen Discovery.')
					,('Aspen Discovery Help', 'Submit Ticket', '', 20, 'Allows the user to submit Aspen Discovery tickets.')
					,('Genealogy', 'Administer Genealogy', 'Genealogy', 0, 'Allows the user to add people, marriages, and obituaries to the genealogy interface.')
					,('User Lists', 'Include Lists In Search Results', '', 0, 'Allows the user to add public lists to search results.')
					,('User Lists', 'Edit All Lists', '', 10, 'Allows the user to edit public lists created by any user.')
				"
			]
		],

		'user_permission_defaults' => [
			'title' => 'Set Default Permissions for Roles',
			'description' => 'Update database tables with defaults from old roles',
			'sql' => [
				'updateDefaultPermissions'
			]
		],

		'user_assign_role_by_ptype' => [
			'title' => 'Assign Role by PType',
			'description' => 'Allow roles to be assigned automatically based on patron type',
			'sql' => [
				'ALTER TABLE ptype ADD COLUMN assignedRoleId INT(11) DEFAULT -1',
				"ALTER TABLE ptype ADD COLUMN restrictMasquerade TINYINT(1) DEFAULT 0"
			]
		],

		'allow_anyone_to_view_documentation' => [
			'title' => 'Allow anyone to view documentation',
			'description' => 'Remove permissions to view help manual and release notes',
			'sql' => [
				"DELETE FROM role_permissions where permissionId = (SELECT id from permissions where name='View Help Manual')",
				"DELETE from permissions where name = 'View Help Manual'",
				"DELETE FROM role_permissions where permissionId = (SELECT id from permissions where name='View Release Notes')",
				"DELETE from permissions where name = 'View Release Notes'"
			]
		],

		'masquerade_permissions' => [
			'title' => 'Create masquerade permissions and roles',
			'description' => 'Create masquerade permissions and roles',
			'sql' => [
				"INSERT INTO roles (name, description) VALUES 
					('Masquerader', 'Allows the user to masquerade as any other user.'),
					('Library Masquerader', 'Allows the user to masquerade as patrons of their home library only.'),
					('Location Masquerader', 'Allows the user to masquerade as patrons of their home location only.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Masquerade', 'Masquerade as any user', '', 0, 'Allows the user to masquerade as any other user including restricted patron types.'),
					('Masquerade', 'Masquerade as unrestricted patron types', '', 10, 'Allows the user to masquerade as any other user if their patron type is unrestricted.'),
					('Masquerade', 'Masquerade as patrons with same home library', '', 20, 'Allows the user to masquerade as patrons with the same home library including restricted patron types.'),
					('Masquerade', 'Masquerade as unrestricted patrons with same home library', '', 30, 'Allows the user to masquerade as patrons with the same home library if their patron type is unrestricted.'),
					('Masquerade', 'Masquerade as patrons with same home location', '', 40, 'Allows the user to masquerade as patrons with the same home location including restricted patron types.'),
					('Masquerade', 'Masquerade as unrestricted patrons with same home location', '', 50, 'Allows the user to masquerade as patrons with the same home location if their patron type is unrestricted.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Masquerader'), (SELECT id from permissions where name='Masquerade as any user'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Masquerader'), (SELECT id from permissions where name='Masquerade as patrons with same home library'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Location Masquerader'), (SELECT id from permissions where name='Masquerade as patrons with same home location'))",
				"UPDATE ptype set assignedRoleId = (SELECT roleId from roles where name='Masquerader') WHERE masquerade = 'any'",
				"UPDATE ptype set assignedRoleId = (SELECT roleId from roles where name='Library Masquerader') WHERE masquerade = 'library'",
				"UPDATE ptype set assignedRoleId = (SELECT roleId from roles where name='Location Masquerader') WHERE masquerade = 'location'",
				"ALTER TABLE ptype drop column masquerade"
			]
		],

		'test_roles_permission' => [
			'title' => 'Add permissions for testing roles',
			'description' => 'Add permissions for testing roles',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('System Administration', 'Test Roles', '', 17, 'Allows the user to use the test_role parameter to act as different role.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='userAdmin'), (SELECT id from permissions where name='Test Roles'))",
			]
		],

		'staff_ptypes' => [
			'title' => 'Staff patron types',
			'description' => 'Add the ability to treat specific patron types as staff',
			'sql' => [
				'ALTER TABLE ptype add column isStaff TINYINT(1) DEFAULT 0',
			]
		],

		'ptype_descriptions' => [
			'title' => 'PType descriptions',
			'description' => 'Add the ability to define descriptions for patron types',
			'sql' => [
				"ALTER TABLE ptype ADD COLUMN description VARCHAR(100) DEFAULT ''"
			]
		],

		'oai_website_permissions' => [
			'title' => 'Fix permissions for OAI and Website Indexing',
			'description' => 'Fix permissions for OAI and Website Indexing',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions set requiredModule = 'Web Indexer' where requiredModule = 'Website Indexing'",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Website Indexing Settings'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Open Archives'))",
			]
		],

		'list_indexing_permission' => [
			'title' => 'List indexing permissions',
			'description' => 'Create permission to administer list indexing',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('User Lists', 'Administer List Indexing Settings', '', 0, 'Allows the user to administer list indexing settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer List Indexing Settings'))",

			]
		],

		'reporting_permissions' => [
			'title' => 'Reporting permissions',
			'description' => 'Create permissions for circulation reports and student reports',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Circulation Reports', 'View Location Holds Reports', '', 0, 'Allows the user to view lists of holds to be pulled for their home location (CARL.X) only.'),
					('Circulation Reports', 'View All Holds Reports', '', 10, 'Allows the user to view lists of holds to be pulled for any location (CARL.X) only.'),
					('Circulation Reports', 'View Location Student Reports', '', 20, 'Allows the user to view barcode and checkout reports for their home location (CARL.X) only.'),
					('Circulation Reports', 'View All Student Reports', '', 30, 'Allows the user to view barcode and checkout reports for any location (CARL.X) only.')
				",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='locationReports'), (SELECT id from permissions where name='View Location Holds Reports'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='locationReports'), (SELECT id from permissions where name='View All Holds Reports'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='locationReports'), (SELECT id from permissions where name='View Location Student Reports'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='locationReports'), (SELECT id from permissions where name='View All Student Reports'))",
			]
		],

		'view_unpublished_content_permissions' => [
			'title' => 'View unpublished permissions',
			'description' => 'Create permissions to view unpublished content',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Web Builder', 'View Unpublished Content', '', 0, 'Allows the user to view unpublished menu items and content.')
				",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Unpublished Content'))",
			]
		],

		'administer_host_permissions' => [
			'title' => 'Administer host permissions',
			'description' => 'Create permissions to administer host information',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('System Administration', 'Administer Host Information', '', 50, 'Allows the user to change information about the hosts used for Aspen Discovery.')
				",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Host Information'))",
			]
		],

//		'barcode_printing_permissions' => [
//			'title' => 'Print barcodes permissions',
//			'description' => 'Create permissions to print barcodes',
//			'continueOnError' => true,
//			'sql' => [
//				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
//					('Cataloging & eContent', 'Print Barcodes', '', 95, 'Allows the user to print barcodes within Aspen Discovery.')
//				",
//				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Print Barcodes'))",
//			]
//		],

		'system_messages_permissions' => [
			'title' => 'System Messages permissions',
			'description' => 'Create permissions to administer system messages',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Local Enrichment', 'Administer All System Messages', '', 70, 'Allows the user to define system messages for all libraries within Aspen Discovery.'),
					('Local Enrichment', 'Administer Library System Messages', '', 80, 'Allows the user to define system messages for their library within Aspen Discovery.')
				",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All System Messages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='libraryAdmin'), (SELECT id from permissions where name='Administer Library System Messages'))",
			]
		],

		'new_york_times_user_updates' => [
			'title' => 'New York Times permission updates',
			'description' => 'Update permissions for New York Times user and make the id non zero, make sure that all their lists are searchable too',
			'sql' => [
				'fixNytUserPermissions'
			],
		],

		'cleanup_invalid_reading_history_entries' => [
			'title' => 'Cleanup Invalid Reading History Entries',
			'description' => 'Remove old reading history entries that will display as Title Not Available',
			'sql' => [
				'DELETE FROM user_reading_history_work where (groupedWorkPermanentId is null or groupedWorkPermanentId = \'\') and (title is null or title = \'\') and (author is null OR author = \'\')'
			]
		],

		'store_pickup_location' => [
			'title' => 'Store the selected pickup location',
			'description' => 'Store the selected pickup location with the user for cases when the library does not allow home location to be changed',
			'sql' => [
				'ALTER TABLE user ADD COLUMN pickupLocationId INT(11) DEFAULT 0',
				'UPDATE user SET rememberHoldPickupLocation = 0',
				'UPDATE user SET pickupLocationId = homeLocationId'
			]
		],

		'user_add_last_reading_history_update_time' => [
			'title' => 'Store when the reading history was last updated',
			'description' =>  'Store when the reading history was last updated to optimize loading reading history',
			'sql' => [
				'ALTER TABLE user ADD COLUMN lastReadingHistoryUpdate INT(11) DEFAULT 0'
			]
		],

		'user_remove_college_major' => [
			'title' => 'Remove College and Major',
			'description' => 'Remove unused college and major fields from user table',
			'sql' => [
				'ALTER TABLE user DROP COLUMN college',
				'ALTER TABLE user DROP COLUMN major',
			]
		],
		'encrypt_user_table' => [
			'title' => 'Encrypt User Table (Slow)',
			'description' => 'Encrypt data within the user table, this can take a long time for instances with a lot of users.',
			'sql' => [
				//First increase field lengths
				'ALTER TABLE user CHANGE COLUMN password password VARCHAR(256)',
				"ALTER TABLE user CHANGE COLUMN firstname firstname VARCHAR(256) NOT NULL DEFAULT ''",
				"ALTER TABLE user CHANGE COLUMN lastname lastname VARCHAR(256) NOT NULL DEFAULT ''",
				"ALTER TABLE user CHANGE COLUMN email email VARCHAR(256) NOT NULL DEFAULT ''",
				'ALTER TABLE user CHANGE COLUMN cat_username cat_username VARCHAR(256)',
				"ALTER TABLE user CHANGE COLUMN cat_password cat_password VARCHAR(256) DEFAULT ''",
				"ALTER TABLE user CHANGE COLUMN displayName displayName VARCHAR(256) NOT NULL DEFAULT ''",
				"ALTER TABLE user CHANGE COLUMN phone phone VARCHAR(256) NOT NULL DEFAULT ''",
				"ALTER TABLE user CHANGE COLUMN overdriveEmail overdriveEmail VARCHAR(256) NOT NULL DEFAULT ''",
				//'ALTER TABLE user CHANGE COLUMN rbdigitalPassword rbdigitalPassword VARCHAR(256)',
				"ALTER TABLE user CHANGE COLUMN alternateLibraryCardPassword alternateLibraryCardPassword VARCHAR(256) NOT NULL DEFAULT ''",
				//Now do the actual encryption
				'encryptUserFields'
			]
		],

		'user_cache_holds' => [
			'title' => 'User account cache holds',
			'description' => 'Cache holds for a user to improve performance',
			'sql' => [
				'ALTER TABLE user ADD COLUMN holdInfoLastLoaded INT(11) DEFAULT 0',
				"CREATE TABLE user_hold (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					type VARCHAR(20) NOT NULL,
					source VARCHAR(50) NOT NULL,
					userId INT(11) NOT NULL,
					sourceId VARCHAR(50) NOT NULL,
					recordId VARCHAR(50) NOT NULL,
					shortId VARCHAR(50),
					itemId VARCHAR(50),
					title VARCHAR(500),
					title2 VARCHAR(500),
					author VARCHAR(500),
					volume VARCHAR(50),
					callNumber VARCHAR(50),
					available TINYINT(1),
					cancelable TINYINT(1),
					cancelId VARCHAR(50),
					locationUpdateable TINYINT(1),
					pickupLocationId VARCHAR(50),
					pickupLocationName VARCHAR(100),
					status VARCHAR(50),
					position INT(11),
					holdQueueLength INT(11),
					createDate INT(11),
					availableDate INT(11),
					expirationDate INT(11),
					automaticCancellationDate INT(11),
					frozen TINYINT(1),
					canFreeze TINYINT(1),
					reactivateDate INT(11)
				)  ENGINE=InnoDB  DEFAULT CHARSET=utf8"
			]
		],

		'user_cache_checkouts' => [
			'title' => 'User account cache checkouts',
			'description' => 'Cache checkouts for a user to improve performance',
			'sql' => [
				'ALTER TABLE user ADD COLUMN checkoutInfoLastLoaded INT(11) DEFAULT 0',
				"CREATE TABLE user_checkout (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					type VARCHAR(20) NOT NULL,
					source VARCHAR(50) NOT NULL,
					userId INT(11) NOT NULL,
					sourceId VARCHAR(50) NOT NULL,
					recordId VARCHAR(50) NOT NULL,
					shortId VARCHAR(50),
					itemId VARCHAR(50),
					itemIndex VARCHAR(50),
					renewalId VARCHAR(50),
					barcode VARCHAR(50),
					title VARCHAR(500),
					title2 VARCHAR(500),
					author VARCHAR(500),
					callNumber VARCHAR(50),
					volume VARCHAR(50),
					checkoutDate INT(11),
					dueDate INT(11),
					renewCount INT(11),
					canRenew TINYINT(1),
					autoRenew TINYINT(1),
					autoRenewError VARCHAR(500),
					maxRenewals INT(11),
					fine FLOAT,
					returnClaim VARCHAR(500),
					holdQueueLength INT(11)
				)  ENGINE=InnoDB  DEFAULT CHARSET=utf8"
			]
		],

		'user_checkout_cache_additional_fields' =>[
			'title' => 'User Checkout Cache Add Additional Fields',
			'description' => 'Add additional fields to for eContent',
			'sql' => [
				'ALTER TABLE user_checkout ADD column allowDownload TINYINT(1)',
				'ALTER TABLE user_checkout ADD column overdriveRead TINYINT(1)',
				'ALTER TABLE user_checkout ADD column overdriveReadUrl VARCHAR(255)',
				'ALTER TABLE user_checkout ADD column overdriveListen TINYINT(1)',
				'ALTER TABLE user_checkout ADD column overdriveListenUrl VARCHAR(255)',
				'ALTER TABLE user_checkout ADD column overdriveVideo TINYINT(1)',
				'ALTER TABLE user_checkout ADD column overdriveVideoUrl VARCHAR(255)',
				'ALTER TABLE user_checkout ADD column formatSelected TINYINT(1)',
				'ALTER TABLE user_checkout ADD column selectedFormatName VARCHAR(50)',
				'ALTER TABLE user_checkout ADD column selectedFormatValue VARCHAR(25)',
				'ALTER TABLE user_checkout ADD column canReturnEarly TINYINT(1)',
				'ALTER TABLE user_checkout ADD column supplementalMaterials TEXT',
				'ALTER TABLE user_checkout ADD column formats TEXT',
				'ALTER TABLE user_checkout ADD column downloadUrl VARCHAR(255)',
				'ALTER TABLE user_checkout ADD column accessOnlineUrl VARCHAR(255)',
				'ALTER TABLE user_checkout ADD column transactionId VARCHAR(40)',
				'ALTER TABLE user_checkout ADD column coverUrl VARCHAR(255)',
				'ALTER TABLE user_checkout ADD column format VARCHAR(50)',
			]
		],

		'user_checkout_cache_renewal_information' =>[
			'title' => 'User Checkout Cache renewal date',
			'description' => 'Add renewal date to checkout cache',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN renewalDate INT(11)',
				'ALTER TABLE user_checkout ADD COLUMN renewIndicator VARCHAR(20)',
			],
		],

		'user_circulation_cache_grouped_work' => [
			'title' => 'Circulation caching grouped work',
			'description' => 'Add groupedWorkId to circulation caching information',
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN groupedWorkId CHAR(36)',
				'ALTER TABLE user_hold ADD COLUMN groupedWorkId CHAR(36)',
			]
		],

		'user_circulation_cache_overdrive_magazines' => [
			'title' => 'Circulation caching overdrive magazines',
			'description' => 'Add overdrive magazine to checkout caching information',
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN overdriveMagazine TINYINT(1)',
			]
		],

		'user_circulation_cache_overdrive_supplemental_materials' => [
			'title' => 'Circulation caching supplemental materials',
			'description' => 'Add overdrive supplemental materials to checkout caching information',
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN isSupplemental TINYINT(1) DEFAULT 0',
			]
		],

		'user_account_summary_cache' => [
			'title' => 'User Account Summary caching',
			'description' => 'Store Account Summary for users',
			'sql' => [
				"CREATE TABLE user_account_summary (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					source VARCHAR(50) NOT NULL,
					userId INT(11) NOT NULL,
					numCheckedOut INT(11) DEFAULT 0,
					numOverdue INT(11) DEFAULT 0,
					numAvailableHolds INT(11) DEFAULT 0,
					numUnavailableHolds INT(11) DEFAULT 0,
					totalFines FLOAT DEFAULT 0,
					expirationDate INT(11) DEFAULT 0,
					numBookings INT(11) DEFAULT 0,
					lastLoaded INT(11)
				)  ENGINE=InnoDB  DEFAULT CHARSET=utf8",
				'ALTER TABLE user_account_summary ADD UNIQUE (source, userId)'
			]
		],

		'user_account_summary_remaining_checkouts' => [
			'title' => 'User Account Summary - remaining checkouts',
			'description' => 'Add remaining checkouts to account summary for Hoopla',
			'sql' => [
				'ALTER TABLE user_account_summary ADD COLUMN numCheckoutsRemaining INT(11) DEFAULT 0'
			]
		],

		'user_circulation_cache_indexes' => [
			'title' => 'Circulation Caching indexes',
			'description' => 'Add indexes to circulation caching tables',
			'sql' => [
				'ALTER TABLE user_checkout ADD INDEX (userId, source, recordId)',
				'ALTER TABLE user_hold ADD INDEX (userId, source, recordId)',
				'ALTER TABLE user_checkout ADD INDEX (userId, groupedWorkId)',
				'ALTER TABLE user_hold ADD INDEX (userId, groupedWorkId)'
			]
		],

		'user_hold_format' => [
			'title' => 'Add format to cached information for user holds',
			'description' => 'Add format to cached information for user holds',
			'sql' => [
				'ALTER TABLE user_hold ADD COLUMN format VARCHAR(50)'
			]
		],

		'user_username_increase_length' => [
			'title' => 'Increase length of username field to accommodate FOLIO',
			'description' => 'Increase length of username field to accommodate FOLIO',
			'sql' => [
				'ALTER TABLE user CHANGE COLUMN username username VARCHAR(36) NOT NULL'
			]
		],

		'user_circulation_cache_cover_link' => [
			'title' => 'Circulation Caching add links',
			'description' => 'Add caching of cover url and link url to improve performance',
			'sql' => [
				'ALTER TABLE user_hold ADD column coverUrl VARCHAR(255)',
				'ALTER TABLE user_hold ADD column linkUrl VARCHAR(255)',
				'ALTER TABLE user_checkout ADD column linkUrl VARCHAR(255)',
			]
		],

		'user_account_summary_expiration_date_extension' => [
			'title' => 'Account Summary enlarge expiration dates',
			'description' => 'Update Account Summary to allow expiration dates that are far in the future',
			'sql' => [
				'ALTER TABLE user_account_summary CHANGE COLUMN expirationDate expirationDate BIGINT DEFAULT 0',
			]
		],

		'user_account_cache_volume_length' => [
			'title' => 'Increase length of volume in holds and checkouts',
			'description' => 'Increase length of volume in holds and checkouts',
			'sql' => [
				'ALTER TABLE user_checkout CHANGE COLUMN volume volume VARCHAR(255)',
				'ALTER TABLE user_hold CHANGE COLUMN volume volume VARCHAR(255)'
			]
		],

		'user_reading_history_dates_in_past' => [
			'title' => 'Expand Reading History Check In Date',
			'description' => 'Update Reading History to allow check in dates prior to 1970',
			'sql' => [
				'ALTER table user_reading_history_work change column checkInDate checkInDate BIGINT NULL;'
			]
		],

		'user_circulation_cache_callnumber_length' => [
			'title' => 'Expand call number length in circulation caches',
			'description' => 'Update circulation caches to increase length of call number fields',
			'sql' => [
				'ALTER TABLE user_checkout change column callNumber callNumber VARCHAR(100)',
				'ALTER TABLE user_hold change column callNumber callNumber VARCHAR(100)',
			]
		]
	);
}

/** @noinspection PhpUnused */
function updateDefaultPermissions(){
	require_once ROOT_DIR . '/sys/Administration/Role.php';
	require_once ROOT_DIR . '/sys/Administration/Permission.php';
	require_once  ROOT_DIR . '/sys/Administration/RolePermissions.php';
	$permissions = [];
	$permission = new Permission();
	$permission->find();
	while ($permission->fetch()){
		$permissions[$permission->name] = $permission->id;
	}

	$role = new Role();
	$role->orderBy('name');
	$role->find();
	while ($role->fetch()){
		$defaultPermissions = $role->getDefaultPermissions();
		foreach ($defaultPermissions as $permissionName){
			$rolePermission = new RolePermissions();
			$rolePermission->roleId = $role->roleId;
			if (array_key_exists($permissionName, $permissions)){
				$rolePermission->permissionId = $permissions[$permissionName];
				$rolePermission->insert();
			}
		}
	}
}

/** @noinspection PhpUnused */
function makeNytUserListPublisher()
{
	$user = new User();
	$user->username = 'nyt_user';
	if ($user->find(true)) {
		$role = new Role();
		$role->name = 'listPublisher';
		if ($role->find(true)) {
			require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
			$userRole = new UserRoles();
			$userRole->userId = $user->id;
			$userRole->roleId = $role->roleId;
			$userRole->insert();
		}
	}
}

/** @noinspection PhpUnused */
function fixNytUserPermissions()
{
	//Get the New York Times User
	$user = new User();
	$user->username = 'nyt_user';
	if ($user->find(true)) {
		if ($user->id == 0){
			$idToMoveTo = -2;
			$foundIdToMoveTo = false;
			while (!$foundIdToMoveTo){
				//We will move the user to a negative id.
				$tmpUser = new User();
				$tmpUser->id = $idToMoveTo;
				if ($tmpUser->find(true)){
					$idToMoveTo--;
				}else{
					$foundIdToMoveTo = true;
				}
			}
			global $aspen_db;
			$aspen_db->query("UPDATE user SET id = $idToMoveTo WHERE id = 0");
			$aspen_db->query("UPDATE user_roles SET userId = $idToMoveTo WHERE userId = 0");
			$aspen_db->query("UPDATE user_list SET user_id = $idToMoveTo WHERE user_id = 0");
			$user->id = $idToMoveTo;
		}
	}
	require_once ROOT_DIR . '/sys/UserLists/UserList.php';
	$nytLists = new UserList();
	$nytLists->user_id = $user->id;
	$nytLists->find();
	while ($nytLists->fetch()){
		if ($nytLists->searchable == 0){
			$nytLists->searchable = 1;
			$nytLists->update();
		}
	}
}

/** @noinspection PhpUnused */
function encryptUserFields(){
	set_time_limit(0);
	$user = new User();
	$numUsers = $user->count();
	$numBatches = (int)ceil($numUsers / 1000);
	for ($i = 0; $i < $numBatches; $i++){
		$user = new User();
		$user->limit($i * 1000, 1000);
		$user->find();
		while ($user->fetch()){
			//Just need to re-save to make the encryption work
			$user->update();
		}
		$user->__destruct();
	}
}