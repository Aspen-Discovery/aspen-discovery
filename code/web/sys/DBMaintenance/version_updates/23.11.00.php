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