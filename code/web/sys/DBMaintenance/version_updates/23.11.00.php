<?php
/** @noinspection PhpUnused */
function getUpdates23_11_00(): array {
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
		'add_self_check_barcode_styles' => [
			'title' => 'Add self-check barcode styles',
			'description' => 'Add options for libraries to manage valid barcode styles for self-checkout',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE aspen_lida_self_check_barcode (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					selfCheckSettingsId INT(11) NOT NULL DEFAULT -1,
					barcodeStyle VARCHAR(75) NOT NULL 
				) ENGINE = InnoDB",
			]
		], // add_self_check_barcode_styles

		//kodi - ByWater

	];
}