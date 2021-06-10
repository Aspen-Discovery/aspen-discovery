<?php
/** @noinspection PhpUnused */
function getUpdates21_08_00() : array
{
	return [
		'quipu_ecard_settings' => [
			'title' => 'Quipu eCARD Settings',
			'description' => 'Add the ability to define settings for Quipu eCARD integration',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE quipu_ecard_setting (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					server VARCHAR(50) NOT NULL, 
					clientId INT(11) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			]
		], //quipu_ecard_settings

	];
}