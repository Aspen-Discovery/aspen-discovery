<?php
/** @noinspection PhpUnused */
function getUpdates23_08_00(): array {
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
		'custom_facets' => [
			'title' => 'Add custom facet indexing information to Indexing Profiles',
			'description' => 'Add custom facet indexing information to Indexing Profiles',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1ValuesToExclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2ValuesToExclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3ValuesToExclude TEXT",
				"UPDATE indexing_profiles set customFacet1ValuesToInclude = '.*'",
				"UPDATE indexing_profiles set customFacet2ValuesToInclude = '.*'",
				"UPDATE indexing_profiles set customFacet3ValuesToInclude = '.*'",
			]
		],
		'twilio_settings' => [
			'title' => 'Twilio Settings',
			'description' => 'Add twilio settings and permissions',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS twilio_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) UNIQUE,
					phone VARCHAR(15),
					accountSid VARCHAR(50),
					authToken VARCHAR(256)
				)",
				"ALTER TABLE library ADD COLUMN twilioSettingId INT(11) DEFAULT -1",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Administer Twilio', '', 34, 'Controls if the user can change Twilio settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Twilio'))",
			]
		],

		//kirstien - ByWater


		//kodi - ByWater

		'webpage_default_image' => [
			'title' => 'Website Indexing - Set default image for cover images',
			'description' => 'Update website_indexing_settings table to have default values for the default cover image',
			'sql' => [
				"ALTER TABLE website_indexing_settings ADD COLUMN defaultCover VARCHAR(100) default ''",
			],
		], //webpage_default_image
		'OAI_default_image' => [
			'title' => 'OAI Indexing - Set default image for cover images',
			'description' => 'Update open_archives_collection table to have default values for the default cover image',
			'sql' => [
				"ALTER TABLE open_archives_collection ADD COLUMN defaultCover VARCHAR(100) default ''",
			],
		], //OAI_default_image

		//other organizations

	];
}