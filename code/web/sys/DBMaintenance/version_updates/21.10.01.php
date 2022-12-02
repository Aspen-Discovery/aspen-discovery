<?php
/** @noinspection PhpUnused */
function getUpdates21_10_01(): array {
	return [
		'greenhouse_appAccess' => [
			'title' => 'Add app access level options',
			'description' => 'Create app access level for the greenhouse',
			'sql' => [
				'ALTER TABLE aspen_sites ADD COLUMN appAccess TINYINT(1) DEFAULT 0',
			],
		],
		//greenhouse_appAccess
	];
}