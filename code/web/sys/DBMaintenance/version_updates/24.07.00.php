<?php

function getUpdates24_07_00(): array {
	$curTime = time();
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
		'prevent_automatic_hour_updates' => [
			'title' => 'Prevent Automatic Hour Updates',
			'description' => 'Prevent automatically updating hours for libraries and locations',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowUpdatingHolidaysFromILS TINYINT(1) DEFAULT 1',
				'ALTER TABLE location ADD COLUMN allowUpdatingHoursFromILS TINYINT(1) DEFAULT 1'
			]
		], //prevent_automatic_hour_updates
		'increase_format_length_for_circulation_cache' => [
			'title' => 'Increase Format Length for Circulation Cache',
			'description' => 'Make sure that formats can be saved for holds and checkouts',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_hold CHANGE COLUMN format format varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL',
				'ALTER TABLE user_checkout CHANGE COLUMN format format varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL'
			]
		], //increase_format_length_for_circulation_cache

		//kirstien - ByWater


		//kodi - ByWater
		'self_registration_form_carlx' => [
			'title' => 'Self Registration Variables for CarlX',
			'description' => 'Moves variables needed for CarlX registration out of variables table & config array',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS self_registration_form_carlx (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					selfRegEmailNotices VARCHAR(255),
					selfRegDefaultBranch VARCHAR(255),
					selfRegPatronExpirationDate DATE,
					selfRegPatronStatusCode VARCHAR(255),
					selfRegPatronType VARCHAR(255),
    				selfRegRegBranch VARCHAR(255),
    				selfRegRegisteredBy VARCHAR(255),
    				lastPatronBarcode VARCHAR(255),
    				barcodePrefix VARCHAR(255),
					selfRegIDNumberLength INT(2)
				) ENGINE INNODB',
			],
		], // self_registration_form_carlx
		'overdrive_format_length' => [
			'title' => 'Format Length',
			'description' => 'Increase column length for format in user_hold table to accomodate concatenated OverDrive/Libby formats',
			'sql' => [
				'ALTER TABLE user_hold CHANGE COLUMN format format VARCHAR(150)',
			],
		],//overdrive_format_length

		//katherine - ByWater
		//greenhouseMonitoring
		'greenhouseSlackIntegration2' => [
			'title' => 'Greenhouse Slack Integration 2',
			'description' => 'Greenhouse Slack Integration - add a second Slack webhook for Sytems alerts',
			'sql' => [
				'ALTER TABLE greenhouse_settings ADD COLUMN greenhouseSystemsAlertSlackHook VARCHAR(255)',
			],
		], //greenhouse settings

		//pedro - PTFS-Europe
		'smtp_settings' => [
			'title' => 'SMTP Settings',
			'description' => 'Allow configuration of SMTP to send mail from',
			'sql' => [
				"CREATE TABLE smtp_settings (
					id int(11) NOT NULL AUTO_INCREMENT,
					name varchar(80) NOT NULL,
					host varchar(80) NOT NULL DEFAULT 'localhost',
					port int(11) NOT NULL DEFAULT 25,
					ssl_mode enum('disabled','ssl','tls') NOT NULL,
					from_address varchar(80) DEFAULT NULL,
					from_name varchar(80) DEFAULT NULL,
					user_name varchar(80) DEFAULT NULL,
					password varchar(80) DEFAULT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB",
			],
		], //smtp_settings

		'permissions_create_administer_smtp' => [
			'title' => 'Create Administer SMTP Permission',
			'description' => 'Controls if the user can change SMTP settings',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer SMTP', '', 30, 'Controls if the user can change SMTP settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer SMTP'))",
			],
		], //permissions_create_administer_smtp


		//alexander - PTFS Europe
		'update_cookie_management_preferences' => [
			'title' => 'Update Cookie Management Preferences',
			'description' => 'Update cookie management preferences for user tracking',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceAxis360 TINYINT(1) DEFAULT 0",
			],
		], //update_user_tracking_cookies
		'update_cookie_management_preferences_more_options' => [
			'title' => 'Update Cookie Management Preferences: More Options',
			'description' => 'Update cookie management preferences for user tracking - adding more options',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEbscoEds TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEbscoHost TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceSummon TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceEvents TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceHoopla TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceOpenArchives TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceOverdrive TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferencePalaceProject TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceSideLoad TINYINT(1) DEFAULT 0",
			],
		], //update_user_tracking_cookies
		'add_cookie_management_preferences' => [
			'title' => 'Cookie Management for Cloud Libray and Website',
			'description' => 'Add Cookie Management preferences for website and cloud library',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user ADD COLUMN userCookiePreferenceCloudLibrary TINYINT(1) DEFAULT 0",
				"ALTER TABLE user ADD COLUMN userCookiePreferenceWebsite TINYINT(1) DEFAULT 0",
			],
		], //add_user_tacking_cookie_preferences

		//other



	];
}