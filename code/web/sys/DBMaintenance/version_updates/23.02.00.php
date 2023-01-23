<?php
/** @noinspection PhpUnused */
function getUpdates23_02_00(): array {
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
		'increase_sublocation_to_include' => [
			'title' => 'Increase sublocation to include',
			'description' => 'Increase the length of sublocation in records to include',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library_records_to_include CHANGE COLUMN subLocation subLocation VARCHAR(150) NOT NULL DEFAULT '';",
				"ALTER TABLE location_records_to_include CHANGE COLUMN subLocation subLocation VARCHAR(150) NOT NULL DEFAULT '';",
			]
		], //increase_sublocation_to_include

		//kirstien
		'add_expo_eas_build_webhook_key' => [
			'title' => 'Add Expo EAS Build webhook key',
			'description' => 'Add Expo EAS Build webhook key to Greenhouse settings',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE greenhouse_settings ADD COLUMN expoEASBuildWebhookKey VARCHAR(256) default NULL",
			]
		], //add_expo_eas_build_webhook_key
		'add_aspen_lida_build_tracker' => [
			'title' => 'Add Aspen LiDA Build Tracker',
			'description' => 'Add table to track Aspen LiDA builds in the Greenhouse',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS aspen_lida_build (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					buildId VARCHAR(72) NOT NULL,
					status VARCHAR(11) NOT NULL,
					appId VARCHAR(72) NOT NULL, 
					name VARCHAR(72) NOT NULL, 
					version VARCHAR(72) NOT NULL,
					buildVersion VARCHAR(72) NOT NULL,  
					channel VARCHAR(72) NOT NULL DEFAULT 'default',
					updateId VARCHAR(72) NOT NULL DEFAULT 0,
					patch VARCHAR(5) DEFAULT 0, 
					updateCreated VARCHAR(255),
					gitCommitHash VARCHAR(72), 
					buildMessage VARCHAR(72), 
					error TINYINT(1) DEFAULT 0, 
					errorMessage VARCHAR(255),
					createdAt VARCHAR(255),
					completedAt VARCHAR(255), 
					updatedAt VARCHAR(255), 
					isSupported TINYINT(1) DEFAULT 1,
					isEASUpdate TINYINT(1) DEFAULT 0,
					platform VARCHAR(25) NOT NULL,
					artifact VARCHAR(255),
					UNIQUE INDEX (buildId, updateId)
				) ENGINE INNODB",
			]
		],
		//add_aspen_lida_build_tracker
		'add_build_tracker_slack_alert' => [
			'title' => 'Add Aspen LiDA Build Tracker Slack alert',
			'description' => 'Add option to enable Aspen LiDA Build Tracker Slack alerts in Greenhouse settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE greenhouse_settings ADD COLUMN sendBuildTrackerAlert TINYINT(1) DEFAULT 0',
			]
		],
		//add_build_tracker_slack_alert
		'add_staff_ptype_to_sso_settings' => [
			'title' => 'Add Staff Patron Type to SSO Settings',
			'description' => 'Adds field to assign staff users a different patron type than self-registered users',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN staffPType VARCHAR(30) default NULL',
			]
		],
		//add_staff_ptype_to_sso_setting
		'add_saml_options_to_sso_settings' => [
			'title' => 'Add Additional SAML options to SSO Settings',
			'description' => 'Adds fields to customize SAML login button',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN samlMetadataOption VARCHAR(30)',
				'ALTER TABLE sso_setting ADD COLUMN samlBtnIcon VARCHAR(255)',
				'ALTER TABLE sso_setting ADD COLUMN samlBtnBgColor CHAR(7) DEFAULT "#de1f0b"',
				'ALTER TABLE sso_setting ADD COLUMN samlBtnTextColor CHAR(7) DEFAULT "#ffffff"'
			]
		],
		//add_saml_options_to_sso_settings
		'add_staff_ptypes_to_sso_settings' => [
			'title' => 'Add patron types to SSO Settings',
			'description' => 'Adds patron types to SSO Settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sso_setting DROP COLUMN staffPType',
				'ALTER TABLE sso_setting ADD COLUMN samlStaffPTypeAttr VARCHAR(255) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN samlStaffPTypeAttrValue VARCHAR(255) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN samlStaffPType VARCHAR(30) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN oAuthStaffPTypeAttr VARCHAR(255) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN oAuthStaffPTypeAttrValue VARCHAR(255) default NULL',
				'ALTER TABLE sso_setting ADD COLUMN oAuthStaffPType VARCHAR(30) default NULL',
			]
		],
		//add_staff_ptypes_to_sso_settings

		//kodi

		//other
	];
}