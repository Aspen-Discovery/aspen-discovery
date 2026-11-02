<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth
		//borrowbox
		'borrowbox_module' => [
			'title' => 'Create BorrowBox Module',
			'description' => 'Setup BorrowBox eContent integration module',
			'sql' => [
				"INSERT INTO modules (
					name,
					enabled,
					indexName,
					backgroundProcess,
					logClassPath,
					logClassName,
					settingsClassPath,
					settingsClassName
				) VALUES (
					'BorrowBox',
					0,
					'grouped_works',
					'borrowbox_extract',
					'/sys/BorrowBox/BorrowBoxExtractLogEntry.php',
					'BorrowBoxExtractLogEntry',
					'/sys/BorrowBox/BorrowBoxSetting.php',
					'BorrowBoxSetting'
				)",
			]
		], //borrowbox_module
		'borrowbox_permissions' => [
			'title' => 'BorrowBox Permissions',
			'description' => 'Add permissions for BorrowBox administration',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (
					sectionName,
					name,
					requiredModule,
					weight,
					description
				) VALUES (
					'eContent - BorrowBox',
					'Administer BorrowBox',
					'BorrowBox',
					10,
					'Allows the user to administer BorrowBox integration settings.'
				)",
				"INSERT INTO role_permissions (
					roleId,
					permissionId
				) VALUES (
					(SELECT roleId from roles where name = 'opacAdmin'),
					(SELECT id from permissions where name = 'Administer BorrowBox')
				)",
			]
		], //borrowbox_permissions

	];
}