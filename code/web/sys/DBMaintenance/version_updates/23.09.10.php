<?php
/** @noinspection PhpUnused */
function getUpdates23_09_10(): array {
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

		'remove_suppress_itemless_bibs_setting' => [
			'title' => 'Indexing Profiles - Remove suppress itemless bibs setting',
			'description' => 'Indexing Profiles - Remove suppress itemless bibs setting',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE indexing_profiles DROP COLUMN suppressItemlessBibs',
				'ALTER TABLE sideloads DROP COLUMN suppressItemlessBibs'
			]
		], //remove_suppress_itemless_bibs_setting
		'callNumberPrestamp2' => [
			'title' => 'Call Number Prestamp 2',
			'description' => 'Add the ability to have 2 item fields used as call number prestamps',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN callNumberPrestamp2 CHAR(1)',
				'ALTER TABLE sierra_export_field_mapping ADD COLUMN callNumberPrestamp2ExportSubfield CHAR(1)',
			]
		], //callNumberPrestamp2
		'exportingUrlDescription' => [
			'title' => 'URL Description',
			'description' => 'Add the ability to export and display URL Descriptions',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN itemUrlDescription CHAR(1)',
				'ALTER TABLE sierra_export_field_mapping DROP COLUMN urlExportFieldTag',
			]
		], //exportingUrlDescription
	];
}