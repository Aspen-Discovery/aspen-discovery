<?php

/** @noinspection PhpUnused */
function getUpdates25_09_00(): array {
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
		'25_09_add_performance_indexes' => [
			'title' => '25.09 Add Performance Indexes',
			'description' => '25.09 Add Performance Indexes',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE search ADD INDEX searchLookup(searchUrl(500),session_id,user_id)',
				'ALTER TABLE themes ADD INDEX nameById(id,displayName)',
				'ALTER TABLE library ADD INDEX isDefault(isDefault)',
				'ALTER TABLE library ADD INDEX subdomainUrl(subdomain, baseUrl)'
			]
		], //25_09_add_performance_indexes
		'remove_quick_searches' => [
			'title' => 'Remove Quick Searches',
			'description' => 'Remove Unused Quick Search Tables',
			'continueOnError' => false,
			'sql' => [
				'DROP TABLE aspen_lida_quick_search_setting',
				'DROP TABLE aspen_lida_quick_searches'
			]
		], //remove_quick_searches
		'remove_rbdigital_tables' => [
			'title' => 'Remove RBdigital tables',
			'description' => 'Remove Unused RBdigital Tables',
			'continueOnError' => false,
			'sql' => [
				'DROP TABLE rbdigital_magazine_issue',
				'DROP TABLE rbdigital_magazine',
				'DROP TABLE rbdigital_magazine_usage',
				'DROP TABLE rbdigital_title',
				'DROP TABLE rbdigital_record_usage',
				'DROP TABLE user_rbdigital_usage',
				"DELETE FROM modules where name = 'RBdigital'",
				"DELETE FROM role_permissions where permissionId = (SELECT id from permissions where name = 'Administer RBdigital')",
				"DELETE FROM permissions where name = 'Administer RBdigital'",
			]
		], //remove_rbdigital_tables
		'remove_redwood_tables' => [
			'title' => 'Remove Redwood tables',
			'description' => 'Remove Unused Redwood Table',
			'continueOnError' => false,
			'sql' => [
				'DROP TABLE redwood_user_contribution'
			]
		], //remove_redwood_tables
		'add_grouped_work_display_format_display' => [
			'title' => 'Grouped Display Settings add Format Display Option',
			'description' => 'Grouped Display Settings add Format Display Option',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE grouped_work_display_settings ADD COLUMN formatDisplayStyle INT DEFAULT 1'
			]
		], //add_grouped_work_display_format_display
		'add_self_check_completion_message' => [
			'title' => 'Add Self Check Completion Message',
			'description' => 'Add configuration table for self check completion messages',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE self_check_completion_message (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					formats VARCHAR(500),
					owningLocations VARCHAR(500),
					checkoutLocations VARCHAR(500)
				) ENGINE INNODB',
			]
		], //add_self_check_completion_message

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS
		'add_self_reg_note_setting' => [
			'title' => 'Add Self Registration Note Setting',
			'description' => 'Add setting to control whether self-registration note is added to Sierra patron records.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE self_registration_form_sierra ADD COLUMN addSelfRegNote TINYINT DEFAULT 1'
			],
		], // add_self_reg_note_setting
		'increase_browse_category_label_length' => [
			'title' => 'Increase Browse Category Label Length',
			'description' => 'Increase the allowed length for browse category labels from 50 to 100 characters.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE browse_category MODIFY label VARCHAR(100) NOT NULL'
			],
		], // increase_browse_category_label_length

		//alexander - Open Fifth
		'increase_location_display_name_allowed_length' => [
			'title' => 'Increase Location Display Name Allowed Length',
			'description' => 'Increase the allowed length for the location display name',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE location MODIFY displayName VARCHAR(100) NOT NULL'
			],
		], // increase_location_display_name_allowed_length
		'add_title_to_user_work_review' => [
			'title' => 'Add Title To user Work Review',
			'description' => 'Add title of reviewed work to table',
			'sql' => [
				"ALTER TABLE user_work_review ADD COLUMN title VARCHAR(512) DEFAULT ''",
			]
		], //add_title_to_user_work_review

		//chloe - Open Fifth


		//Jacob - Open Fifth

		//Pedro - Open Fifth


		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

		//Talpa Search

		// Brendan Lawlor
		'addLibraryEmailToCustomForm' => [
			 'title' => 'Add library email to custom form',
			 'description' => 'Add library email to custom form',
			 'continueOnError' => false,
			 'sql' => [
				 'ALTER TABLE library_web_builder_custom_form ADD COLUMN emailResultsTo varchar(100) DEFAULT ""'
			 ]
		 ], //addLibraryEmailToCustomForm
		
	];
}
