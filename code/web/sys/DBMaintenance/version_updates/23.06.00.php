<?php
/** @noinspection PhpUnused */
function getUpdates23_06_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/

		//mark
		//kirstien
		'add_ecommerce_payflow_settings' => [
			'title' => 'Add eCommerce vendor PayPal Payflow',
			'description' => 'Create tables to store settings for PayPal Payflow',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS paypal_payflow_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) NOT NULL UNIQUE,
					sandboxMode TINYINT(1) DEFAULT 0,
					layoutType VARCHAR(2) DEFAULT "AB" NOT NULL,
					partner VARCHAR(72) NOT NULL,
					vendor VARCHAR(72) NOT NULL,
					user VARCHAR(72) NOT NULL,
					password VARCHAR(72) NOT NULL
				) ENGINE INNODB',
				'ALTER TABLE library ADD COLUMN paypalPayflowSettingId INT(11) DEFAULT -1',
			],
		],
		// add_ecommerce_payflow_settings
		'permissions_ecommerce_payflow' => [
			'title' => 'Add permissions for PayPal Payflow',
			'description' => 'Create permissions for administration of PayPal Payflow',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer PayPal Payflow', '', 10, 'Controls if the user can change PayPal Payflow settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer PayPal Payflow'))",
			],
		],
		// permissions_ecommerce_payflow
		//kodi
		//other
	];
}