<?php
/** @noinspection PhpUnused */
function getUpdates23_10_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //name*/

		//mark - ByWater
		'optionalUpdates' => [
			'title' => 'Optional Updates',
			'description' => 'Add the ability to publish optional updates for administrators',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS optional_updates(
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					name varchar(50) COLLATE utf8mb4_general_ci NOT NULL UNIQUE,
					descriptionFile VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
					versionIntroduced VARCHAR(8),
					status INT(1)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('System Administration', 'Run Optional Updates', '', 22, 'Allows the user to apply optional updates to their system.')
					",
				'addOptionalUpdatesPermission',
			]
		], //optionalUpdates
		'optionalUpdates23_10' => [
			'title' => 'Optional Updates 23.10',
			'description' => 'Add an optional updates for 23.10',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO optional_updates(name, descriptionFile, versionIntroduced, status) VALUES ('moveSearchToolsToTop', 'MoveSearchToolsToTop.MD', '23.10.00', 1)",
				"INSERT INTO optional_updates(name, descriptionFile, versionIntroduced, status) VALUES ('useFloatingCoverStyle', 'UseFloatingCoverStyle.MD', '23.10.00', 1)",
				"INSERT INTO optional_updates(name, descriptionFile, versionIntroduced, status) VALUES ('displayCoversForEditions', 'DisplayCoversForEditions.MD', '23.10.00', 1)",
				"INSERT INTO optional_updates(name, descriptionFile, versionIntroduced, status) VALUES ('enableNewBadge', 'EnableNewBadge.MD', '23.10.00', 1)",
			]
		], //optionalUpdates23_10
		'increase_scoping_field_lengths_2' => [
			'title' => 'Increase Scoping Field Lengths',
			'description' => 'Increase Scoping Field Lengths for libraries with lots of locations',
			'sql' => [
				"ALTER TABLE grouped_work_record_items CHANGE COLUMN locationOwnedScopes locationOwnedScopes TEXT DEFAULT ('~')",
				"ALTER TABLE grouped_work_record_items CHANGE COLUMN libraryOwnedScopes libraryOwnedScopes TEXT DEFAULT ('~')",
				"ALTER TABLE grouped_work_record_items CHANGE COLUMN recordIncludedScopes recordIncludedScopes TEXT DEFAULT ('~')",
			],
		],

		//kirstien - ByWater
		'add_always_display_renew_count' => [
			'title' => 'Add option to always show renewal count',
			'description' => 'Add option in Library Systems to always show the renewal count for a checkout',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD alwaysDisplayRenewalCount TINYINT(1) default 0',
			]
		], //add_always_display_renew_count
		'add_lida_system_messages_options' => [
			'title' => 'System messages in LiDA',
			'description' => 'Add options for pushing system messages to LiDA',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE system_messages ADD appMessage VARCHAR(280) default NULL',
				'ALTER TABLE system_messages ADD pushToApp TINYINT(1) default 0',
			]
		], //add_lida_system_messages_options

		//kodi - ByWater
		'theme_explore_more_images' => [
			'title' => 'Theme - Set custom images for explore more categories',
			'description' => 'Update theme table to have custom image values for each explore more category',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN catalogImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN genealogyImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN articlesDBImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN eventsImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN listsImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN libraryWebsiteImage VARCHAR(100) default ''",
				"ALTER TABLE themes ADD COLUMN historyArchivesImage VARCHAR(100) default ''",
			],
		],
		//theme_explore_more_images
		'permissions_self_reg_forms' => [
			'title' => 'Alters permissions for Custom Self Registration Forms',
			'description' => 'Create permissions for altering custom self registration forms',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Cataloging & eContent', 'Administer Self Registration Forms', 'Cataloging & eContent', 20, 'Allows the user to alter custom self registration forms for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Self Registration Forms'))",
			],
		],
		//permissions_self_reg_forms
		'self_registration_form' => [
			'title' => 'Self Registration Form',
			'description' => 'Setup tables to store data for custom self registration forms',
			'sql' => [
				"CREATE TABLE self_registration_form (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL UNIQUE
				)",
				"CREATE TABLE self_reg_form_values (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					selfRegistrationFormId INT NOT NULL, 
					weight INT NOT NULL DEFAULT '0',
					symphonyName VARCHAR(50) NOT NULL, 
					displayName VARCHAR(50) NOT NULL,
					fieldType ENUM ('text', 'date') NOT NULL DEFAULT 'text',
					patronUpdate ENUM ('read_only','hidden','editable','editable_required') NOT NULL DEFAULT 'editable',
					required TINYINT NOT NULL DEFAULT '0',
					note VARCHAR(75),
					UNIQUE groupValue (selfRegistrationFormId, symphonyName)
				) ENGINE = InnoDB",
			],
		],
		// self_registration_form
		'self_reg_default' => [
			'title' => 'Symphony Self Registration Default Values',
			'description' => 'Adds a default registration form for Symphony',
			'sql' => [
				"INSERT INTO self_registration_form (id, name) VALUES (1, 'default')",
				"INSERT INTO self_reg_form_values VALUES 
                             (1,1,1, 'firstName', 'First Name', 'text', 'read_only', 1, NULL),
                             (2,1,2, 'middleName', 'Middle Name', 'text', 'read_only', 0, NULL),
                             (3,1,3, 'lastName', 'Last Name', 'text', 'read_only', 1, NULL),
                             (4,1,4, 'dob', 'Date of Birth', 'date', 'read_only', 0, NULL),
                             (5,1,5, 'street', 'Street', 'text', 'editable_required', 1, NULL),
                             (6,1,6, 'apt_suite', 'APT', 'text', 'editable', 0, NULL),
                             (7,1,7, 'city', 'City', 'text', 'editable_required', 1, NULL),
                             (8,1,8, 'state', 'State', 'text', 'editable_required', 1, NULL),
                             (9,1,9, 'zip', 'Zip Code', 'text', 'editable_required', 1, NULL),
                             (10,1,10, 'phone', 'Primary Phone','text', 'editable', 0, NULL),
                             (11,1,11, 'cellphone', 'Cellphone', 'text', 'editable', 0, NULL),
                             (12,1,12, 'homephone', 'Home Phone', 'text', 'editable', 0, NULL),
                             (13,1,13, 'email', 'Email', 'text', 'editable', 0, NULL)",
			],
		],
		// self_reg_default
		'self_reg_form_id' => [
			'title' => "Self Registration Form Id",
			'description' => "Adds self registration form ID to library table",
			'sql' => [
				"ALTER TABLE library ADD COLUMN selfRegistrationFormId INT(11) DEFAULT -1",
			],
		],
		//self_reg_form_id
	];
}

function addOptionalUpdatesPermission(&$update){
	$dbMaintenancePermission = new Permission();
	$dbMaintenancePermission->name = 'Run Database Maintenance';
	$numUpdates = 0;
	if ($dbMaintenancePermission->find(true)) {
		$optionalUpdatesPermission = new Permission();
		$optionalUpdatesPermission->name = 'Run Optional Updates';
		if ($optionalUpdatesPermission->find(true)) {
			$dbMaintenanceRolePermission = new RolePermissions();
			$dbMaintenanceRolePermission->permissionId = $dbMaintenancePermission->id;
			$dbMaintenanceRolePermission->find();
			while ($dbMaintenanceRolePermission->fetch()){
				$newOptionalUpdateRolePermission = new RolePermissions();
				$newOptionalUpdateRolePermission->roleId = $dbMaintenanceRolePermission->roleId;
				$newOptionalUpdateRolePermission->permissionId = $optionalUpdatesPermission->id;
				$newOptionalUpdateRolePermission->insert();
				$numUpdates++;
			}
		}
	}
	$update['status'] = "<strong>Added permission to $numUpdates roles</strong><br/>";
	$update['success'] = true;
}