<?php
/** @noinspection PhpUnused */
function getUpdates22_06_01(): array {
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'aspen_site_timezone' => [
			'title' => 'Add timezone to Aspen site',
			'description' => 'Add timezone to Aspen site',
			'sql' => [
				'ALTER TABLE aspen_sites ADD COLUMN timezone TINYINT(1) DEFAULT 0',
			],
		],
		//aspen_site_timezone
		'ils_record_suppression' => [
			'title' => 'ILS records Suppression Information',
			'description' => 'Add additional information for why ILS records are suppressed',
			'sql' => [
				'ALTER TABLE ils_records ADD COLUMN suppressed TINYINT(1) DEFAULT 0',
				'ALTER TABLE ils_records ADD COLUMN suppressionNotes TEXT',
			],
		],
		//ils_record_suppression
	];
}
