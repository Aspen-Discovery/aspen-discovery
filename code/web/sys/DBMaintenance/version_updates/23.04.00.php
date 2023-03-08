<?php
/** @noinspection PhpUnused */
function getUpdates23_04_00(): array {
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

		//kodi
		'permissions_create_events_communico' => [
			'title' => 'Alters permissions for Events',
			'description' => 'Create permissions for Communico',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Communico Settings', 'Events', 20, 'Allows the user to administer integration with Communico for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Communico Settings'))",
			],
		],
		// permissions_create_events_communico
		'communico_settings' => [
			'title' => 'Define events settings for Communico integration',
			'description' => 'Initial setup of the Communico integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS communico_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					baseUrl VARCHAR(255) NOT NULL,
					clientId VARCHAR(36) NOT NULL,
					clientSecret VARCHAR(36) NOT NULL
				) ENGINE INNODB',
			],
		],

		// communico_settings
		'communico_events' => [
			'title' => 'Communico Events Data',
			'description' => 'Setup tables to store events data for Communico',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS communico_events (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingsId INT NOT NULL,
					externalId varchar(36) NOT NULL,
					title varchar(255) NOT NULL,
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					deleted TINYINT default 0,
					UNIQUE (settingsId, externalId)
				)',
			],
		],
		// communico_events

		//other
	];
}