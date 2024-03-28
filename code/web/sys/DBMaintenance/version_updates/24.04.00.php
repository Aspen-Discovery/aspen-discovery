<?php

function getUpdates24_04_00(): array {
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

		//mark - ByWater
		'replace_arial_fonts' => [
			 'title' => 'Replace Arial Fonts',
			 'description' => 'Replace Arial Fonts',
			 'continueOnError' => false,
			 'sql' => [
				 "UPDATE Themes set bodyFont = 'Arion' where bodyFont = 'Arial'",
				 "UPDATE Themes set headingFont = 'Arion' where headingFont = 'Arial'",
			 ]
		 ], //replace_arial_fonts

		//kirstien - ByWater

		//kodi - ByWater

		//lucas - Theke

		//alexander - PTFS Europe

		//jacob - PTFS Europe

		// James Staub


	];
}