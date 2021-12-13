<?php
/** @noinspection PhpUnused */
function getUpdates22_01_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'curbside_pickup_settings' => [
			'title' => 'Add settings for Curbside Pickup',
			'description' => 'Add settings for Curbside Pickup',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS curbside_pickup_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					alwaysAllowPickups TINYINT(1) DEFAULT 0,
					allowCheckIn TINYINT(1) DEFAULT 1,
					useNote TINYINT(1) DEFAULT 1,
					noteLabel VARCHAR(75) DEFAULT "Note",
					noteInstruction VARCHAR(255) DEFAULT NULL,
					instructionSchedule LONGTEXT DEFAULT NULL,
					instructionNewPickup LONGTEXT DEFAULT NULL,
					contentSuccess LONGTEXT DEFAULT NULL,
					contentCheckedIn LONGTEXT DEFAULT NULL
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Curbside Pickup', 'Administer Curbside Pickup', '', 10, 'Controls if the user can change Curbside Pickup settings.')",
				"ALTER TABLE library ADD COLUMN curbsidePickupSettingId INT(11) DEFAULT -1"
			]
		], //curbside_pickup_settings
	];
}