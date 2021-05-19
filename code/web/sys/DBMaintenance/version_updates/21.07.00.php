<?php
function getUpdates21_07_00()
{
	return [
		'indexing_profiles_add_notes_subfield' => [
			'title' => 'Indexing Profile add notes subfield',
			'description' => 'Add Notes Subfield to Indexing Profile',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN noteSubfield CHAR(1) default ' '",
				"UPDATE indexing_profiles SET noteSubfield = 'z' WHERE catalogDriver = 'Koha'"
			]
		],
		'indexing_profiles_add_due_date_for_Koha' => [
			'title' => 'Indexing Profile set dueDate for Koha',
			'description' => 'Add Due Date Subfield to Indexing Profile for Koha',
			'continueOnError' => true,
			'sql' => [
				"UPDATE indexing_profiles SET dueDate = 'k' WHERE catalogDriver = 'Koha'"
			]
		],
		'browse_categories_add_startDate_endDate' => [
			'title' => 'Add startDate and endDate to Browse Categories',
			'description' => 'Add startDate and endDate to Browse Categories',
			'sql' => [
				"ALTER TABLE browse_category ADD COLUMN startDate INT(11) DEFAULT 0",
				"ALTER TABLE browse_category ADD COLUMN endDate INT(11) DEFAULT 0",
			]
		],
	];
}

