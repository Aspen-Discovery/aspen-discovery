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
		'third_party_registration' => [
			'title' => 'Third Party Registration',
			'description' => 'Configuration of Third Party Registration ',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN enableThirdPartyRegistration TINYINT DEFAULT 0',
				'ALTER TABLE library ADD COLUMN thirdPartyRegistrationLocation INT(11) DEFAULT -1',
				'ALTER TABLE library ADD COLUMN thirdPartyPTypeAddressValidated INT(11) DEFAULT -1',
				'ALTER TABLE library ADD COLUMN thirdPartyPTypeAddressNotValidated INT(11) DEFAULT -1',
				"UPDATE permissions set name = 'Library Registration', description = 'Configure Library fields related to how Self Registration and Third Party Registration is configured in Aspen.' WHERE name = 'Library Self Registration'",
			],
		], //third_party_registration
		'update_collection_spotlight_number_of_titles' => [
			'title' => 'Update Collection Spotlight Minimum Number of Titles',
			'description' => 'Update Collection Spotlight Minimum Number of Titles',
			'continueOnError' => true,
			'sql' => [
				'update collection_spotlights set numTitlesToShow = 25 where numTitlesToShow = 0;',
			],
		], //update_collection_spotlight_number_of_titles

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
		'theme_cover_default_image' => [
			'title' => 'Theme - Set default image for cover images',
			'description' => 'Update theme table to have default values for the default cover image',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN defaultCover VARCHAR(100) default ''",
			],
		], //theme_cover_default_image
		'theme_format_category_icons' => [
			'title' => 'Theme - Set custom icon images for format category icons',
			'description' => 'Update theme table to have custom icon image values for format category icons',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN booksImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN eBooksImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN audioBooksImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN musicImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN moviesImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN booksImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN eBooksImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN audioBooksImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN musicImageSelected VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN moviesImageSelected VARCHAR(100) default ''",
			],
		], //theme_format_category_icons
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