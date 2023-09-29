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
		], //theme_explore_more_images
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