<?php
/** @noinspection PhpUnused */
function getUpdates23_07_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/
		//mark
		'rename_prospector_to_innreach2' => [
			'title' => 'Rename Prospector Integration to INN-Reach',
			'description' => 'Rename Prospector Integration to INN-Reach',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library CHANGE COLUMN repeatInProspector repeatInInnReach TINYINT DEFAULT 0',
				'ALTER TABLE library DROP COLUMN prospectorCode',
				'ALTER TABLE library CHANGE COLUMN showProspectorResultsAtEndOfSearch showInnReachResultsAtEndOfSearch TINYINT DEFAULT 1',
				'ALTER TABLE library CHANGE COLUMN enableProspectorIntegration enableInnReachIntegration TINYINT(4) NOT NULL DEFAULT 0',
			],
		], //rename_prospector_to_innreach2
		'rename_prospector_to_innreach3' => [
			'title' => 'Rename Prospector Integration to INN-Reach Location',
			'description' => 'Rename Prospector Integration to INN-Reach in Location table',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE location CHANGE COLUMN repeatInProspector repeatInInnReach TINYINT DEFAULT 0',
			],
		], //rename_prospector_to_innreach3
		'third_party_registration' => [
			'title' => 'Third Party Registration',
			'description' => 'Configuration of Third Party Registration ',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN enableThirdPartyRegistration TINYINT DEFAULT 0',
				'ALTER TABLE library ADD COLUMN thirdPartyRegistrationLocation INT(11) DEFAULT -1',
				'ALTER TABLE library ADD COLUMN thirdPartyPTypeAddressValidated INT(11) DEFAULT -1',
				'ALTER TABLE library ADD COLUMN thirdPartyPTypeAddressNotValidated INT(11) DEFAULT -1',
				"UPDATE permissions set name = 'Library Registration', description = 'Configure Library fields related to how Self Registration and Third Party Registration is configured in Aspen.' WHERE name = 'Library Self Registration'",
			],
		], //third_party_registration
		'update_collection_spotlight_number_of_titles' => [
			'title' => 'Update Collection Spotlight Minimum Number of Titles',
			'description' => 'Update Collection Spotlight Minimum Number of Titles',
			'continueOnError' => true,
			'sql' => [
				'update collection_spotlights set numTitlesToShow = 25 where numTitlesToShow = 0;',
			],
		], //update_collection_spotlight_number_of_titles
		'remove_unused_fields_23_07' => [
			'title' => 'Remove Unused Fields - 23.07',
			'description' => 'Remove Unused Fields - 23.07',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE aspen_usage DROP COLUMN islandoraSearches',
				'ALTER TABLE ptype DROP COLUMN allowStaffViewDisplay'
			]
		], //remove_unused_fields_23_07
		'remove_unused_fields_23_07b' => [
			'title' => 'Remove Unused Fields - 23.07b',
			'description' => 'Remove Unused Fields - 23.07b',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user DROP COLUMN alwaysHoldNextAvailable',
				'ALTER TABLE user DROP COLUMN overdriveAutoCheckout',
				'ALTER TABLE user DROP COLUMN primaryTwoFactor',
				'ALTER TABLE user DROP COLUMN authLocked',
				'ALTER TABLE search DROP COLUMN folder_id',
				'ALTER TABLE search DROP COLUMN newTitles',
			]
		], //remove_unused_fields_23_07b

		//kirstien
		'user_onboard_notifications' => [
			'title' => 'Add column to store if user should be onboarded about notifications',
			'description' => 'Add column in user table to store if they should be onboarded about app notifications when opening Aspen LiDA.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user ADD COLUMN onboardAppNotifications TINYINT(1) DEFAULT 1',
			],
		], //user_onboard_notifications
		'add_ecommerce_square_settings' => [
			'title' => 'Add eCommerce vendor Square',
			'description' => 'Create tables to store settings for Square',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS square_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) NOT NULL UNIQUE,
					sandboxMode TINYINT(1) DEFAULT 0,
					applicationId VARCHAR(80) NOT NULL,
					accessToken VARCHAR(80) NOT NULL,
					locationId VARCHAR(80) NOT NULL
				) ENGINE INNODB',
				'ALTER TABLE library ADD COLUMN squareSettingId INT(11) DEFAULT -1',
				'ALTER TABLE user_payments ADD COLUMN squareToken VARCHAR(255) DEFAULT null',
				'ALTER TABLE user_payments MODIFY COLUMN orderId VARCHAR(75)',
				'ALTER TABLE user_payments MODIFY COLUMN transactionId VARCHAR(75)',
			],
		],
		// add_ecommerce_square_settings
		'permissions_ecommerce_square' => [
			'title' => 'Add permissions for Square',
			'description' => 'Create permissions for administration of Square',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer Square', '', 10, 'Controls if the user can change Square settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Square'))",
			],
		],
		// permissions_ecommerce_square

		//kodi
		'add_disallow_third_party_covers' => [
			'title' => 'Add option to disallow third party cover images for certain works',
			'description' => 'Add option to disallow third party cover images for certain works',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE bookcover_info ADD COLUMN disallowThirdPartyCover TINYINT(1) DEFAULT 0',
			],
		], //add_disallow_third_party_covers


		// other
		'theme_cover_default_image' => [
			'title' => 'Theme - Set default image for cover images',
			'description' => 'Update theme table to have default values for the default cover image',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN defaultCover VARCHAR(100) default ''",
			],
		], //theme_cover_default_image
		'theme_format_category_icons' => [
			'title' => 'Theme - Set custom icon images for format category icons',
			'description' => 'Update theme table to have custom icon image values for format category icons',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN booksImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN eBooksImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN audioBooksImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN musicImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN moviesImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN booksImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN eBooksImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN audioBooksImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN musicImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN moviesImageSelected VARCHAR(100) default ''",
			],
		], //theme_format_category_icons
	];
}