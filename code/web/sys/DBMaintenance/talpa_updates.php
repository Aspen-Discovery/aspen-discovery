<?php

function getTalpaUpdates() {
	return [
		'create_talpa_module' => [
			'title' => 'Create Talpa Module',
			'description' => 'Setup Talpa module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Talpa Search', '', '')",
			],
		], //create_talpa_module
		'createSettingsForTalpa' => [
			'title' => 'Create Talpa settings',
			'description' => 'Create settings to store information for Talpa Search Integrations',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE talpa_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					talpaApiToken VARCHAR(32) DEFAULT '',
					talpaSearchSourceString VARCHAR(32) DEFAULT 'Talpa Search',
					talpaTryItButton TINYINT(1) UNSIGNED DEFAULT 2,
					tryThisSearchInTalpaText VARCHAR(32) DEFAULT 'Try this search in Talpa',
					tryThisSearchInTalpaSidebarSwitch TINYINT(1) UNSIGNED DEFAULT 1,
					tryThisSearchInTalpaNoResultsSwitch TINYINT(1) UNSIGNED DEFAULT 1,
					talpaExplainerText MEDIUMTEXT,
					includeTalpaLogoSwitch TINYINT(1) UNSIGNED DEFAULT 1,
					includeTalpaOtherResultsSwitch TINYINT(1) UNSIGNED DEFAULT 1,
					talpaOtherResultsExplainerText VARCHAR(180) DEFAULT 'Talpa Search found these other results not owned by your library.'
				) ENGINE=INNODB",
				'ALTER TABLE library ADD COLUMN talpaSettingsId INT(11) DEFAULT -1',
			],
		], //createSettingsForTalpa
		'createWorkMappingForTalpa' => [
			'title' => 'Create groupedWorkID mapping table for Talpa Search',
			'description' => 'Allows Talpa to return grouped work results in Talpa Search module.',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE talpa_ltwork_to_groupedwork (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedRecordPermanentId CHAR(40),
					lt_workcode INT(11) UNSIGNED,
					checked TINYINT(1) SIGNED DEFAULT 0,
					INDEX(`groupedRecordPermanentId`),
					INDEX(`lt_workcode`)
				) ENGINE = InnoDB"
			]
		], //createWorkMappingForTalpa
	];
}
