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
		'vdx_settings' => [
			'title' => 'VDX Settings setup',
			'description' => 'Add the ability to add VDX Settings to the site',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS vdx_settings(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE,
							baseUrl VARCHAR(255) NOT NULL,
							submissionEmailAddress VARCHAR(255) NOT NULL
						) ENGINE = INNODB;',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer VDX Settings', '', 10, 'Allows the user to define settings for Interlibrary Loans with VDX.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer VDX Settings'))",
			]
		],
		'vdx_forms' => [
			'title' => 'VDX From setup',
			'description' => 'Add the ability to configure VDX forms for locations',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS vdx_form(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE,
							introText TEXT,
							showAuthor TINYINT(1) DEFAULT 0,
							showPublisher TINYINT(1) DEFAULT 0,
							showIsbn TINYINT(1) DEFAULT 0,
							showAcceptFee TINYINT(1) DEFAULT 0,
							showMaximumFee TINYINT(1) DEFAULT 0,
							feeInformationText TEXT,
							showCatalogKey TINYINT(1) DEFAULT 0
						) ENGINE = INNODB;',
				'CREATE TABLE vdx_form_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							vdxFormId INT,
							locationId INT,
							UNIQUE INDEX vdxFormLocation(vdxFormId, locationId)
						) ENGINE = INNODB;',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer All VDX Forms', '', 20, 'Allows the user to define administer all VDX Forms.'), 
							('ILL Integration', 'Administer Library VDX Forms', '', 22, 'Allows the user to define administer VDX Forms for their library.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All VDX Forms'))",
			]
		]
	];
}