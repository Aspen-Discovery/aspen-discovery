<?php
/** @noinspection SqlResolve */
function getSummonUpdates() {
	return [
        'createSummonModule' => [
			'title' => 'Create Summon module',
			'description' => 'Setup modules for Summon Integration',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Summon', '', '')",

			],
		],
		'createSettingsForSummon' => [
			'title' => 'Create Summon settings',
			'description' => 'Create settings to store information for Summon Integrations',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE summon_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					edsApiProfile VARCHAR(50) DEFAULT '',
					summonBaseApi VARCHAR(50) DEFAULT '',
					summonApiId VARCHAR(50) DEFAULT '',
					summonApiPassword VARCHAR(50) DEFAULT ''
				) ENGINE INNODB",
				'ALTER TABLE library ADD COLUMN summonSettingsId INT(11) DEFAULT -1',
			],
		],
    ];
}