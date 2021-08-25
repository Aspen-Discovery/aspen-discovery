<?php
/** @noinspection PhpUnused */
function getUpdates21_11_00() : array
{
	return [
		'showCardExpirationDate' => [
			'title' => 'Add app access level options',
			'description' => 'Create app access level for the greenhouse',
			'sql' => [
				'ALTER TABLE library ADD COLUMN showCardExpirationDate TINYINT(1) DEFAULT 1'
			]
		], //showCardExpirationDate
		'materialsRequestStaffComments' => [
			'title' => 'Allow adding staff comments to Materials Requests',
			'description' => 'Allow adding staff comments to Materials Requests',
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN staffComments TEXT'
			]
		],
		'additionalTranslationTermInfo' => [
			'title' => 'Add categorizing of translation termsm',
			'description' =>  'Add additional information to translation terms to be able categorize them',
			'sql' => [
				'ALTER TABLE translation_terms ADD COLUMN isPublicFacing TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN isAdminFacing TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN isMetadata TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN isAdminEnteredData TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN lastUpdate INT(11) DEFAULT 0',
			]
		],
		'addGreenhouseUrl' => [
			'title' => 'Add Greenhouse URL',
			'description' => 'Add a link to the Greenhouse',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN greenhouseUrl VARCHAR(128)'
			]
		]
	];
}