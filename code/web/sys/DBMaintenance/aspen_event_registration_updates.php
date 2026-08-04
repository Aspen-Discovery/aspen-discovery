<?php /** @noinspection SqlResolve */

function getAspenEventRegistrationUpdates() {
	return [
		'create_user_aspen_event_instance_registrations_table' => [
			'title' => 'Create the User Aspen Event Instance Registrations table',
			'description' => 'Adds the ability to save registration to aspen a aspen event instance',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_aspen_event_instance_registrations (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT NOT NULL,
					eventInstanceId INT NOT NULL,
					success TINYINT(1) DEFAULT NULL,
					attended TINYINT(1) DEFAULT NULL
				)ENGINE = InnoDB',
			],
		], // create_user_aspen_event_instance_registrations_table
		'create_aspen_event_settings_table' => [
			'title' => 'Define events settings for Aspen Native Events module',
			'description' => 'Initial setup of the Aspen Native Events',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_event_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					registrationModalBody mediumtext
				) ENGINE = INNODB',
			],
		], // create_aspen_event_settings_table
		'add_registrationRequired_to_events' => [
			'title' => 'Add registrationRequired to Events',
			'description' => 'Add an registration required column to events',
			'sql' => [
				'ALTER TABLE event ADD COLUMN registrationRequired TINYINT(1) DEFAULT 0',
			],
		], // add_registrationRequired_to_events
		'add_numberOfSeats_to_events' => [
			'title' => 'Add numberOfSeats to Events and Event Instances',
			'description' => 'Add number of seats columns for capacity management. NULL or 0 means unlimited.',
			'sql' => [
				'ALTER TABLE event ADD COLUMN numberOfSeats INT DEFAULT NULL',
				'ALTER TABLE event_instance ADD COLUMN numberOfSeats INT DEFAULT NULL',
			],
		], // add_numberOfSeats_to_events
		'add_allowEventRegistration_to_library_t' => [
			'title' => 'Add Allow Event Registration To Library',
			'description' => 'Add the option to allow/block staff from enabling event registration on a per event basis',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN IF NOT EXISTS allowEventRegistration TINYINT(1) NOT NULL DEFAULT 0",
			],
		], //add_allowEventRegistration_to_library

		//jacob - Staff Event Registration
		'staff_event_registration_permissions' => [
			'title' => 'Staff Event Registration Permissions',
			'description' => 'Add permissions for staff to register users for Aspen native events',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Register Users for Events for All Locations', 'Events', 55, 'Allows the user to register patrons for native events at all locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Register Users for Events for Home Library Locations', 'Events', 56, 'Allows the user to register patrons for native events at home library locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Register Users for Events for Home Location', 'Events', 57, 'Allows the user to register patrons for native events at home location.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Register Users for Events for All Locations'))",
			]
		], //staff_event_registration_permissions
		'staff_patron_event_attendance_management_permissions' => [
			'title' => 'Staff Event Registration Permissions',
			'description' => 'Add permissions for staff to register users for Aspen native events',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Manage Patron Event Attendance for All Locations', 'Events', 55, 'Allows the user manage patron attendance for native events at all locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Manage Patron Event Attendance for Home Library Locations', 'Events', 56, 'Allows the user manage patron attendance for native events at home library locations.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Manage Patron Event Attendance for Home Location', 'Events', 57, 'Allows the user manage patron attendance for native events at home location.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Manage Patron Event Attendance for All Locations'))",
			]
		], //staff_patron_event_attendance_management_permissions
		'staff_event_registration_library_setting' => [
			'title' => 'Staff Event Registration Library Setting',
			'description' => 'Add library setting to allow staff to register users for events',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN IF NOT EXISTS allowStaffToRegisterUsersForEvents TINYINT(1) DEFAULT 0",
			]
		], //staff_event_registration_library_setting
		'staff_event_registration_tracking' => [
			'title' => 'Staff Event Registration Tracking',
			'description' => 'Add tracking fields for staff registrations',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_aspen_event_instance_registrations ADD COLUMN IF NOT EXISTS registeredByStaffId INT DEFAULT NULL",
				"ALTER TABLE user_events_entry ADD COLUMN IF NOT EXISTS savedByStaffId INT DEFAULT NULL",
			]
		], //staff_event_registration_tracking
		'patron_attendance_tracking' => [
			'title' => 'Add Attended Column',
			'description' => 'Add attended column to track patron attendance at events',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE user_aspen_event_instance_registrations ADD COLUMN IF NOT EXISTS attended TINYINT(1) DEFAULT NULL",
			]
		], //patron_attendance_tracking
		'add_fieldUse_to_event_field' => [
			'title' => 'Add fieldUse to Event Fields',
			'description' => 'Add an field use column to event fields',
			'sql' => [
				'ALTER TABLE event_field ADD COLUMN fieldUse TINYINT(1) DEFAULT 0',
			],
		], // add_fieldUse_to_event_field
		'add_fieldSetUse_to_event_field' => [
			'title' => 'Add fieldSetUse to Event Field Sets',
			'description' => 'Add an field use column to event field sets',
			'sql' => [
				'ALTER TABLE event_field_set ADD COLUMN fieldSetUse TINYINT(1) DEFAULT 0',
			],
		], // add_fieldSetUse_to_event_field
		'update_event_type_table' => [
			'title' => 'Update Event Type Table',
			'description' => 'Update the Event Type table to link to information and/or registration field set ids',
			'sql' => [
				'ALTER TABLE event_type CHANGE COLUMN eventFieldSetId eventInformationFieldSetId INT NOT NULL',
				'ALTER TABLE event_type ADD COLUMN eventRegistrationFieldSetId TINYINT(1) DEFAULT 0',
			],
		], // update_event_type_table
		'add_staffNotes_to_event_table' => [
			'title' => 'Add staffNotes to Event Table',
			'description' => 'Add staff only notes to events',
			'sql' => [
				'ALTER TABLE event ADD COLUMN staffNotes longtext DEFAULT NULL',
			],
		], // add_staffNotes_to_event_table
		'add_user_aspen_event_instance_registrations_event_field' => [
			'title' => 'Add User Events Instance Registration Event Field Table',
			'description' => 'Stores custom registration fields as tied to event instance registrations to record patron information',
			'sql' =>  [
				"CREATE TABLE IF NOT EXISTS user_aspen_event_instance_registrations_event_field (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventInstanceRegistrationId INT NOT NULL,
					eventFieldId INT NOT NULL,
					value TEXT DEFAULT NULL
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
			] 
		], //add_user_aspen_event_instance_registrations_event_field
		'create_attendee_category_tables' => [
			'title' => 'Create Attendee Category Tables',
			'description' => 'Add attendee categories and link them to event types with max attendee counts',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS aspen_event_attendee_category (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					staffDescription VARCHAR(255) NOT NULL,
					publicDescription VARCHAR(255) NOT NULL,
					UNIQUE KEY (name)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
				"CREATE TABLE IF NOT EXISTS event_type_attendee_category (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eventTypeId INT NOT NULL,
					attendeeCategoryId INT NOT NULL,
					maxAttendees INT NOT NULL DEFAULT 1,
					UNIQUE KEY (eventTypeId, attendeeCategoryId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			],
		], //create_attendee_category_tables
		'create_registration_attendee_table' => [
			'title' => 'Create Registration Attendee Table',
			'description' => 'Stores per-category attendee counts for each registration',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS user_aspen_event_instance_registration_attendee (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					registrationId INT NOT NULL,
					attendeeCategoryId INT NOT NULL,
					count INT NOT NULL DEFAULT 0,
					UNIQUE KEY (registrationId, attendeeCategoryId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			],
		], //create_registration_attendee_table
	];
}
