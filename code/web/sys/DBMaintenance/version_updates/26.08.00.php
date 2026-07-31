<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_08_00(): array {
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
		'display_sort_term_values' => [
			'title' => 'Display Sort Term Values',
			'description' => 'Add configuration option to dynamically show sort term values for total checkouts, date added, number of holds, and call number.',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD displaySortTermValue TINYINT(1) DEFAULT 0",
			],
		], //display_sort_term_values
		'localhop_images' => [
			'title' => 'LocalHop Images',
			'description' => 'Add setting for toggling use of LocalHop images for event covers',
			'sql' => [
				'ALTER TABLE localhop_settings ADD COLUMN useLocalHopImages tinyint(1) NOT NULL DEFAULT 0',
			]
		], //localhop_images

		//yanjun

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen
		'permissions_edit_payment_status' => [
			'title' => 'Create Edit Payment Status permission',
			'description' => 'Adds a permission to edit statuses in eCommerce reports.',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Edit Payment Status', '', 10, 'Allows the user to manually update payment statuses')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Edit Payment Status'))",
			]
		], //permissions_edit_payment_status
		'add_edited_status_to_user_payments' => [
			'title' => 'Add editedStatus to user_payments table',
			'description' => 'Adds a column to store a manually edited payment status.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_payments ADD COLUMN editedStatus VARCHAR(20) NOT NULL DEFAULT ''",
			]
		], //add_edited_status_to_user_payments

		//other

	];
}
