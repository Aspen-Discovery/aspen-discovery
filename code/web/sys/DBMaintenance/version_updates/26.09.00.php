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
		'publisher_keyword_exclusion' => [
			'title' => 'Publisher Keyword Exclusion',
			'description' => 'Add a variable in indexing profiles to determine if publisher info is excluded from the keyword index or not.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE `indexing_profiles` ADD COLUMN `excludePublisherFromKeywordIndex` TINYINT(1) NOT NULL DEFAULT 0;'
			]
		]

		//yanjun

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
