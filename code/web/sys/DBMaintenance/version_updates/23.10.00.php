<?php
/** @noinspection PhpUnused */
function getUpdates23_10_00(): array {
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

		//mark - ByWater

		//kirstien - ByWater
		'add_always_display_renew_count' => [
			'title' => 'Add option to always show renewal count',
			'description' => 'Add option in Library Systems to always show the renewal count for a checkout',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD alwaysDisplayRenewalCount TINYINT(1) default 0',
			]
		], //add_always_display_renew_count

		//kodi - ByWater

	];
}