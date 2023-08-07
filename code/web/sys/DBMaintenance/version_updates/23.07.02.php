<?php
/** @noinspection PhpUnused */
function getUpdates23_07_02(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/
		'add_failed_login_attempt_logging' => [
			'title' => 'Add failed login attempt logging',
			'description' => 'Add failed login attempt logging',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS failed_logins_by_ip_address (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					ipAddress VARCHAR(25),
					timestamp INT(11)
				)',
			],
		], //add_failed_login_attempt_logging
  ];
}