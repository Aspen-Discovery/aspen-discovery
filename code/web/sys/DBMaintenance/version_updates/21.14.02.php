<?php
/** @noinspection PhpUnused */
function getUpdates21_14_02() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'overDriveDisableRequestLogging' => [
			'title' => 'External Requests - Allow disabling during export',
			'description' => 'Allow disabling external request logging during OverDrive export',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN enableRequestLogging TINYINT(1) DEFAULT 0',
			]
		], //overDriveDisableRequestLogging

	];
}