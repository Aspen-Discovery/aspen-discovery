<?php

function getUpdates24_02_00(): array {
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
		'self_reg_sections' => [
			'title' => 'Symphony Self Registration Sections',
			'description' => 'Adds definable sections to the self registration form for a more organized look.',
			'sql' => [
				"ALTER TABLE self_reg_form_values ADD COLUMN section ENUM ('librarySection', 'identitySection', 'mainAddressSection', 'contactInformationSection') NOT NULL DEFAULT 'identitySection'",
			],
		], //self_reg_sections
		'self_reg_sections_assignment' => [
			'title' => 'Symphony Self Registration Sections Assignments',
			'description' => 'Assigns sections for Symphony self registration form values.',
			'sql' => [
				"UPDATE self_reg_form_values SET section = 'librarySection' WHERE symphonyName = 'library'",
				"UPDATE self_reg_form_values SET section = 'identitySection' WHERE symphonyName in (
                	'firstName',
                    'middleName',
                	'lastName',
                    'preferredName',
                    'usePreferredName',
                    'suffix',
                    'title',
                    'dob',
            		'birthdate',
                    'care_of',
                    'careof',
                    'guardian',
                	'parentname')",
				"UPDATE self_reg_form_values SET section = 'mainAddressSection' WHERE symphonyName in (
                	'po_box',
                    'street',
                	'mailingaddr',
                    'primaryAddress',
                    'apt_suite',
                    'city',
                    'state',
                    'zip')",
				"UPDATE self_reg_form_values SET section = 'contactInformationSection' WHERE symphonyName in (
                	'email',
                    'phone',
                	'dayphone',
                    'cellPhone',
                    'workphone',
                    'homephone',
                    'ext',
                    'fax',
            		'primaryPhone')",
			],
		], //self_reg_sections_assignment

        //lucas - Theke
         'requires_address_info' => [
             'title' => 'Requires address information',
             'description' => 'Add a checkbox to prompt users for their address when making a donation.',
             'continueOnError' => false,
             'sql' => [
                 'ALTER TABLE donations_settings ADD COLUMN requiresAddressInfo TINYINT(1) default 0'
             ]
        ], //requires_address_info

        'add_address_information_for_donations' => [
            'title' => 'Adds address information for donations',
            'description' => 'Adds new columns with address information of the user ',
            'continueOnError' => false,
            'sql' => [
                'ALTER TABLE donations ADD COLUMN address VARCHAR(50)',
				'ALTER TABLE donations ADD COLUMN address2 VARCHAR(50)',
                'ALTER TABLE donations ADD COLUMN city VARCHAR(50)',
                'ALTER TABLE donations ADD COLUMN state VARCHAR(50)',
                'ALTER TABLE donations ADD COLUMN zip INT(11)',

            ]
        ]

		//alexander - PTFS Europe

		//jacob - PTFS Europe


	];
}