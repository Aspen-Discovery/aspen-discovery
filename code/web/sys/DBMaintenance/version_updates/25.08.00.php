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
		'library_local_ill_email' => [
			'title' => 'Library - Local ILL Email',
			'description' => 'Add Local ILL Email to Library Settings',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN localIllEmail varchar(255) default ''"
			]
		], //library_local_ill_email
		'materials_request_add_source' => [
			'title' => 'Materials Request - Add Source',
			'description' => 'Add Source to Materials Request to differentiate between Local ILL and standard requests',
			'sql' => [
				"ALTER TABLE materials_request ADD COLUMN source TINYINT DEFAULT 1"
			]
		], //materials_request_add_source

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove
		'library_you_might_also_like' => [
			'title' => 'Library You Might Also Like Setting',
			'description' => 'Add a setting for libraries for the "You Might Also Like" feature to disable or enable with restrictions.',
			'sql' => [
				"ALTER TABLE library ADD COLUMN showYouMightAlsoLike TINYINT(1) DEFAULT 1;",
				"UPDATE library SET showYouMightAlsoLike =0 WHERE showWhileYouWait=0;"
			],
		], //library_you_might_also_like
		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'add_updating_contact_info_from_ils' => [
			'title' => 'Add the Option to "Automatically Update Contact Information from the ILS"',
			'description' => 'Add the Option to "Automatically Update Contact Information from the ILS" ',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE location ADD COLUMN allowUpdatingContactInfoFromILS TINYINT(1) DEFAULT 0",
			],
		], // add_updating_contact_info_from_ils
		'add_max_hold_cancellation_date_field' => [
			'title' => 'Add Max Hold Cancellation Date Field',
			'description' => 'Add the Max Hold Cancellation Date field for when hold cancellations are enabled.',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS maxHoldCancellationDate int(11) DEFAULT -1 AFTER defaultNotNeededAfterDays;'
			]
		], //add_max_hold_cancellation_date_field

		// Laura Escamilla - ByWater Solutions

		//alexander - Open Fifth
		'control_display_of_user_dropdown_in_community_engagement_admin_view' => [
			'title' => 'Control User Select Type in Admin View',
			'description' => 'Add options for how to select users in the admin view section',
			'sql' => [
				"ALTER TABLE library ADD COLUMN communityEngagementAdminUserSelect VARCHAR(20) DEFAULT 'dropdown'",
			],
		], //control_display_of_user_dropdown_in_community_engagement_admin_view
		'display_only_users_from_current_library_in_user_search_admin_view' => [
			'title' => 'Display Only Users From Current Library in User Search Admin View',
			'description' => 'Add option to display users from all libraries or only the current library location when searching by user in Admin View',
			'sql' => [
				"ALTER TABLE library ADD COLUMN displayOnlyUsersForLocationInUserAdmin TINYINT(1) DEFAULT 0",
			],
		], //display_only_users_from_current_library_in_user_search_admin_view
		'allow_admin_to_enroll_users_via_admin_view' => [
			'title' => 'Allow Admin To Enroll Users Via Admin View',
			'description' => 'Add control over whether admin can enroll users via the admin view page',
			'sql' => [
				"ALTER TABLE library ADD COLUMN allowAdminToEnrollUsersInAdminView TINYINT(1) DEFAULT 0",
			],
		], //allow_admin_to_enroll_users_via_admin_view

		//chloe - Open Fifth


		//Jacob - Open Fifth

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

		//Talpa Search
		
	];
}
