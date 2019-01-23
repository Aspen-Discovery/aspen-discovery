<?php
/**
 * Updates related to user tables for cleanliness
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 2:42 PM
 */

function getUserUpdates(){
	return array(
		'roles_1' => array(
			'title' => 'Roles 1',
			'description' => 'Add new role for epubAdmin',
			'sql' => array(
				"INSERT INTO roles (name, description) VALUES ('epubAdmin', 'Allows administration of eContent.')",
			),
		),

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
				"UPDATE user SET overdriveEmail = email"
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
			'description' => 'Store the source of a user account so we can accommodate multiple ilses',
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

			'user_hoopla_confirmation_checkout' => array(
					'title' => 'Hoopla Checkout Confirmation Prompt',
					'description' => 'Stores user preference whether or not to prompt for confirmation before checking out a title from Hoopla',
					'sql' => array(
							"ALTER TABLE `user` ADD COLUMN `hooplaCheckOutConfirmation` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;"
					),
			),

	);
}