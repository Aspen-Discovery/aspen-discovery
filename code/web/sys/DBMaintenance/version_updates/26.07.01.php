<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_07_01(): array {
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
		'ip_address_headers_to_check' => [
			'title' => 'IPAddress Headers To Check',
			'description' => 'Add the ability to configure what headers should be checked when loading the active IP.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN checkClientIP TINYINT(1) NOT NULL DEFAULT 0',
				'ALTER TABLE system_variables ADD COLUMN checkXForwardedFor TINYINT(1) NOT NULL DEFAULT 0',
				'ALTER TABLE system_variables ADD COLUMN checkXForwarded TINYINT(1) NOT NULL DEFAULT 0',
				'ALTER TABLE system_variables ADD COLUMN checkForwardedFor TINYINT(1) NOT NULL DEFAULT 0',
				'ALTER TABLE system_variables ADD COLUMN checkForwarded TINYINT(1) NOT NULL DEFAULT 0',
				'ALTER TABLE system_variables ADD COLUMN checkRemoteHost TINYINT(1) NOT NULL DEFAULT 0',
				'ALTER TABLE system_variables ADD COLUMN checkRemoteAddr TINYINT(1) NOT NULL DEFAULT 1',
			],
		], //ip_address_headers_to_check

		//kirstien

		//kodi

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