<?php
/** @noinspection PhpUnused */
function getUpdates23_01_00(): array
{
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/

		//mark

		//kirstien
		'add_account_alerts_notification' => [
			'title' => 'Add account alert notification type',
			'description' => 'Adds account alert notifications',
			'sql' => [
				'ALTER TABLE user_notification_tokens ADD COLUMN notifyAccount TINYINT(1) DEFAULT 0',
			],
		],
		//add_account_alerts_notification
		'add_invoiceCloud' => [
			'title' => 'Add eCommerce vendor InvoiceCloud',
			'description' => 'Create InvoiceCloud settings table, update available permissions',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS invoice_cloud_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) NOT NULL UNIQUE,
					apiKey VARCHAR(500) NOT NULL,
					invoiceTypeId INT(10),
					ccServiceFee VARCHAR(50)
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer InvoiceCloud', '', 10, 'Controls if the user can change InvoiceCloud settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer InvoiceCloud'))",
				'ALTER TABLE library ADD COLUMN invoiceCloudSettingId INT(11) DEFAULT -1',
			],
		],
		//add_invoiceCloud

		//kodi
		'user_browse_add_home' => [
			'title' => 'Add New Browse Categories to Home',
			'description' => 'Store user selection for adding browse categories to home page',
			'sql' => [
				'ALTER TABLE user ADD COLUMN browseAddToHome TINYINT(1) DEFAULT 1',
			],
		],
		//user_browse_add_home
		//other
	];
}