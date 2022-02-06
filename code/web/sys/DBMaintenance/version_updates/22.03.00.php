<?php
/** @noinspection PhpUnused */
function getUpdates22_03_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'evergreen_folio_modules' => [
			'title' => 'Add modules for Evergreen and FOLIO',
			'description' => 'Add modules for Evergreen and FOLIO',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('Evergreen', 'grouped_works', 'evergreen_export','/sys/ILS/IlsExtractLogEntry.php', 'IlsExtractLogEntry')",
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('FOLIO', 'grouped_works', 'folio_export','/sys/ILS/IlsExtractLogEntry.php', 'IlsExtractLogEntry')",
			]
		], //evergreen_folio_modules
	];
}
