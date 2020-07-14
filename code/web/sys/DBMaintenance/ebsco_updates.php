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
		]
	];
}
