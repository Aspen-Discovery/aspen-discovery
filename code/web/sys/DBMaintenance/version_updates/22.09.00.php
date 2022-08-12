<?php
/** @noinspection PhpUnused */
function getUpdates22_09_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'vdx_hold_groups' => [
			'title' => 'VDX Hold Group setup',
			'description' => 'Add the ability to add VDX Hold Groups to the site',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS vdx_hold_groups(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE
						) ENGINE = INNODB;',
				'CREATE TABLE vdx_hold_group_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							vdxHoldGroupId INT,
							locationId INT,
							UNIQUE INDEX vdxHoldGroupLocation(vdxHoldGroupId, locationId)
						) ENGINE = INNODB;',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer VDX Hold Groups', '', 15, 'Allows the user to define Hold Groups for Interlibrary Loans with VDX.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer VDX Hold Groups'))",
			]
		],
	];
}