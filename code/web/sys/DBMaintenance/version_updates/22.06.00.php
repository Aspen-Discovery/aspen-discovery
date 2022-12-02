<?php
/** @noinspection PhpUnused */
function getUpdates22_06_00(): array {
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'createSettingsforEBSCOhost' => [
			'title' => 'Create EBSCOhost settings',
			'description' => 'Create settings to store information for EBSCOhost integrations',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE ebscohost_settings (
    				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    				name VARCHAR(50) NOT NULL UNIQUE,
    				authType VARCHAR(50) DEFAULT \'profile\',
    				profileId VARCHAR(50) DEFAULT \'\',
    				profilePwd VARCHAR(50) DEFAULT \'\',
    				ipProfileId VARCHAR(50)
			) ENGINE = InnoDB',
				'ALTER TABLE library ADD COLUMN ebscohostSettingId INT(11) DEFAULT -1',
				'ALTER TABLE location ADD COLUMN ebscohostSettingId INT(11) DEFAULT -2',
			],
		],
		//createSettingsforEBSCOhost
		'createPermissionsforEBSCOhost' => [
			'title' => 'Create permissions for EBSCOhost',
			'description' => 'Create permissions for creating and modifying EBSCOhost settings',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer EBSCOhost Settings', 'EBSCOhost', 20, 'Allows the user to administer integration with EBSCOhost')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer EBSCOhost Settings'))",
			],
		],
		//createPermissionsforEBSCOhost
		'indexAndSearchVersionVariables' => [
			'title' => 'Index and Search Version Variables',
			'description' => 'Add variables to determine what version should be ',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN indexVersion INT DEFAULT 2",
				"ALTER TABLE system_variables ADD COLUMN searchVersion INT DEFAULT 1",
			],
		],
		//indexAndSearchVersionVariables
		'grouped_work_language' => [
			'title' => 'Grouped Work Language',
			'description' => 'Add Language as a differentiator for Grouped Works',
			'sql' => [
				'ALTER TABLE grouped_work ADD COLUMN primary_language VARCHAR(3)',
			],
		],
		//grouped_work_language
		'regroupAllRecordsDuringNightlyIndex' => [
			'title' => 'Index and Search Version Variables',
			'description' => 'Add variables to determine what version should be ',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN regroupAllRecordsDuringNightlyIndex TINYINT DEFAULT 0",
				"UPDATE system_variables SET regroupAllRecordsDuringNightlyIndex = 1",
			],
		],
		//regroupAllRecordsDuringNightlyIndex
		'increase_grouped_work_length_for_language' => [
			'title' => 'Increase Grouped Work Length for language',
			'description' => 'Add variables to determine what version should be ',
			'sql' => [
				"ALTER TABLE grouped_work CHANGE COLUMN permanent_id permanent_id CHAR(40) NOT NULL UNIQUE",
				"ALTER TABLE grouped_work_scheduled_index CHANGE COLUMN permanent_id permanent_id CHAR(40) NOT NULL",
				"ALTER TABLE grouped_work_alternate_titles CHANGE COLUMN permanent_id permanent_id CHAR(40) NOT NULL",
				"ALTER TABLE grouped_work_display_info CHANGE COLUMN permanent_id permanent_id CHAR(40) NOT NULL UNIQUE",
				"ALTER TABLE novelist_data CHANGE COLUMN groupedRecordPermanentId groupedRecordPermanentId CHAR(40) NOT NULL",
				"ALTER TABLE syndetics_data CHANGE COLUMN groupedRecordPermanentId groupedRecordPermanentId CHAR(40) NOT NULL",
				"ALTER TABLE user_checkout CHANGE COLUMN groupedWorkId groupedWorkId CHAR(40)",
				"ALTER TABLE user_hold CHANGE COLUMN groupedWorkId groupedWorkId CHAR(40)",
				"ALTER TABLE user_list_entry CHANGE COLUMN sourceId sourceId VARCHAR(40) NOT NULL",
				"ALTER TABLE user_not_interested CHANGE COLUMN groupedRecordPermanentId groupedRecordPermanentId VARCHAR(40) NOT NULL",
				"ALTER TABLE user_reading_history_work CHANGE COLUMN groupedWorkPermanentId groupedWorkPermanentId VARCHAR(40)",
				"ALTER TABLE user_work_review CHANGE COLUMN groupedRecordPermanentId groupedRecordPermanentId VARCHAR(40) NOT NULL",

			],
		],
		//increase_grouped_work_length_for_language
		'createMaterialRequestStats' => [
			'title' => 'Create Material Request stats table',
			'description' => 'Track usage of material requests',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE materials_request_usage (
    				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    				locationId INT(4),
    				year INT(4) NOT NULL,
    				month INT(4) NOT NULL,
    				statusId INT(4) NOT NULL,
    				numUsed INT(11) NOT NULL DEFAULT 0
			) ENGINE = InnoDB',
			],
		],
		//createMaterialRequestStats
		'holdIsILL' => [
			'title' => 'Hold - Is ILL',
			'description' => 'Add a property to determine if a hold is ILL',
			'sql' => [
				'ALTER TABLE user_hold ADD COLUMN isIll TINYINT(1) DEFAULT 0',
			],
		],
		//holdIsILL
		'updateGroupedWorkFacetReadling' => [
			'title' => 'Fix spelling in Reading Levels',
			'description' => 'Fix spelling in displayNamePlural of Reading Levels',
			'sql' => [
				'UPDATE grouped_work_facet SET displayNamePlural="Reading Levels" WHERE displayNamePlural="Readling Levels"',
			],
		],
		//updateGroupedWorkFacetReadling
		'updateGroupedWorkFacetReadingtoAudience' => [
			'title' => 'Update Reading Level/s to Audience/s',
			'description' => 'Update Reading Level/s to Audience/s',
			'sql' => [
				'UPDATE grouped_work_facet SET displayNamePlural="Audiences" WHERE displayNamePlural="Reading Levels"',
				'UPDATE grouped_work_facet SET displayName="Audience" WHERE displayName="Reading Level"',
			],
		],
		//updateGroupedWorkFacetReadingtoAudience
		'updateDefaultConfiguration' => [
			'title' => 'Update default configuration',
			'description' => 'Update default configuration options',
			'sql' => [
				"ALTER TABLE library ALTER allowFreezeHolds SET DEFAULT '1'",
				"ALTER TABLE library ALTER maxDaysToFreeze SET DEFAULT '365'",
				"ALTER TABLE library ALTER allowMasqueradeMode SET DEFAULT '1'",
				"ALTER TABLE library ALTER enableMaterialsRequest SET DEFAULT '0'",
				"ALTER TABLE indexing_profiles ALTER treatUnknownAudienceAs SET DEFAULT 'General'",
				"ALTER TABLE indexing_profiles ALTER hideUnknownLiteraryForm SET DEFAULT '1'",
				"ALTER TABLE indexing_profiles ALTER hideNotCodedLiteraryForm SET DEFAULT '1'",
				"ALTER TABLE indexing_profiles ALTER checkRecordForLargePrint SET DEFAULT '1'",
				"ALTER TABLE overdrive_settings ALTER useFulfillmentInterface SET DEFAULT '1'",
			],
		],
		//updateDefaultConfiguration
		'updateDefaultConfiguration2' => [
			'title' => 'Update default configuration part 2',
			'description' => 'Update default configuration options',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE system_variables ALTER useHtmlEditorRatherThanMarkdown SET DEFAULT '1'",
			],
		],
		//updateDefaultConfiguration2
		'addRecommendedForYou' => [
			'title' => 'Add Recommended For You Browse Category',
			'description' => 'Adds system Recommended For You browse category if one has not been created yet',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO browse_category (textId, label, source) VALUES ('system_recommended_for_you', 'Recommended For You', 'GroupedWork')",
			],
		],
		//addRecommendedForYou
		'addWeightToDonationValue' => [
			'title' => 'Add weight column to donations_value',
			'description' => 'Add weight column to donations_value to allow sorting',
			'sql' => [
				'ALTER TABLE donations_value ADD COLUMN weight INT DEFAULT 0',
			],
		],
		//addWeightToDonationValue
	];
}
