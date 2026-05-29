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
	];
}