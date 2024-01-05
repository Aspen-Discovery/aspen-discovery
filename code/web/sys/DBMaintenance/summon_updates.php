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
		'createSummonPermissions' => [
			'title' => 'Create Summon Permissions',
			'description' => 'Create Summon Permissions',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer Summon', 'Summon', 180, 'Allows the user configure Summon integration for all libraries.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('', 'Library Summon Options', '', 49, 'Configure Library fields related to Summon content.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Summon Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Summon Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Summon'))",
			],
		],
    ];
}