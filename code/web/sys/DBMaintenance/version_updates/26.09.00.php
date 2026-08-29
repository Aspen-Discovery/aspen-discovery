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
		'add_hoopla_products_to_update' => [
			'title' => 'Add Records To Reindex to Hoopla Settings',
			'description' => 'Add a queue of Hoopla record IDs to refresh and reindex.',
			'sql' => [
				'ALTER TABLE hoopla_settings ADD COLUMN productsToUpdate MEDIUMTEXT NULL',
			],
		],

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
