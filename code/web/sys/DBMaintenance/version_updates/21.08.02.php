<?php
/** @noinspection PhpUnused */
function getUpdates21_08_02(): array {
	return [
		'storeRecordDetailsInDatabase' => [
			'title' => 'Add Store Record Details In Database to System Variables',
			'description' => 'Allows disabling database updates for performance',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN storeRecordDetailsInDatabase TINYINT(1) DEFAULT 1',
			],
		],
		//storeRecordDetailsInDatabase
	];
}