<?php
/** @noinspection PhpUnused */
function getUpdates22_01_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'sierra_public_note_export' => [
			'title' => 'Add export of public note from Sierra',
			'description' => 'Add export of public note from Sierra',
			'sql' => [
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN itemPublicNoteExportSubfield VARCHAR(1) DEFAULT ''",
			]
		], //sierra_public_note_export
		'greenhouse_add_ils'=> [
			'title' => 'Greenhouse - Add ILS',
			'description' => 'Track the active ILS for a site within the greenouse',
			'sql' => [
				'ALTER TABLE aspen_sites ADD COLUMN ils INT'
			]
		], //greenhouse_add_ils
		'website_pages_deletionReason' => [
			'title' => 'Add deletion reason to website pages',
			'description' => 'Website Pages - Deletion Reason',
			'sql' => [
				"ALTER TABLE website_pages ADD COLUMN deleteReason VARCHAR(255) DEFAULT ''"
			]
		], //website_pages_deletionReason
	];
}