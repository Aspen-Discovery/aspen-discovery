<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
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
		'remove_overdrive_products_to_update' => [
			'title' => 'Remove OverDrive Products To Reindex column',
			'description' => 'Records to reindex are now stored in record_identifiers_to_reload',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE overdrive_settings DROP COLUMN productsToUpdate',
			],
		], //remove_overdrive_products_to_update

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