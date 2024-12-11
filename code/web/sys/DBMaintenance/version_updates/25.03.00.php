<?php

function getUpdates25_03_00(): array {
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

		//mark - Grove

		//katherine - Grove

		//kirstien - Grove

		// Leo Stoyanov - BWS

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe
		'add_heycentric_setting_table' => [
			'title' => 'HeyCentric Settings Are Stored',
			'description' => 'HeyCentric settings are stored so they can be administered and assigned to libraries',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE heycentric_setting (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
				name VARCHAR(50) NOT NULL UNIQUE,		
				baseUrl VARCHAR(50) NOT NULL,
				privateKey VARCHAR(255) NOT NULL,
				client VARCHAR(10) NOT NULL,
				entity VARCHAR(10) NOT NULL,
				till VARCHAR(10) NOT NULL,
				area VARCHAR(10) NOT NULL,
				rurl TEXT NOT NULL DEFAULT '/MyAccount/AJAX?method=completeHeyCentricOrder'
				)"
			]
		], // add_heycentric_setting_table
		'add_heyCentric_permissions' => [
			'title' => 'Create HeyCentric Permissions',
			'description' => 'Add an HeyCentric permission section containing the permissions to do with this module',
			'sql' => [
				"INSERT INTO permissions (name, sectionName, weight, description) VALUES ( 'Administer HeyCentric Settings','ecommerce', 10, 'Allows the user to administer the integration with HeyCentric')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer HeyCentric Settings'))",
			],
		], // add_heyCentric_permissions
		'add_heycentric_setting_id_to_library' => [
			'title' => 'Add HeyCentric Setting Id To Library',
			'description' => 'Add an heyCentricSettingId property to libraries so that they can be assigned the relevant HeyCentric Setting',
			'sql' => [
				"ALTER TABLE library ADD heyCentricSettingId INT NOT NULL DEFAULT -1",
			],
		], // add_heycentric_setting_id_to_library
		'add_declined_status_to_user_payment' => [
			'title' => 'Add Declined Status To User Payment',
			'description' => 'add a declined column to the user payment table so this payment attempt outcome is accounted for',
			'sql' => [
				"ALTER TABLE user_payments ADD declined TINYINT(1) DEFAULT 0",
			],
		], //'add_declined_status_to_user_payment'
		'add_heyCentricPaymentReferenceNumber_to_user_payment' => [
			'title' => 'Add HeyCentric Payment Reference Number To User Payment',
			'description' => 'stores the reference number for an approved payment as returned by HeyCentric',
			'sql' => [
				"ALTER TABLE user_payments ADD heyCentricPaymentReferenceNumber INT",
			],
		], // 'add_heyCentricPaymentReferenceNumber_to_user_payment'

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}