<?php
/** @noinspection SqlResolve */
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
		'ebsco_eds_increase_id_length' => [
			'title' => 'EBSCO EDS Usage - Increase ebscoId length',
			'description' => 'Increase length of ebscoId in ebsco eds usage table.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE ebsco_eds_usage CHANGE COLUMN ebscoId ebscoId VARCHAR(100) NOT NULL",
			],
		],
		'ebsco_eds_research_starters' => [
			'title' => 'EBSCO EDS research starters',
			'description' => 'Setup information to handle research starters form EBSCO',
			'sql' => [
				'CREATE table ebsco_research_starter (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					ebscoId VARCHAR(100) NOT NULL UNIQUE,
					title VARCHAR(255)
				)',
				'CREATE table ebsco_research_starter_dismissals (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					researchStarterId INT NOT NULL,
					userId INT NOT NULL
				)',
				'ALTER TABLE ebsco_research_starter_dismissals ADD UNIQUE INDEX (userId, researchStarterId)',
				'ALTER TABLE user ADD COLUMN hideResearchStarters TINYINT(1) DEFAULT 0',
			]
		],

		'ebsco_eds_usage_add_instance' => [
			'title' => 'EBSCO EDS Usage - Instance Information',
			'description' => 'Add Instance Information to EBSCO EDS Usage stats',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ebsco_eds_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE ebsco_eds_usage DROP INDEX ebscoId',
				'ALTER TABLE ebsco_eds_usage ADD UNIQUE INDEX (instance, ebscoId, year, month)',
				'ALTER TABLE user_ebsco_eds_usage ADD COLUMN instance VARCHAR(100)',
				'ALTER TABLE user_ebsco_eds_usage DROP INDEX year',
				'ALTER TABLE user_ebsco_eds_usage ADD UNIQUE INDEX (instance, userId, year, month)',
			]
		],
	];
}
