<?php

function getUpdates24_02_10(): array {
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

		//mark - ByWater
		'library_payment_history' => [
			'title' => 'Library - Payment History',
			'description' => 'Add options related to library payment history',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library add column showPaymentHistory TINYINT DEFAULT 0'
			]
		], //library_show_payment_history
		'user_payment_lines' => [
			'title' => 'User Payment Lines',
			'description' => 'Add User Payment Lines Table',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE user_payment_lines (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					paymentId INT(11) NOT NULL,
					description TEXT,
					amountPaid FLOAT
				)'
			]
		], //user_payment_lines
		'library_deletePaymentHistoryOlderThan' => [
			'title' => 'Library delete payment history older than',
			'description' => 'Add setting to delete payment history older than a specific date',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN deletePaymentHistoryOlderThan INT DEFAULT 0'
			]
		], //library_deletePaymentHistoryOlderThan
		'overdrive_index_cross_ref_id' => [
			'title' => 'OverDrive Index Cross Ref ID',
			'description' => 'OverDrive Index Cross Ref ID',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE overdrive_api_products add index crossRefId(crossRefId)'
			]
		], //overdrive_index_cross_ref_id
		'index_ils_barcode' => [
			'title' => 'Add ILS Barcode index',
			'description' => 'Add ILS Barcode index to user table',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user add index ils_barcode(ils_barcode)'
			]
		], //index_ils_barcode
		'index_common_timestamp_columns' => [
			'title' => 'Index Common Timestamp Columns',
			'description' => 'Add Indexes to some table that store timestamps',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE axis360_export_log add index startTime(startTime)',
				'ALTER TABLE cached_values add index expirationTime(expirationTime)',
				'ALTER TABLE cloud_library_export_log add index startTime(startTime)',
				'ALTER TABLE cron_log add index startTime(startTime)',
				'ALTER TABLE cron_process_log add index startTime(startTime)',
				'ALTER TABLE errors add index timestamp(timestamp)',
				'ALTER TABLE events_indexing_log add index startTime(startTime)',
				'ALTER TABLE hoopla_export_log add index startTime(startTime)',
				'ALTER TABLE ils_extract_log add index indexingProfileTime(indexingProfile, startTime)',
				'ALTER TABLE list_indexing_log add index startTime(startTime)',
				'ALTER TABLE overdrive_extract_log add index startTime(startTime)',
				'ALTER TABLE palace_project_export_log add index startTime(startTime)',
				'ALTER TABLE sideload_log add index startTime(startTime)',
				'ALTER TABLE user_list add index dateUpdated(dateUpdated)',
				'ALTER TABLE website_index_log add index startTime(startTime)',
			]
		], //index_common_timestamp_columns

		//kirstien - ByWater

		//kodi - ByWater

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub

	];
}