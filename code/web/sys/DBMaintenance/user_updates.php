<?php

function getUserUpdates(){
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
	);
}

function makeNytUserListPublisher(){
	$user = new User();
	$user->username = 'nyt_user';
	if ($user->find(true)){
		$role = new Role();
		$role->name = 'listPublisher';
		if ($role->find(true)){
			require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
			$userRole = new UserRoles();
			$userRole->userId = $user->id;
			$userRole->roleId = $role->roleId;
			$userRole->insert();
		}
	}
}