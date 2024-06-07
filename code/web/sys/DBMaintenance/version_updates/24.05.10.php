<?php

function getUpdates24_05_10(): array {
	return [
        /*'name' => [
            'title' => '',
            'description' => '',
            'continueOnError' => false,
            'sql' => [
                ''
            ]
		], //name*/

		'library_add_can_update_work_phone_number' => [
			'title' => 'Library Add Can Update Work Phone Number',
			'description' => 'Allow control over if a library can update their work phone number',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library ADD allowPatronWorkPhoneNumberUpdates TINYINT(1) DEFAULT 1",
				"UPDATE library set allowPatronWorkPhoneNumberUpdates = showWorkPhoneInProfile",
			],
		], //library_add_can_update_work_phone_number
		'show_item_notes_in_copies' => [
			'title' => 'Show Item Notes in Copies',
			'description' => 'Allow control over if notes are displayed in copy details',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD showItemNotes TINYINT(1) DEFAULT 1",
			],
		], //show_item_notes_in_copies
    ];
}