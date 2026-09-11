<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_12_00(): array {
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
		'header_background_image_height' => [
			'title' => 'Header Background Image Height',
			'description' => 'Add headerBackgroundImageHeight to themes so the header can have an explicit height independent of the Logo image size.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN headerBackgroundImageHeight VARCHAR(10) DEFAULT NULL'
			]
		], //header_background_image_height
		'header_background_image_adapt_height' => [
			'title' => 'Header Background Image Adapt Height',
			'description' => 'Add headerBackgroundImageAdaptHeight to themes so the header can size itself to the background image\'s own proportions instead of a fixed height.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN headerBackgroundImageAdaptHeight TINYINT(1) DEFAULT 0'
			]
		], //header_background_image_adapt_height

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}
