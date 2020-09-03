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
				"ALTER TABLE user ADD COLUMN alternateLibraryCardPassword VARCHAR(60) DEFAULT ''",
				"ALTER TABLE user CHANGE COLUMN cat_password cat_password VARCHAR(60) DEFAULT ''",
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
					,('Islandora Archives', 'Administer Islandora Archive', 'Islandora', 0, 'Allows the user to administer integration with an Islandora archive.')
					,('Islandora Archives', 'View Archive Authorship Claims', 'Islandora', 10, 'Allows the user to view authorship claims for Islandora archive materials.')
					,('Islandora Archives', 'View Library Archive Authorship Claims', 'Islandora', 12, 'Allows the user to view authorship claims for Islandora archive materials.')
					,('Islandora Archives', 'View Archive Material Requests', 'Islandora', 20, 'Allows the user to view material requests for Islandora archive materials.')
					,('Islandora Archives', 'View Library Archive Material Requests', 'Islandora', 22, 'Allows the user to view material requests for Islandora archive materials.')
					,('Islandora Archives', 'View Islandora Archive Usage', 'Islandora', 30, 'Allows the view a report of objects in the repository by library.')
					,('Open Archives', 'Administer Open Archives', 'Open Archives', 0, 'Allows the user to administer integration with Open Archives repositories for all libraries.')
					,('Events', 'Administer Library Calendar Settings', 'Events', 10, 'Allows the user to administer integration with Library Calendar for all libraries.')
					,('Website Indexing', 'Administer Website Indexing Settings', 'Website Indexing', 0, 'Allows the user to administer the indexing of websites for all libraries.')
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