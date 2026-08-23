<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_09_00(): array {
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
	
		//pedro

		//mark j
		'add_num_sample_titles_to_email_template' => [
			'title' => 'Add Number of Sample Titles to Email Template',
			'description' => 'Adds a column to control how many sample titles are shown in saved search alert emails.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE email_template ADD COLUMN numSampleTitles INT NOT NULL DEFAULT 3"
			]
		], //add_num_sample_titles_to_email_template

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}
