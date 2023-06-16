<?php
/** @noinspection PhpUnused */
function getUpdates23_07_00(): array {
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
		'rename_prospector_to_innreach2' => [
			'title' => 'Rename Prospector Integration to INN-Reach',
			'description' => 'Rename Prospector Integration to INN-Reach',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library CHANGE COLUMN repeatInProspector repeatInInnReach TINYINT DEFAULT 0',
				'ALTER TABLE library DROP COLUMN prospectorCode',
				'ALTER TABLE library CHANGE COLUMN showProspectorResultsAtEndOfSearch showInnReachResultsAtEndOfSearch TINYINT DEFAULT 1',
				'ALTER TABLE library CHANGE COLUMN enableProspectorIntegration enableInnReachIntegration TINYINT(4) NOT NULL DEFAULT 0',
			],
		], //rename_prospector_to_innreach2

		//kirstien

		//kodi
		'add_disallow_third_party_covers' => [
			'title' => 'Add option to disallow third party cover images for certain works',
			'description' => 'Add option to disallow third party cover images for certain works',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE bookcover_info ADD COLUMN disallowThirdPartyCover TINYINT(1) DEFAULT 0',
			],
		], //add_disallow_third_party_covers

		//other
		'collection_report_permissions' => [
			'title' => 'Reporting permissions',
			'description' => 'Create permissions for collection reports',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Circulation Reports', 'View Location Collection Reports', '', 40, 'Allows the user to view collection reports for their home location (CARL.X) only.'),
					('Circulation Reports', 'View All Collection Reports', '', 50, 'Allows the user to view collection reports for any location (CARL.X) only.')
				",
				//"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='school library staff'), (SELECT id from permissions where name='View Location Collection Reports'))",
				//"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='MNPS Library Services'), (SELECT id from permissions where name='View All Collection Reports'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View All Collection Reports'))",
			],
		], //collection_report_permissions
	];
}