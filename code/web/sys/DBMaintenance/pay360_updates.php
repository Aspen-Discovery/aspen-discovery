<?php /** @noinspection SqlResolve */
function getPay360Updates() {
	return [
		'add_pay360_setting_table' => [
			'title' => 'Pay360 Settings Are Stored',
			'description' => 'Pay360 settings are stored so they can be administered and assigned to libraries',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE pay360_setting (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(50) NOT NULL UNIQUE,
				wsdlUrl VARCHAR(255),
				privateKey VARCHAR(255) NOT NULL,
				scpId VARCHAR(50),
				hmacKeyId VARCHAR(50),
				algorithm VARCHAR(50),
				siteId VARCHAR(50),
				subjectType VARCHAR(50),
				systemCode VARCHAR(50),
				pollingEnabled tinyint(1) DEFAULT 1
				)"
			]
		], // add_pay360_setting_table
		'add_pay360_permissions' => [
			'title' => 'Create Pay360 Permissions',
			'description' => 'Add an Pay360 permission section containing the permissions to do with this module',
			'sql' => [
				"INSERT INTO permissions (name, sectionName, weight, description) VALUES ( 'Administer Pay360','eCommerce', 10, 'Allows the user to administer the integration with Pay360. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Pay360'))",
			],
		], // add_pay360_permissions
		'add_pay360_setting_id_to_library' => [
			'title' => 'Add Pay360 Setting Id To Library',
			'description' => 'Add an pay360SettingId property to libraries so that they can be assigned the relevant Pay360 Setting',
			'sql' => [
				"ALTER TABLE library ADD pay360SettingId INT NOT NULL DEFAULT -1",
			],
		], // add_pay360_setting_id_to_library
		'add_pay360_setting_id_to_location' => [
			'title' => 'Add Pay360 Setting Id To Location',
			'description' => 'Add an pay360SettingId property to locations so that they can be assigned the relevant Pay360 Setting',
			'sql' => [
				"ALTER TABLE location ADD pay360SettingId INT NOT NULL DEFAULT -1",
			],
		], // add_pay360_setting_id_to_location
		'add_declined_status_to_user_payment' => [
			'title' => 'Add Declined Status To User Payment',
			'description' => 'add a declined column to the user payment table so this payment attempt outcome is accounted for',
			'sql' => [
				"ALTER TABLE user_payments ADD declined TINYINT(1) DEFAULT 0",
			],
		], //'add_declined_status_to_user_payment'
		'add_pay360TransactionStateMessage_to_user_payment' => [
			'title' => 'Add Pay360 Payment Transaction State To User Payment',
			'description' => 'stores the transaction state for an approved payment as returned by Pay360',
			'sql' => [
				"ALTER TABLE user_payments ADD pay360TransactionStateMessage VARCHAR(255)",
			],
		], // 'add_pay360TransactionStateMessage_to_user_payment'
		'add_pay360Timestamp_to_user_payment' => [
			'title' => 'Add Pay360 Payment Timestamp To User Payment',
			'description' => 'stores the timestamp for a given pay360 payment attempt',
			'sql' => [
				"ALTER TABLE user_payments ADD pay360Timestamp VARCHAR(14)",
			],
		], // 'add_pay360Timestamp_to_user_payment'
	];
}