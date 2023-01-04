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
		'users_to_tasks' => [
			'title' => 'Development - Link Users To Tasks',
			'description' => 'Development - Link Users To Tasks',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS development_task_developer_link (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11), 
					taskId INT(11), 
					UNIQUE INDEX (userId, taskId)
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS development_task_qa_link (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11), 
					taskId INT(11), 
					UNIQUE INDEX (userId, taskId)
				) ENGINE INNODB',
			],
		],
		//users_to_tasks
		'ptype_length' => [
			'title' => 'PType length',
			'description' => 'Increase the length of the PType column',
			'sql' => [
				"ALTER TABLE ptype CHANGE COLUMN pType pType VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL",
			],
		],
		//ptype_length
		'record_parents_index' => [
			'title' => 'Record Parents Index',
			'description' => 'Add an index to record_parents to improve index performance',
			'sql' => [
				"alter table record_parents add index parentRecordId(parentRecordId)",
			],
		],
		//record_parents_index
		'object_history_action_type' => [
			'title' => 'Object History Action Type',
			'description' => 'Add an action type to object history',
			'sql' => [
				"alter table object_history add COLUMN actionType TINYINT DEFAULT 2",
				"alter table object_history add INDEX actionType(actionType)",
			],
		],
		//object_history_action_type
		'add_index_to_ils_volume_info' => [
			'title' => 'Add Index to ILS Volume Info',
			'description' => 'Add an index to ils_volume_info to improve search result performance',
			'sql' => [
				"alter table ils_volume_info add index recordId(recordId)",
			],
		],
		//add_index_to_ils_volume_info

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
		'add_account_alerts_notification_settings' => [
			'title' => 'Add account alert notification type to settings',
			'description' => 'Adds account alert notifications option to notification settings',
			'sql' => [
				'ALTER TABLE aspen_lida_notification_setting ADD COLUMN notifyAccount TINYINT(1) DEFAULT 0',
			],
		],
		//add_account_alerts_notification_settings

		//kodi
		'user_browse_add_home' => [
			'title' => 'Add New Browse Categories to Home',
			'description' => 'Store user selection for adding browse categories to home page',
			'sql' => [
				'ALTER TABLE user ADD COLUMN browseAddToHome TINYINT(1) DEFAULT 1',
			],
		],
		//user_browse_add_home
		'account_linking_setting_by_ptype' => [
			'title' => 'Set account linking setting by PType',
			'description' => 'Set account linking setting by PType',
			'sql' => [
				"ALTER TABLE ptype ADD COLUMN accountLinkingSetting TINYINT(1) DEFAULT 0",
			],
		],
		//account_linking_setting_by_ptype
		'records_to_include_updates' => [
			'title' => 'Updates to records to include',
			'description' => 'Migrates functionality of records owned into records to include and adds some new fields for exclusion rules',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library_records_to_include ADD COLUMN markRecordsAsOwned TINYINT default 0",
				"ALTER TABLE library_records_to_include ADD COLUMN iTypesToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN audiencesToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN formatsToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN shelfLocation VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN shelfLocationsToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN collectionCode VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include ADD COLUMN collectionCodesToExclude VARCHAR(100) NOT NULL DEFAULT ''",

				"ALTER TABLE location_records_to_include ADD COLUMN markRecordsAsOwned TINYINT default 0",
				"ALTER TABLE location_records_to_include ADD COLUMN iTypesToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN audiencesToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN formatsToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN shelfLocation VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN shelfLocationsToExclude VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN collectionCode VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include ADD COLUMN collectionCodesToExclude VARCHAR(100) NOT NULL DEFAULT ''",
			]
		],
		//records_to_include_updates
		'migrate_records_owned' => [
			'title' => 'Migrate Records Owned to Records to Include',
			'description' => 'Migrates values in Records Owned table to Records to Include table',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO library_records_to_include(libraryId, indexingProfileId, markRecordsAsOwned, location, locationsToExclude, sublocation, sublocationsToExclude, weight) 
					SELECT libraryId, indexingProfileId, 1, location, locationsToExclude, sublocation, sublocationsToExclude, 0 from library_records_owned",
				"INSERT INTO location_records_to_include(locationId, indexingProfileId, markRecordsAsOwned, location, locationsToExclude, sublocation, sublocationsToExclude, weight) 
					SELECT locationId, indexingProfileId, 1, location, locationsToExclude, sublocation, sublocationsToExclude, 0 from location_records_owned",
				"DROP TABLE library_records_owned",
				"DROP TABLE location_records_owned",

			]
		],
		//migrate_records_owned
		//other
	];
}