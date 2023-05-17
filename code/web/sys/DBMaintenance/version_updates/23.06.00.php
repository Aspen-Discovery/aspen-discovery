<?php
/** @noinspection PhpUnused */
function getUpdates23_06_00(): array {
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
		//kirstien
		'add_ecommerce_payflow_settings' => [
			'title' => 'Add eCommerce vendor PayPal Payflow',
			'description' => 'Create tables to store settings for PayPal Payflow',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS paypal_payflow_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) NOT NULL UNIQUE,
					sandboxMode TINYINT(1) DEFAULT 0,
					partner VARCHAR(72) NOT NULL,
					vendor VARCHAR(72) NOT NULL,
					user VARCHAR(72) NOT NULL,
					password VARCHAR(72) NOT NULL
				) ENGINE INNODB',
				'ALTER TABLE library ADD COLUMN paypalPayflowSettingId INT(11) DEFAULT -1',
			],
		],
		// add_ecommerce_payflow_settings
		'permissions_ecommerce_payflow' => [
			'title' => 'Add permissions for PayPal Payflow',
			'description' => 'Create permissions for administration of PayPal Payflow',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer PayPal Payflow', '', 10, 'Controls if the user can change PayPal Payflow settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer PayPal Payflow'))",
			],
		],
		// permissions_ecommerce_payflow
		'add_sso_saml_student_attributes' => [
			'title' => 'Add settings to setup student users with SSO',
			'description' => 'Add settings to setup student users with SSO using SAML',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN samlStudentPTypeAttr VARCHAR(255) DEFAULT null',
				'ALTER TABLE sso_setting ADD COLUMN samlStudentPTypeAttrValue VARCHAR(255) DEFAULT null',
				'ALTER TABLE sso_setting ADD COLUMN samlStudentPType VARCHAR(30) DEFAULT null',
			],
		],
		//add_sso_saml_student_attributes
		//kodi
		'event_library_mapping' => [
			'title' => 'Event Library Mapping',
			'description' => 'Maps library branch names to the values in Aspen for Events relevancy',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS `event_library_map_values` (
				  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `aspenLocation` varchar(255) NOT NULL,
				  `eventsLocation` varchar(255) NOT NULL,
				  `locationId` INT(11) NOT NULL,
				  `libraryId` INT(11) NOT NULL,
				  UNIQUE KEY (`locationId`)
				)',
			]
		], //event_library_mapping
		'event_library_mapping_values' => [
			'title' => 'Event Library Mapping Values',
			'description' => 'Populates event_library_map_values with existing information.',
			'sql' => [
				"INSERT INTO event_library_map_values(aspenLocation, eventsLocation, locationId, libraryId) SELECT displayName, displayName, locationId, libraryId FROM location ORDER BY locationId ASC",
			]
		], //event_library_mapping_values
		//other
	];
}