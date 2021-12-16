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
	];
}