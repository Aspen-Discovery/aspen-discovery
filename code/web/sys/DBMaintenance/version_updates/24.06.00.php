<?php

function getUpdates24_06_00(): array {
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

		//kodi - ByWater

		//other

		//alexander - PTFS Europe
		'summon_ip_addresses' => [
			'title' => 'Summon IP address configuration',
			'description' => 'Allow configuration of which IP addresses should automatically authenticate with Summon',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE ip_lookup ADD COLUMN authenticatedForSummon TINYINT DEFAULT 0',
			]
		], //summon authentication
		'explore_more_section_control' => [
			'title' => 'Explore More Section Control',
			'description' => 'Allow control over whether the Explore More Section is displayed',
			'sql' => [
				"ALTER TABLE layout_settings ADD COLUMN showExploreMoreOptions TINYINT DEFAULT '1'",
			]
		],//control_whether_the_explore_more_box_is_displayed


	];
}