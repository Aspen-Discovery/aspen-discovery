<?php

function getUpdates25_08_00(): array {
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

		//mark - Grove

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS

		// Laura Escamilla - ByWater Solutions

		//alexander - Open Fifth

		//chloe - Open Fifth


		//Jacob - Open Fifth

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

		//Talpa Search
		'addSendCatalogItemsToTalpaOnSave' => [
			'title' => 'Add Send Catalog Items to Talpa Search setting',
			'description' => 'Add a new setting to allow a one-time, immediate sharing of holdings with Talpa Search.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE talpa_settings ADD COLUMN sendCatalogItemsToTalpaOnSave TINYINT(1) UNSIGNED DEFAULT 0",
			],
		], //addSendCatalogItemsToTalpaOnSave
	];
}
