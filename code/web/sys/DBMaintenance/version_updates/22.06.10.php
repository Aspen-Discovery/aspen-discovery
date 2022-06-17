<?php
/** @noinspection PhpUnused */
function getUpdates22_06_10() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'increase_course_reserves_source_length' => [
			'title' => 'Increase Course Reserves Source Length',
			'description' => 'Allow sourceId to be longer for course reserves entries',
			'sql' => [
				'ALTER TABLE course_reserve_entry CHANGE sourceId sourceId VARCHAR(40) COLLATE utf8mb4_general_ci DEFAULT NULL',
			]
		], //increase_course_reserves_source_length
		'ebscohost_search_settings' => [
			'title' => 'EBSCOhost search settings',
			'description' => 'Add configuration of database searching for EBSCOhost',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE ebscohost_database (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					searchSettingId INT NOT NULL,
					shortName VARCHAR(50) NOT NULL,
					displayName VARCHAR(50) NOT NULL,
					allowSearching TINYINT DEFAULT 1,
					searchByDefault TINYINT DEFAULT 1, 
					showInExploreMore TINYINT DEFAULT 0,
					showInCombinedResults TINYINT DEFAULT 0
				) ENGINE INNODB',
				'CREATE TABLE ebscohost_search_options (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) NOT NULL,
					settingId INT(11) NOT NULL
				) ENGINE INNODB',
				'ALTER TABLE library ADD COLUMN ebscohostSearchSettingId INT(11) DEFAULT -1',
				'ALTER TABLE location ADD COLUMN ebscohostSearchSettingId INT(11) DEFAULT -2',
			]
		], //ebscohost_search_settings
		//TODO: Upgrade existing settings to use new search settings
	];
}
