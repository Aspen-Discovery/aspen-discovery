<?php
/** @noinspection PhpUnused */
function getUpdates22_02_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'aspen_lida_settings' => [
			'title' => 'Add settings for Aspen LiDA',
			'description' => 'Add settings for Aspen LiDA',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_lida_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					slugName VARCHAR(50) UNIQUE
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer Aspen LiDA Settings', '', 10, 'Controls if the user can change Aspen LiDA settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Aspen LiDA Settings'))",
			]
		], //aspen_lida_settings
		'open_archives_multiple_imageRegex' => [
			'title' => 'Open Archives Multiple imageRegex',
			'description' => 'Allow multiple Image Regular Expressions to be defined for an Open Archives Collection',
			'sql' => [
				"ALTER TABLE open_archives_collection CHANGE COLUMN imageRegex imageRegex TEXT"
			]
		], //open_archives_multiple_imageRegex
	];
}
