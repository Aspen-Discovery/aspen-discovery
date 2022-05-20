<?php
/** @noinspection PhpUnused */
function getUpdates22_06_00() : array
{
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
				'ALTER TABLE location ADD COLUMN ebscohostSettingId INT(11) DEFAULT -2'
			]
		],//createSettingsforEBSCOhost
		'createPermissionsforEBSCOhost' => [
			'title' => 'Create permissions for EBSCOhost',
			'description' => 'Create permissions for creating and modifying EBSCOhost settings',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer EBSCOhost Settings', 'EBSCOhost', 20, 'Allows the user to administer integration with EBSCOhost')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer EBSCOhost Settings'))"
			]
		], //createPermissionsforEBSCOhost
		'indexAndSearchVersionVariables' => [
			'title' => 'Index and Search Version Variables',
			'description' => 'Add variables to determine what version should be ',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN indexVersion INT DEFAULT 2",
				"ALTER TABLE system_variables ADD COLUMN searchVersion INT DEFAULT 1",
			]
		], //indexAndSearchVersionVariables
	];
}
