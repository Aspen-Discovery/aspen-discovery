<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_10_00(): array {
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
		'header_logo_alignment' => [
			'title' => 'Header Logo Alignment',
			'description' => 'Add headerLogoAlignment to themes so the Logo can be aligned left, center, or right within the header.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE themes ADD COLUMN headerLogoAlignment VARCHAR(10) DEFAULT 'left'"
			]
		], //header_logo_alignment
		'footer_background_image' => [
			'title' => 'Footer Background Image',
			'description' => 'Add footerBackgroundImage, footerBackgroundImageSize, and footerBackgroundImageRepeat to themes so the footer can have a background image, matching the Header Background Image options.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN footerBackgroundImage VARCHAR(100) DEFAULT NULL',
				"ALTER TABLE themes ADD COLUMN footerBackgroundImageSize VARCHAR(10) DEFAULT 'cover'",
				"ALTER TABLE themes ADD COLUMN footerBackgroundImageRepeat VARCHAR(10) DEFAULT 'no-repeat'"
			]
		], //footer_background_image
		'hide_aspen_version' => [
			'title' => 'Hide Aspen Version',
			'description' => 'Add hideAspenVersion to themes so libraries can hide the Aspen Discovery version number normally shown in the footer.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE themes ADD COLUMN hideAspenVersion TINYINT(1) DEFAULT 0'
			]
		], //hide_aspen_version

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}