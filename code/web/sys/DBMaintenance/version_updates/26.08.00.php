<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_08_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe
		'add_booking_toggles_to_library' => [
			'title' => 'Add Bookings Toggles to Library',
			'description' => 'Adds enableBookingDisplay, enableBookingPlacement, enableBookingUpdates, and enableBookingCancellations flags to the library table to allow per-library-system control of the patron actions in the Koha Bookings integration.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS enableBookingDisplay tinyint(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS enableBookingPlacement tinyint(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS enableBookingUpdates tinyint(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS enableBookingCancellations tinyint(1) DEFAULT 0',
			]
		], //add_booking_toggles_to_library
		'add_bookable_to_grouped_work_record_items' => [
			'title' => 'Add Bookable Flag to Grouped Work Record Items',
			'description' => 'Adds bookable column to grouped_work_record_items so item-level bookability can be indexed from Koha.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE grouped_work_record_items ADD COLUMN IF NOT EXISTS bookable tinyint(1) DEFAULT 0',
			]
		], //add_bookable_to_grouped_work_record_items
		'create_user_booking' => [
			'title' => 'Create User Booking Table',
			'description' => 'Creates user_booking table to store a minimal reference copy of Koha bookings for change detection.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS user_booking (
					id int(11) NOT NULL AUTO_INCREMENT,
					userId int(11) NOT NULL,
					recordId varchar(50) NOT NULL,
					itemId varchar(50) NOT NULL,
					ils_booking_id int(11) NOT NULL,
					ils_start_date date NOT NULL,
					ils_end_date date NOT NULL,
					ils_pickup_library_id varchar(50) DEFAULT NULL,
					ils_status varchar(50) DEFAULT NULL,
					createdAt int(11) NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY userId (userId, ils_booking_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
			]
		], //create_user_booking
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
