<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n


		//kirstien

		//kodi
		'dpla_exclusions' => [
			'title' => 'Add Table for DP.LA Excluded Titles',
			'description' => 'Add table for DP.LA excluded titles.',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS dpla_exclusion_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    				dplaLink VARCHAR(255) NOT NULL
				) ENGINE INNODB',
			],
		], // dpla_exclusions
		'permissions_dpla_exclusions' => [
			'title' => 'Alters permissions for DP.LA Exclusions',
			'description' => 'Create permissions for DP.LA Exclusions',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Third Party Enrichment', 'Administer DP.LA Exclusions', '', 0, 'Allows the user to define DP.LA results exclusions for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer DP.LA Exclusions'))",
			],
		],
		// permissions_create_events_localhop

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
