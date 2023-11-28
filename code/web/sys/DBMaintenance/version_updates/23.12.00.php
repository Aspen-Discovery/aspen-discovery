<?php

function getUpdates23_12_00(): array {
	$curTime = time();
	return [
             'library_show_language_and_display_in_header' => [
			'title' => 'Library Show Language and Display in Header',
			'description' => 'Add option to allow the language and display settings to be shown in the page header',
			'sql' => [
				"ALTER TABLE library ADD languageAndDisplayInHeader INT(1) DEFAULT 0",
			],
		],
		'location_show_language_and_display_in_header' => [
			'title' => 'Location Show Language and Display in Header',
			'description' => 'Add option to allow the language and display settings to be shown in the page header',
			'sql' => [
				"ALTER TABLE location ADD languageAndDisplayInHeader INT(1) DEFAULT 0",
			],
		],
     ];
} 