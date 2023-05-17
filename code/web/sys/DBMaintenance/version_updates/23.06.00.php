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
		'add_sso_saml_student_attributes' => [
			'title' => 'Add settings to setup student users with SSO',
			'description' => 'Add settings to setup student users with SSO using SAML',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN samlStudentPTypeAttr VARCHAR(255) DEFAULT null',
				'ALTER TABLE sso_setting ADD COLUMN samlStudentPTypeAttrValue VARCHAR(255) DEFAULT null',
				'ALTER TABLE sso_setting ADD COLUMN samlStudentPType VARCHAR(30) DEFAULT null',
			],
		],
		//add_sso_saml_student_attributes
		'add_show_edition_covers' => [
			'title' => 'Add option to show edition covers',
			'description' => 'Add option to show individual covers for each edition',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE grouped_work_display_settings ADD COLUMN showEditionCovers TINYINT(1) DEFAULT 0',
			],
		],
		//add_show_edition_covers
		//kodi
		//other
	];
}