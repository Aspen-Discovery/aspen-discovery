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
		'rename_prospector_to_innreach' => [
			'title' => 'Rename Prospector Integration to INN-Reach',
			'description' => 'Rename Prospector Integration to INN-Reach',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library RENAME COLUMN repeatInProspector TO repeatInInnReach',
				'ALTER TABLE location RENAME COLUMN repeatInProspector TO repeatInInnReach',
				'ALTER TABLE library DROP COLUMN prospectorCode',
				'ALTER TABLE library RENAME COLUMN showProspectorResultsAtEndOfSearch TO showInnReachResultsAtEndOfSearch',
				'ALTER TABLE library RENAME COLUMN enableProspectorIntegration TO enableInnReachIntegration',
			],
		], //rename_prospector_to_innreach

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

		//other
	];
}