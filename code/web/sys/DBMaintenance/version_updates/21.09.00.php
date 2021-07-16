<?php
/** @noinspection PhpUnused */
function getUpdates21_09_00() : array
{
	return [
		'compress_novelist_fields' => [
			'title' => 'Add Compression for Novelist fields',
			'description' => 'Add Compression for fields that store metadata especially fields that are infrequently used',
			'sql' => [
				'ALTER TABLE novelist_data change column jsonResponse jsonResponse MEDIUMBLOB',
				'UPDATE novelist_data set jsonResponse = COMPRESS(jsonResponse)',
				'OPTIMIZE TABLE novelist_data',
			]
		], //compress_novelist_fields
		'compress_hoopla_fields' => [
			'title' => 'Add Compression for Hoopla fields',
			'description' => 'Add Compression for fields that store metadata especially fields that are infrequently used',
			'sql' => [
				'ALTER TABLE hoopla_export change column rawResponse rawResponse MEDIUMBLOB',
				'UPDATE hoopla_export set rawResponse = COMPRESS(rawResponse)',
				'OPTIMIZE TABLE hoopla_export',
			]
		], //compress_hoopla_fields
		'compress_overdrive_fields' => [
			'title' => 'Add Compression for OverDrive fields',
			'description' => 'Add Compression for fields that store metadata especially fields that are infrequently used',
			'sql' => [
				'ALTER TABLE overdrive_api_product_metadata change column rawData rawData MEDIUMBLOB',
				'UPDATE overdrive_api_product_metadata set rawData = COMPRESS(rawData)',
				'OPTIMIZE TABLE overdrive_api_product_metadata',
			]
		], //compress_hoopla_fields
		'user_payments_cancelled' => [
			'title' => 'User payments add cancelled field',
			'description' => 'Add cancelled field for user payments',
			'sql' => [
				'ALTER TABLE user_payments ADD COLUMN cancelled TINYINT(1)',
			]
		], //user_payments_cancelled
		'removeProPayFromLibrary' => [
			'title' => 'Remove ProPay From Library',
			'description' => 'Remove unused ProPayFields from library settings',
			'sql' => [
				'ALTER TABLE library DROP COLUMN proPayAccountNumber',
				'ALTER TABLE library DROP COLUMN proPayAgencyCode',
			]
		], //removeProPayFromLibrary
		'propay_settings' => [
			'title' => 'Add settings for ProPay',
			'description' => 'Add settings for ProPay integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS propay_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					useTestSystem TINYINT(1),
					authenticationToken CHAR(36),
					billerAccountId LONG,
					merchantProfileId LONG,
					payerAccountId LONG
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer ProPay', '', 10, 'Controls if the user can change ProPay settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer ProPay'))",
				"ALTER TABLE library ADD COLUMN proPaySettingId INT(11) DEFAULT -1"
			]
		], //propay_settings
		'paypal_settings' => [
			'title' => 'Add settings for PayPal',
			'description' => 'Add settings for PayPal integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS paypal_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					sandboxMode TINYINT(1),
					clientId VARCHAR(80),
					clientSecret VARCHAR(80)
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer PayPal', '', 10, 'Controls if the user can change PayPal settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer PayPal'))",
				"ALTER TABLE library ADD COLUMN payPalSettingId INT(11) DEFAULT -1"
			]
		], //paypal_settings
		'worldpay_settings' => [
			'title' => 'Add settings for WorldPay',
			'description' => 'Add settings for WorldPay integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS worldpay_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					merchantCode VARCHAR(20),
					settleCode VARCHAR(20)
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer WorldPay', '', 10, 'Controls if the user can change WorldPay settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer WorldPay'))",
				"ALTER TABLE library ADD COLUMN worldPalSettingId INT(11) DEFAULT -1"
			]
		], //worldpay_settings
		'worldpay_setting_typo' => [
			'title' => 'Fix typo in WorldPay settings',
			'description' => 'Fix typo in WorldPay settings',
			'sql' => [
				"ALTER TABLE library CHANGE COLUMN worldPalSettingId worldPaySettingId INT(11) DEFAULT -1"
			]
		], //worldpay_setting_typo
		'store_marc_in_db' => [
			'title' => 'Store MARC data in DB',
			'description' => 'Update to store MARC data in the database',
			'sql' => [
				"RENAME TABLE ils_marc_checksums TO ils_records",
				"ALTER TABLE ils_records ADD COLUMN deleted TINYINT(1)",
				"ALTER TABLE ils_records ADD COLUMN dateDeleted INT(11)",
				"ALTER TABLE ils_records ADD COLUMN suppressed TINYINT(1)",
				"ALTER TABLE ils_records ADD COLUMN suppressionReason INT(11)",
				"ALTER TABLE ils_records ADD COLUMN sourceData MEDIUMBLOB",
				"CREATE TABLE ils_suppression_reasons (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					reason VARCHAR(100) UNIQUE
				) ENGINE INNODB"
			]
		], //store_marc_in_db
		'marc_last_modified' => [
			'title' => 'MARC last modified',
			'description' => 'Add last modified date to ils_records',
			'sql' => [
				'ALTER TABLE ils_records ADD COLUMN lastModified INT(11)'
			]
		], //marc_last_modified
		'createSearchInterface_libraries_locations' => [
			'title' => 'Allow Libraries and Locations with no search interface',
			'description' => 'Allow some libraries and locations to be non-searchable to save memory and indexing time',
			'sql' => [
				"ALTER TABLE library ADD COLUMN createSearchInterface TINYINT(1) DEFAULT 1",
				"ALTER TABLE location ADD COLUMN createSearchInterface TINYINT(1) DEFAULT 1",
			]
		], //createSearchInterface_libraries_locations
		'normalize_scope_data' => [
			'title' => 'Normalize Scope Data',
			'description' => 'Normalize Scope Data to minimize data stored and speed insertions',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS grouped_work_record_scope_details (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedStatusId INT(11),
					statusId INT(11),
					available TINYINT(1),
					holdable TINYINT(1),
					inLibraryUseOnly TINYINT(1),
					localUrl VARCHAR(1000),
					locallyOwned TINYINT(1),
					libraryOwned TINYINT(1),
					UNIQUE (groupedStatusId, statusId, available, holdable, inLibraryUseOnly, localUrl, locallyOwned, libraryOwned)
				) ENGINE INNODB',
				"INSERT INTO grouped_work_record_scope_details (groupedStatusId, statusId, available, holdable, inLibraryUseOnly, localUrl, locallyOwned, libraryOwned) select groupedStatusId, statusId, available, holdable, inLibraryUseOnly, localUrl, locallyOwned, libraryOwned from grouped_work_record_scope group by groupedStatusId, statusId, available, holdable, inLibraryUseOnly, localUrl, locallyOwned, libraryOwned",
				"DROP INDEX groupedWorkItemId on grouped_work_record_scope",
				"DROP INDEX scopeId on grouped_work_record_scope",
				"ALTER TABLE grouped_work_record_scope ADD COLUMN scopeDetailsId INT(11)",
				"update grouped_work_record_scope inner join grouped_work_record_scope_details on 
				      grouped_work_record_scope_details.groupedStatusId = grouped_work_record_scope.groupedStatusId and 
				      grouped_work_record_scope_details.statusId = grouped_work_record_scope.statusId and 
				      grouped_work_record_scope_details.available = grouped_work_record_scope.available and
				      grouped_work_record_scope_details.holdable = grouped_work_record_scope.holdable and 
				      grouped_work_record_scope_details.inLibraryUseOnly = grouped_work_record_scope.inLibraryUseOnly and
				      (grouped_work_record_scope_details.localUrl = grouped_work_record_scope.localUrl OR  ( grouped_work_record_scope_details.localUrl is null and grouped_work_record_scope.localUrl is null)) and
				      grouped_work_record_scope_details.locallyOwned = grouped_work_record_scope.locallyOwned and  
				      grouped_work_record_scope_details.libraryOwned = grouped_work_record_scope.libraryOwned
				   SET scopeDetailsId = grouped_work_record_scope_details.id",
				"ALTER TABLE grouped_work_record_scope DROP COLUMN groupedStatusId, DROP COLUMN statusId, DROP COLUMN available, DROP COLUMN holdable, inLibraryUseOnly, localUrl, DROP COLUMN locallyOwned, DROP COLUMN libraryOwned, DROP COLUMN id",
				"OPTIMIZE table grouped_work_record_scope"
			]
		], //normalize_scope_data
	];
}