<?php

function getUpdates25_09_00(): array {
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

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS

		//alexander - Open Fifth

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
