<?php

function getUpdates24_10_00(): array {
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
		'additional_administration_locations' => [
			'title' => 'Additional Administration Locations',
			'description' => 'Add a table to store additional locations that a user can administer',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS `user_administration_locations` (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					locationId INT(11) NOT NULL,
					UNIQUE INDEX (userId,locationId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;'
			]
		], //additional_administration_locations
		'add_place_holds_for_materials_request_permission' => [
			'title' => 'Add Place Holds For Materials Request Permission',
			'description' => 'Add Place Holds For Materials Request Permission',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Materials Requests', 'Place Holds For Materials Requests', '', 25, 'Allows users to place holds for users that have active Materials Requests once titles are added to the catalog.')",
			]
		], //add_place_holds_for_materials_request_permission
		'add_hold_options_for_materials_request_statuses' => [
			'title' => 'Add Hold Options for Materials Request Statuses',
			'description' => 'Add new options to control what statuses should be used when placing holds for materials requests',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE materials_request_status ADD COLUMN checkForHolds TINYINT(1) DEFAULT 0",
				"ALTER TABLE materials_request_status ADD COLUMN holdPlacedSuccessfully TINYINT(1) DEFAULT 0",
				"ALTER TABLE materials_request_status ADD COLUMN holdFailed TINYINT(1) DEFAULT 0",
			]
		], //add_hold_options_for_materials_request_statuses
		'add_materials_request_format_mapping' => [
			'title' => 'Add Materials Request Format Mapping',
			'description' => 'Add new a new table to define mapping between Aspen Materials Request Formats and Aspen Catalog Formats',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS materials_request_format_mapping (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					catalogFormat VARCHAR(255) NOT NULL,
					materialsRequestFormatId INT(11) NOT NULL ,
					UNIQUE (libraryId, catalogFormat)
				) ENGINE INNODB"
			]
		], //add_materials_request_format_mapping
		'materials_request_ready_for_holds' => [
			'title' => 'Materials Request Ready For Holds',
			'description' => 'Add a new flag to materials requests to indicate they are ready for holds to be placed',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN readyForHolds TINYINT(1) DEFAULT 0'
			]
		], //materials_request_ready_for_holds
		'materials_request_hold_candidates' => [
			'title' => 'Add Materials Request Format Mapping',
			'description' => 'Add new a new table to define mapping between Aspen Materials Request Formats and Aspen Catalog Formats',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS materials_request_hold_candidate (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					requestId INT(11) NOT NULL,
					source VARCHAR(255) NOT NULL,
					sourceId VARCHAR(255) NOT NULL,
					UNIQUE (requestId, source, sourceId)
				) ENGINE INNODB"
			]
		], //materials_request_hold_candidates
		'materials_request_selected_hold_candidate' => [
			'title' => 'Materials Request - Selected Hold Candidate',
			'description' => 'Add new a column to store the selected hold candidate for a request',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN selectedHoldCandidateId INT(11) DEFAULT 0'
			]
		], //materials_request_selected_hold_candidate
		'materials_request_hold_failure_message' => [
			'title' => 'Materials Request - Hold Failure Message',
			'description' => 'Add new a column to failure message when placing a hold',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN holdFailureMessage TEXT'
			]
		], //materials_request_hold_failure_message
		'update_default_request_statuses' => [
			'title' => 'Update default material request statuses',
			'description' => 'Add new material request statuses',
			'continueOnError' => false,
			'sql' => [
				"UPDATE materials_request_status SET isOpen = 1, checkForHolds = 1 where description='Item purchased' and libraryId = -1",
				"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen, holdPlacedSuccessfully, libraryId) 
					VALUES ('Hold Placed', 1, '{title} has been received by the library and you have been added to the hold queue. 

Thank you for your purchase suggestion!', 0, 1, -1)",
				"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen, holdFailed, libraryId) 
					VALUES ('Hold Failed', 1, '{title} has been received by the library, however we were not able to add you to the hold queue. Please ensure that your account is in good standing and then visit our catalog to place your hold.

	Thanks', 0, 1, -1)",
			]
		], //update_default_request_statuses

		//katherine - ByWater

		//kirstien - ByWater

		//kodi - ByWater

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library

		//Jeremy Eden - Howell Carnegie District Library
		'add_openarchives_dateformatting_field' => [
			'title' => 'Add Open Archives date formatting setting',
			'description' => 'Add Open Archives date formatting setting',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE open_archives_collection ADD COLUMN dateFormatting tinyint default 1',
			]
		], //add_defaultContent_field

		//other

	];
}
