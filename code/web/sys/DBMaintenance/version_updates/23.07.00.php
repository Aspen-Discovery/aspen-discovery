<?php
/** @noinspection PhpUnused */
function getUpdates23_07_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/
		//mark
		'rename_prospector_to_innreach2' => [
			'title' => 'Rename Prospector Integration to INN-Reach',
			'description' => 'Rename Prospector Integration to INN-Reach',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library CHANGE COLUMN repeatInProspector repeatInInnReach TINYINT DEFAULT 0',
				'ALTER TABLE library DROP COLUMN prospectorCode',
				'ALTER TABLE library CHANGE COLUMN showProspectorResultsAtEndOfSearch showInnReachResultsAtEndOfSearch TINYINT DEFAULT 1',
				'ALTER TABLE library CHANGE COLUMN enableProspectorIntegration enableInnReachIntegration TINYINT(4) NOT NULL DEFAULT 0',
			],
		], //rename_prospector_to_innreach2

		//kirstien

		//kodi
		'add_disallow_third_party_covers' => [
			'title' => 'Add option to disallow third party cover images for certain works',
			'description' => 'Add option to disallow third party cover images for certain works',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE bookcover_info ADD COLUMN disallowThirdPartyCover TINYINT(1) DEFAULT 0',
			],
		], //add_disallow_third_party_covers
		'theme_cover_default_image' => [
			'title' => 'Theme - Set default image for cover images',
			'description' => 'Update theme table to have default values for the default cover image',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN defaultCover VARCHAR(100) default ''",
			],
		], //theme_cover_default_image

		//other
	];
}