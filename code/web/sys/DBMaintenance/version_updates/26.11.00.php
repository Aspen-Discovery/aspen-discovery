<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_10_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas
		'storage_settings_s3_columns' => [
			'title' => 'Add S3 columns to storage settings table',
			'description' => 'Extend the storage settings table with S3 connection fields and add s3 as a supported driver.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE storage_settings MODIFY COLUMN driver ENUM('local','s3') NOT NULL DEFAULT 'local'",
				"ALTER TABLE storage_settings ADD COLUMN bucket VARCHAR(255) NOT NULL DEFAULT ''",
				"ALTER TABLE storage_settings ADD COLUMN accessKeyId VARCHAR(255) NOT NULL DEFAULT ''",
				"ALTER TABLE storage_settings ADD COLUMN accessKeySecret VARCHAR(255) NOT NULL DEFAULT ''",
				"ALTER TABLE storage_settings ADD COLUMN region VARCHAR(64) NOT NULL DEFAULT 'us-east-1'",
				"ALTER TABLE storage_settings ADD COLUMN endpoint VARCHAR(512) NOT NULL DEFAULT ''",
				"ALTER TABLE storage_settings ADD COLUMN baseUrl VARCHAR(512) NOT NULL DEFAULT ''",
			],
		], //storage_settings_s3_columns

		'storage_settings_verified_status' => [
			'title' => 'Track S3 public access verification status',
			'description' => 'Add verifiedStatus/verifiedMessage to storage_settings so the admin UI can show whether the configured public base URL has actually been confirmed reachable, instead of assuming it works.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE storage_settings ADD COLUMN verifiedStatus ENUM('unverified','verified','failed') NOT NULL DEFAULT 'unverified'",
				"ALTER TABLE storage_settings ADD COLUMN verifiedMessage VARCHAR(255) NULL DEFAULT NULL",
			],
		], //storage_settings_verified_status

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}