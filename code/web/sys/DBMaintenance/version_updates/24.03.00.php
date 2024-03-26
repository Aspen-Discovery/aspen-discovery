<?php

function getUpdates24_03_00(): array {
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
		'library_toggle_hold_position' => [
			'title' => "Library Toggle Hold Position",
			'description' => "Adds column for events facet settings id to library_events_setting table",
			'sql' => [
				"ALTER TABLE library ADD COLUMN showHoldPosition TINYINT(1) DEFAULT 1",
			],
		], //library_toggle_hold_position
		'remove_old_payment_lines' => [
			'title' => 'Remove Old Payment Lines',
			'description' => 'Remove Old Payment Lines',
			'continueOnError' => false,
			'sql' => [
				'TRUNCATE TABLE user_payment_lines'
			]
		], //remove_old_payment_lines

		//kirstien - ByWater
		'add_lida_event_reg_body' => [
			'title' => 'Event Registration Body for Aspen LiDA',
			'description'=> 'Add settings for event registration information to use with APIs/Aspen LiDA',
			'sql' => [
				'ALTER TABLE lm_library_calendar_settings ADD COLUMN registrationModalBodyApp varchar(500)',
				'ALTER TABLE springshare_libcal_settings ADD COLUMN registrationModalBodyApp varchar(500)',
				'ALTER TABLE communico_settings ADD COLUMN registrationModalBodyApp varchar(500)',
			],
		],
		//add_lida_event_reg_body

		//kodi - ByWater
		'self_reg_min_age' => [
			'title' => 'Minimum Age',
			'description' => 'Minimum age for self-registrants.',
			'sql' => [
				"ALTER TABLE library ADD COLUMN minSelfRegAge INT(2) default 0",
			],
		],
		//self_reg_min_age
		'self_reg_symphony_only' => [
			'title' => '"Symphony Only" Self Registration',
			'description' => 'Move "Symphony Only" self registration values out of Primary Configuration and into the Symphony Self Registration Form',
			'sql' => [
				"ALTER TABLE self_registration_form ADD COLUMN promptForParentInSelfReg tinyint(1) NOT NULL DEFAULT 0",
				"ALTER TABLE self_registration_form ADD COLUMN promptForSMSNoticesInSelfReg tinyint(1) NOT NULL DEFAULT 0",
				"ALTER TABLE self_registration_form ADD COLUMN cityStateField tinyint(1) NOT NULL DEFAULT 0",
				"ALTER TABLE self_registration_form ADD COLUMN selfRegistrationUserProfile VARCHAR(20) DEFAULT 'SELFREG'",
				"UPDATE self_registration_form LEFT JOIN library ON (library.selfRegistrationFormId = self_registration_form.id) AND library.selfRegistrationFormId > 0
					SET self_registration_form.promptForParentInSelfReg = library.promptForParentInSelfReg,
					self_registration_form.promptForSMSNoticesInSelfReg = library.promptForSMSNoticesInSelfReg,
					self_registration_form.cityStateField = library.cityStateField,
					self_registration_form.selfRegistrationUserProfile = library.selfRegistrationUserProfile",
				"ALTER TABLE library DROP COLUMN promptForParentInSelfReg",
				"ALTER TABLE library DROP COLUMN promptForSMSNoticesInSelfReg",
				"ALTER TABLE library DROP COLUMN cityStateField",
				"ALTER TABLE library DROP COLUMN selfRegistrationUserProfile",
			],
		],
		//self_reg_symphony_only
		'self_reg_tos' => [
			'title' => "TOS for Self Registration (Symphony)",
			'description' => "Adds table for self registration terms of service pages",
			'sql' => [
				"CREATE TABLE `self_registration_tos` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `name` varchar(75) NOT NULL UNIQUE,
				  `terms` mediumtext,
				  `redirect` mediumtext,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB"
			],
		],
		//self_reg_tos
		'self_reg_form_update' => [
			'title' => "TOS for Self Registration",
			'description' => "Adds column to self_registration_form table for TOS setting id",
			'sql' => [
				"ALTER TABLE self_registration_form ADD COLUMN termsOfServiceSetting int NOT NULL default -1",
			],
		],
		//self_reg_form_update

		'communico_full_index' => [
			'title' => 'Communico Last Full Update',
			'description' => 'Adds variable for last full index of Communico events',
			'sql' => [
				'ALTER TABLE communico_settings ADD COLUMN lastUpdateOfAllEvents INT(11) DEFAULT 0',
			],
		],
		//communico_full_index



		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub


	];
}