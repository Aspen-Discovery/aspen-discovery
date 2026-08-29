<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_09_00(): array {
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
		'hoopla_store_raw_response_length' => [
			'title' => 'Store Raw Response Length for Hoopla',
			'description' => 'Store Raw Response Length for Hoopla and index for performance',
			'sql' => [
				'ALTER TABLE hoopla_export ADD COLUMN rawResponseLength INT AS (UNCOMPRESSED_LENGTH(rawResponse)) STORED',
				'ALTER TABLE hoopla_export ADD INDEX responseIndex(hooplaId, rawChecksum, rawResponseLength)',
			],
		], //hoopla_store_raw_response_length

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}
