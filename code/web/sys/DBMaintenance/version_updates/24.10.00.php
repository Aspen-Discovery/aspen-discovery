<?php

function getUpdates24_10_00(): array {
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

		//mark - Grove
		'additional_administration_locations' => [
			'title' => 'Additional Administration Locations',
			'description' => 'Add a table to store additional locations that a user can administer',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS `user_administration_locations` (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					locationId INT(11) NOT NULL,
					UNIQUE INDEX (userId,locationId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;'
			]
		], //additional_administration_locations

		//katherine - ByWater

		//kirstien - ByWater

		//kodi - ByWater

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library

		//Jeremy Eden - Howell Carnegie District Library
		'add_openarchives_dateformatting_field' => [
			'title' => 'Add Open Archives date formatting setting',
			'description' => 'Add Open Archives date formatting setting',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE open_archives_collection ADD COLUMN dateFormatting tinyint default 1',
			]
		], //add_defaultContent_field

		//other

	];
}
