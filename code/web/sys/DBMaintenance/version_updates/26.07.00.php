<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_00(): array {
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

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen
		'theme_full_width_content' => [
			'title' => 'Add fullWidthContent column',
			'description' => 'Add fullWidthContent column to themes table.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN fullWidthContent TINYINT(1) DEFAULT 0',
			]
		], //theme_full_width_content

		//other

	];
}
