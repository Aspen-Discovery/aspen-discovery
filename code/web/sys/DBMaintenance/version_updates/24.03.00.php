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

		//kirstien - ByWater

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
				"UPDATE self_registration_form LEFT JOIN library ON (library.selfRegistrationFormId = self_registration_form.id)
					SET self_registration_form.promptForParentInSelfReg = library.promptForParentInSelfReg,
					self_registration_form.promptForSMSNoticesInSelfReg = library.promptForSMSNoticesInSelfReg,
					self_registration_form.cityStateField = library.cityStateField,
					self_registration_form.selfRegistrationUserProfile = library.selfRegistrationUserProfile",
				"ALTER TABLE library DROP COLUMN promptForParentInSelfReg",
				"ALTER TABLE library DROP COLUMN promptForSMSNoticesInSelfReg",
				"ALTER TABLE library DROP COLUMN cityStateField",
				"ALTER TABLE library DROP COLUMN selfRegistrationUserProfile",
			]
		]
		//self_reg_symphony_only


		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub


	];
}