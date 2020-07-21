<?php
function getEbscoUpdates(){
	return [
		'createEbscoModules' => [
			'title' => 'Create EBSCO modules',
			'description' => 'Setup modules for EBSCO Integration',
			'sql' =>[
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('EBSCO EDS', '', '')",
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('EBSCOhost','', '')"

			]
		],
		'createSettingsForEbscoEDS' => [
			'title' => 'Create EBSCO EDS settings',
			'description' => 'Create settings to store information for EBSCO EDS Integrations',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE ebsco_eds_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					edsApiProfile VARCHAR(50) DEFAULT '',
					edsSearchProfile VARCHAR(50) DEFAULT '',
					edsApiUsername VARCHAR(50) DEFAULT '',
					edsApiPassword VARCHAR(50) DEFAULT ''
				) ENGINE INNODB",
				'ALTER TABLE library DROP COLUMN edsApiProfile',
				'ALTER TABLE library DROP COLUMN edsApiUsername',
				'ALTER TABLE library DROP COLUMN edsApiPassword',
				'ALTER TABLE library DROP COLUMN edsSearchProfile',
				'ALTER TABLE library ADD COLUMN edsSettingsId INT(11) DEFAULT -1'
			]
		],
		'aspen_usage_ebsco_eds' => [
			'title' => 'Aspen Usage for EBSCO EDS Searches',
			'description' => 'Add a column to track usage of EBSCO EDS searches within Aspen',
			'continueOnError' => false,
			'sql' => array(
				'ALTER TABLE aspen_usage ADD COLUMN ebscoEdsSearches INT(11) DEFAULT 0',
			)
		],
		'track_ebsco_eds_user_usage' => [
			'title' => 'EBSCO EDS Usage by user',
			'description' => 'Add a table to track how often a particular user uses EBSCO EDS.',
			'sql' => [
				"CREATE TABLE user_ebsco_eds_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    userId INT(11) NOT NULL,
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    usageCount INT(11)
				) ENGINE = InnoDB",
				"ALTER TABLE user_ebsco_eds_usage ADD INDEX (year, month, userId)",
			],
		],
		'ebsco_eds_record_usage' => [
			'title' => 'EBSCO EDS Usage',
			'description' => 'Add a table to track how EBSCO EDS is used.',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE ebsco_eds_usage (
				    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				    ebscoId VARCHAR(15) NOT NULL,
				    month INT(2) NOT NULL,
				    year INT(4) NOT NULL,
				    timesViewedInSearch INT(11) NOT NULL,
				    timesUsed INT(11) NOT NULL
				) ENGINE = InnoDB",
				"ALTER TABLE ebsco_eds_usage ADD INDEX (ebscoId, year, month)",
			],
		],
	];
}
