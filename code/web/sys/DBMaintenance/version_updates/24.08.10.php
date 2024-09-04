<?php

function getUpdates24_08_10(): array {
	return [
        /*'name' => [
            'title' => '',
            'description' => '',
            'continueOnError' => false,
            'sql' => [
                ''
            ]
		], //name*/

		//kodi - ByWater
		'self_registration_form_sierra' => [
			'title' => 'Self Registration for Sierra',
			'description' => 'Creates self registration form table for Sierra',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS self_registration_form_sierra (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					selfRegistrationTemplate VARCHAR(25) default "default",
    				selfRegEmailBarcode TINYINT NOT NULL default 0,
    				termsOfServiceSetting int NOT NULL default -1
				) ENGINE INNODB',
			],
		], // self_registration_form_sierra
		'self_reg_values_column_name' => [
			'title' => 'ILS Self Reg Field Names',
			'description' => 'Rename column in self_reg_values to be ilsName instead of symphonyName as it applies to multiple ILSes',
			'sql' => [
				"ALTER TABLE self_reg_form_values CHANGE COLUMN symphonyName ilsName VARCHAR(50) NOT NULL",
			],
		], //self_reg_values_column_name
		'self_reg_note_field_length' => [
			'title' => 'Self Reg Note Field Length',
			'description' => 'Increase the length for the note field in custom self registration forms (carlx, symphony, sierra only)',
			'sql' => [
				"ALTER TABLE self_reg_form_values CHANGE COLUMN note note VARCHAR(255)",
			],
		], //self_reg_note_field_length
		'carlx_tos' => [
			'title' => 'Terms of Service for CarlX Self Registration',
			'description' => 'Add terms of service functionality to CarlX self registration.',
			'sql' => [
				"ALTER TABLE self_registration_form_carlx ADD COLUMN termsOfServiceSetting int NOT NULL default -1"
			]
		], //carlx_tos

	];
}