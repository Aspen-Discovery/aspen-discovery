<?php

function getUpdates23_11_00(): array {
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
        'add_self_check_barcode_styles' => [
            'title' => 'Add self-check barcode styles',
            'description' => 'Add options for libraries to manage valid barcode styles for self-checkout',
            'continueOnError' => true,
            'sql' => [
                'CREATE TABLE aspen_lida_self_check_barcode (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					selfCheckSettingsId INT(11) NOT NULL DEFAULT -1,
					barcodeStyle VARCHAR(75) NOT NULL 
				) ENGINE = InnoDB',
            ]
        ], // add_self_check_barcode_styles
		'extend_symphonyPaymentType' => [
			'title' => 'Extend symphonyPaymentType in library',
			'description' => 'Extend symphonyPaymentType in library',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library MODIFY COLUMN symphonyPaymentType VARCHAR(12)',
			]
		], //extend_symphonyPaymentType

		//kodi - ByWater
		//Alexander - PTFS
		'display_list_author_control' => [
			'title' => 'User List Author Control',
			'description' => 'Add a setting to allow users to control whether their name appears on public lists they have created.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE  user_list ADD COLUMN displayListAuthor TINYINT(1) DEFAULT 1',
				'ALTER TABLE user ADD COLUMN displayListAuthor TINYINT(1) DEFAULT 1',
			],
		],
		//Jacob - PTFS
		'user_cookie_preference_essential' => [
			'title' => 'Add user editable cookie preferences for essential cookies',
			'description' => 'Allow essential cookie preferences to be saved on a per user basis',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user add column userCookiePreferenceEssential INT(1) DEFAULT 0",
			],
		],//user_cookie_preference_essential
		'user_cookie_preference_analytics' => [
			'title' => 'Add user editable cookie preferences for analytics cookies',
			'description' => 'Allow analytics cookie preferences to be saved on a per user basis',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user add column userCookiePreferenceAnalytics INT(1) DEFAULT 0",
			],
		],//user_cookie_preference_analytics
		//other
		'select_ILL_system' => [
			'title' => 'Dropbox ILL systems',
			'description' => 'Add a setting to allow users to specify ILL system used.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE  library ADD COLUMN ILLSystem TINYINT(1) DEFAULT 2',
			],
		],
    ];
}