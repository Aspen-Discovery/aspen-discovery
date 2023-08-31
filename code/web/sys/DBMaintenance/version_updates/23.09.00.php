<?php
/** @noinspection PhpUnused */
function getUpdates23_09_00(): array {
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

		//kodi - ByWater

		// kirstien - ByWater
		'add_forgot_barcode' => [
			'title' => 'Add Forgot Barcode option to Library Systems',
			'description' => 'Add option to allow users to receive their barcode via text',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD enableForgotBarcode TINYINT(1) default 0',
			],
		],
		//add_forgot_barcode

        // James Staub
        'donations_disambiguate_library_and_location' => [
            'title' => 'Corrects "Donate to Library" to "Donate to Location"',
            'description' => 'Corrects "Donate to Library" to "Donate to Location"',
            'sql' => [
                // mariadb < 10.5.2:
                 "ALTER TABLE donations CHANGE COLUMN donateToLibrary donateToLocation varchar(60)",
                 "ALTER TABLE donations CHANGE COLUMN donateToLibraryId donateToLocationId int(11)",
                // mariadb >= 10.5.2:
//                "ALTER TABLE donations RENAME COLUMN donateToLibrary TO donateToLocation",
//                "ALTER TABLE donations RENAME COLUMN donateToLibraryId TO donateToLocationId",
            ],
        ], //donations_disambiguate_library_and_location
        'ecommerce_report_permissions_all_vs_home' => [
            'title' => 'Update ecommerce report permissions',
            'description' => 'Update ecommerce report permissions',
            'continueOnError' => true,
            'sql' => [
                "UPDATE permissions
                SET name = 'View eCommerce Reports for All Libraries',
                    weight = 5,
                    description = 'Allows the user to view eCommerce reports for all libraries.'
                WHERE name = 'View eCommerce Reports'
                ",
                "UPDATE permissions
                SET name = 'View Donations Reports for All Libraries',
                    weight = 7,
                    description = 'Allows the user to view donations reports for all libraries.'
                WHERE name = 'View Donations Reports'
                ",
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('eCommerce', 'View eCommerce Reports for Home Library', '', 6, 'Allows the user to view eCommerce reports for their home library'),
					('eCommerce', 'View Donations Reports for Home Library', '', 8, 'Allows the user to view donations reports for their home library')
				",
                "insert into role_permissions (roleId, permissionId) values
                     ((select roleId from roles where name = 'libraryAdmin'), (select id from permissions where name = 'View eCommerce Reports for Home Library')),
                     ((select roleId from roles where name = 'libraryAdmin'), (select id from permissions where name = 'View Donations Reports for Home Library'))
                ",
            ],
        ], //ecommerce_report_permissions
	];
}