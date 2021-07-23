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
		'fix_dates_in_item_details' => [
			'title' => 'Fix dates in Item Details',
			'description' => 'Fix dates in Item Details',
			'sql' => [
				'ALTER TABLE grouped_work_record_items CHANGE COLUMN dateAdded dateAdded BIGINT',
				'ALTER TABLE grouped_work_record_items CHANGE COLUMN lastCheckInDate lastCheckInDate BIGINT',
			]
		], //fix_dates_in_item_details
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
				"ALTER TABLE grouped_work_record_scope DROP groupedStatusId, DROP statusId, DROP available, DROP holdable, DROP inLibraryUseOnly, DROP localUrl, DROP locallyOwned, DROP libraryOwned, DROP id",
				"OPTIMIZE table grouped_work_record_scope"
			]
		], //normalize_scope_data
		'move_unchanged_scope_data_to_item' => [
			'title' => 'Move scope data that does not vary to item',
			'description' => 'Move scope data that does not vary to item',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE grouped_work_record_items ADD COLUMN groupedStatusId INT(11)',
				'ALTER TABLE grouped_work_record_items ADD COLUMN available TINYINT(1)',
				'ALTER TABLE grouped_work_record_items ADD COLUMN holdable TINYINT(1)',
				'ALTER TABLE grouped_work_record_items ADD COLUMN inLibraryUseOnly TINYINT(1)',
				'UPDATE grouped_work_record_items as dest, 
					(SELECT groupedWorkItemId, groupedStatusId, statusId, available, holdable, inLibraryUseOnly from 
					  grouped_work_record_scope
					  inner join grouped_work_record_scope_details on scopeDetailsId = grouped_work_record_scope_details.id 
					  group by groupedWorkItemId, grouped_work_record_scope_details.groupedStatusId, grouped_work_record_scope_details.statusId, grouped_work_record_scope_details.available, grouped_work_record_scope_details.holdable, grouped_work_record_scope_details.inLibraryUseOnly) as src
					set dest.groupedStatusId = src.groupedStatusId, 
					  dest.statusId = src.statusId,
					  dest.available = src.available, 
					  dest.holdable = src.holdable, 
					  dest.inLibraryUseOnly = src.inLibraryUseOnly
					where dest.id = src.groupedWorkItemId',
				'ALTER TABLE grouped_work_record_scope_details DROP INDEX groupedStatusId',
				'ALTER TABLE grouped_work_record_scope_details DROP groupedStatusId, DROP statusId, DROP available, DROP holdable, DROP inLibraryUseOnly',
			]
		], //move_unchanged_scope_data_to_item
		'store_scope_details_in_concatenated_fields' => [
			'title' => 'Store scope details within concatenated fields',
			'description' => 'Update scoping to add scoped details within the item table rather than a separate table',
			'sql' => [
				'ALTER TABLE grouped_work_record_items ADD COLUMN locationOwnedScopes VARCHAR(500)',
				'ALTER TABLE grouped_work_record_items ADD COLUMN libraryOwnedScopes VARCHAR(500)',
				'ALTER TABLE grouped_work_record_items ADD COLUMN recordIncludedScopes VARCHAR(500)'
			]
		], //move_unchanged_scope_data_to_item
		//TODO: Do some form of conversion from the scoped data to scope information stored at item leve
		'remove_scope_tables' => [
			'title' => 'Remove Scope Tables',
			'description' => 'remove scope tables that are no longer used',
			'sql' => [
				'DROP TABLE grouped_work_record_scope',
				'DROP TABLE grouped_work_record_scope_details',
			]
		], //remove_scope_tables
		'remove_scope_triggers' => [
			'title' => 'Remove Scope Triggers',
			'description' => 'Remove Triggers related to old scope tables',
			'continueOnError' => true,
			'sql' => [
				'DROP TRIGGER after_grouped_work_record_items_delete',
				'DROP TRIGGER after_scope_delete',
			]
		], //remove_scope_triggers
		'record_suppression_no_marc' => [
			'title' => 'Setup ils record suppression for not having marc data',
			'description' => 'Setup ils record suppression for not having marc data',
			'sql' => [
				'ALTER TABLE ils_records DROP COLUMN suppressionReason',
				'ALTER TABLE ils_records CHANGE COLUMN suppressed suppressedNoMarcAvailable TINYINT(1)',
				'DROP TABLE ils_suppression_reasons'
			]
		], //record_suppression_no_marc
		'fix_ils_record_indexes' => [
			'title' => 'Fix ils record indexes',
			'description' => 'Drop ilsId index since it is not unique and we have source and ilsId indexed together',
			'sql' => [
				'ALTER TABLE ils_records DROP INDEX ilsId',
			]
		], //fix_ils_record_indexes
		'storeNYTLastUpdated' => [
			'title' => 'Store the date a NYT List was last modified',
			'description' => 'Store the date that a NYT List was last modified by NYT',
			'sql' => [
				'ALTER TABLE user_list ADD COLUMN nytListModified varchar(20) DEFAULT NULL',
			]
		], //storeNYTLastUpdated
		'fileUploadsThumb' => [
			'title' => 'Store the path to the thumbnail for uploaded PDF',
			'description' => 'Store the path to the thumbnail for uploaded PDF',
			'sql' => [
				'ALTER TABLE file_uploads ADD COLUMN thumbFullPath varchar(512) DEFAULT NULL',
			]
		], //fileUploadsThumb
		'pdfView' => [
			'title' => 'Store preferred PDF view for web builder cells',
			'description' => 'Store how an uploaded PDF should appear in a web builder cell',
			'sql' => [
				'ALTER TABLE web_builder_portal_cell ADD COLUMN pdfView varchar(12) DEFAULT NULL',
			]
		], //pdfView
		'increase_volumeId_length' => [
			'title' => 'Increase Volume Id length',
			'description' => 'Increase volume id length for polaris',
			'sql' => [
				'ALTER TABLE ils_volume_info CHANGE volumeId volumeId VARCHAR(100) NOT NULL'
			]
		], //increase_volumeId_length
		'remove_rbdigital' => [
			'title' => 'Remove RBdigital content',
			'description' => 'Remove RBdigital content form the database',
			'sql' => [
				'ALTER TABLE user drop column rbdigitalId',
				'ALTER TABLE user drop column rbdigitalLastAccountCheck',
				'ALTER TABLE user drop column rbdigitalPassword',
				'ALTER TABLE user drop column rbdigitalUsername',
				'ALTER TABLE library drop column rbdigitalScopeId',
				'ALTER TABLE location drop column rbdigitalScopeId',
				'DROP TABLE rbdigital_scopes',
				'DROP TABLE rbdigital_settings',
				'DROP TABLE rbdigital_export_log',
			]
		], //remove_rbdigital
		'additional_index_logging' => [
			'title' => 'Add additional information to ils index log',
			'description' => 'Add additional information for ILS index log',
			'sql' => [
				'ALTER TABLE ils_extract_log ADD COLUMN isFullUpdate TINYINT(1)',
				'ALTER TABLE ils_extract_log ADD COLUMN currentId VARCHAR(36)'
			]
		], //additional_index_logging
		'add_records_to_delete_for_sideloads' => [
			'title' => 'Add Records To Delete For SideLoads',
			'description' => 'Allow specifying a list of deleted records ids',
			'sql' => [
				'ALTER TABLE sideloads ADD COLUMN deletedRecordsIds MEDIUMTEXT'
			]
		], //add_records_to_delete_for_sideloads
		'local_urls' => [
			'title' => 'Setup local URLs',
			'description' => 'Setup a local urls table to track URLs for sideloads',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS grouped_work_record_item_url (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkItemId INT(11),
					scopeId INT(11),
					url VARCHAR(1000),
					UNIQUE (groupedWorkItemId, scopeId)
				) ENGINE INNODB'
			]
		], //add_footerLogoAlt
		'add_footerLogoAlt' => [
			'title' => 'Add footerLogoAlt',
			'description' => 'Store alt text for the footer logo image',
			'sql' => [
				'ALTER TABLE themes ADD COLUMN footerLogoAlt VARCHAR(255)'
			]
		], //rebuildThemes21_09
			'rebuildThemes21_09' => [
				'title' => 'Rebuild Themes for 21.09',
				'description' => 'Rebuild Themes for 21.09',
				'sql' => [
					"updateAllThemes"
				]
			],
		];
}

	function updateAllThemes(){
		$theme = new Theme();
		$theme->find();
		while ($theme->fetch()){
			$theme->generateCss(true);
		}
	}