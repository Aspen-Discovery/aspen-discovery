<?php

function getUpdates24_07_00(): array {
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


		//kirstien - ByWater


		//kodi - ByWater
		'self_registration_form_carlx' => [
			'title' => 'Self Registration Variables for CarlX',
			'description' => 'Moves variables needed for CarlX registration out of variables table & config array',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS self_registration_form_carlx (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					selfRegEmailNotices VARCHAR(255),
					selfRegDefaultBranch VARCHAR(255),
					selfRegPatronExpirationDate DATE,
					selfRegPatronStatusCode VARCHAR(255),
					selfRegPatronType VARCHAR(255),
    				selfRegRegBranch VARCHAR(255),
    				selfRegRegisteredBy VARCHAR(255),
    				lastPatronBarcode VARCHAR(255),
    				barcodePrefix VARCHAR(255),
					selfRegIDNumberLength INT(2)
				) ENGINE INNODB',
			],
		], // self_registration_form_carlx
		//katherine - ByWater

		//other



	];
}