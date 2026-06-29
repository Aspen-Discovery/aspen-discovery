<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_00(): array {
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
		'symphony_municipalities' => [
			'title' => 'Add new table for Symphony municipalities',
			'description' => 'Add new table for symphony municipalities for self registration.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS self_reg_municipality_values_symphony (
					`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`selfRegistrationFormId` int(11) NOT NULL,
					`municipality` varchar(255) default '' NOT NULL,
					`ilsMunicipality` varchar(255) default '' NOT NULL,
					`municipalityType` varchar(10),
					`selfRegAllowed` tinyint(1) NOT NULL DEFAULT '1',
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
			]
		], //symphony_municipalities

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//other

	];
}
