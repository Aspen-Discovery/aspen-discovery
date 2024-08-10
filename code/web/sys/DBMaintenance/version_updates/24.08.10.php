<?php

function getUpdates24_08_10(): array {
	return [
        /*'name' => [
            'title' => '',
            'description' => '',
            'continueOnError' => false,
            'sql' => [
                ''
            ]
		], //name*/

		//kodi - ByWater
		'self_registration_form_sierra' => [
			'title' => 'Self Registration for Sierra',
			'description' => 'Creates self registration form table for Sierra',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS self_registration_form_sierra (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					selfRegistrationTemplate VARCHAR(25) default "default",
    				selfRegEmailBarcode TINYINT NOT NULL default 0,
    				termsOfServiceSetting int NOT NULL default -1
				) ENGINE INNODB',
			],
		], // self_registration_form_sierra
		'self_reg_values_column_name' => [
			'title' => 'ILS Self Reg Field Names',
			'description' => 'Rename column in self_reg_values to be ilsName instead of symphonyName as it applies to multiple ILSes',
			'sql' => [
				"ALTER TABLE self_reg_form_values RENAME COLUMN symphonyName TO ilsName",
			],
		], //self_reg_values_column_name
		'carlx_tos' => [
			'title' => 'Terms of Service for CarlX Self Registration',
			'description' => 'Add terms of service functionality to CarlX self registration.',
			'sql' => [
				"ALTER TABLE self_registration_form_carlx ADD COLUMN termsOfServiceSetting int NOT NULL default -1"
			]
		], //carlx_tos

		//James Staub - Nashville Public Library

		'snappay_settings' => [
			'title' => 'SnapPay Settings',
			'description' => 'Add eCommerce vendor SnapPay.',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS snappay_settings (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL UNIQUE,
                    sandboxMode TINYINT NOT NULL DEFAULT 0,
                    accountId BIGINT(10) NOT NULL,
                    merchantId VARCHAR(20) NOT NULL,
    				apiAuthenticationCode VARCHAR(255) NOT NULL,
    				snapPayHMACSignature VARCHAR(255) NOT NULL
                ) ENGINE = InnoDB',
				'ALTER TABLE library ADD COLUMN snapPaySettingId INT(11) DEFAULT -1',
			],
		], //snappay_settings

		'permissions_ecommerce_snappay' => [
			'title' => 'Add permissions for SnapPay',
			'description' => 'Create permissions for administration of SnapPay',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer SnapPay', '', 10, 'Controls if the user can change SnapPay settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer SnapPay'))",
			],
		], //permissions_ecommerce_snappay
	];
}