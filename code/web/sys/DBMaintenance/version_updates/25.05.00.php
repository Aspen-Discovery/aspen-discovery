<?php

function getUpdates25_05_00(): array {
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
		'system_variables_add_lida_github_repository' => [
			'title' => 'system_variables_add_lida_github_repository',
			'description' => 'Add a field to store the github repository for LiDA within System Variables to load release notes from',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE system_variables add column lidaGitHubRepository VARCHAR(255) DEFAULT 'https://github.com/Aspen-Discovery/aspen-lida'",
			]
		], //system_variables_add_lida_github_repository

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'custom_form_field_enums_to_text' => [
			'title' => 'Increase Custom Form Field EnumValues Size',
			'description' => 'Changes the enumValues column in web_builder_custom_form_field from VARCHAR(255) to TEXT to allow for longer select lists.',
			'sql' => [
				"ALTER TABLE web_builder_custom_form_field MODIFY COLUMN enumValues TEXT DEFAULT NULL",
			]
		], //custom_form_field_enums_to_text
		'ip_lookup_ipv6_support' => [
			'title' => 'Add Support for IPv6 Addresses',
			'description' => 'Add support for IPv6 addresses in ip_lookup table.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE ip_lookup MODIFY startIpVal VARCHAR(255) NULL COMMENT 'Numeric value for IPv4 or encoded string for IPv6'",
				"ALTER TABLE ip_lookup MODIFY endIpVal VARCHAR(255) NULL COMMENT 'Numeric value for IPv4 or encoded string for IPv6'"
			],
		], //ip_lookup_ipv6_support

		//alexander - Open Fifth
		'allow_filtering_of_linked_users_in_checkouts' => [
			'title' => 'Allow Filtering of Linked Users in Checkouts',
			'description' => 'Allow libraries the option of allowing users to filter their checkouts by linked user',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowFilteringOfLinkedAccountsInCheckouts TINYINT(1) DEFAULT 0',
			],
		], //allow_filtering_of_linked_users_in_checkouts
		'allow_selecting_checkouts_to_export' => [
			'title' => 'Allow Selecting Checkouts to Export',
			'description' => 'Allow libraries the option of allowing users to export only selected checkouts',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowSelectingCheckoutsToExport TINYINT(1) DEFAULT 0'
			],
		], //allow_selecting_checkouts_to_export
		'add_ability_to_highlight_campaigns_in_account_area' => [
			'title' => 'Add Ability to Highlight Campaigns In Account Area',
			'description' => 'Allow libraries to choose whether to display a block highlighting campaigns on the account page',
			'sql' => [
				"ALTER TABLE library ADD COLUMN highlightCommunityEngagement TINYINT(1) DEFAULT 0",
			],
		],

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}