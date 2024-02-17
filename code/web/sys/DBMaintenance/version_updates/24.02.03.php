<?php

function getUpdates24_02_03(): array {
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

		'processes_to_stop' => [
			'title'=> 'Processes To Stop',
			'description' => 'Setup processes to stop table',
			'sql' => [
				'CREATE TABLE processes_to_stop (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					processId INT(11) NOT NULL,
					processName VARCHAR(255) NOT NULL,
					stopAttempted TINYINT DEFAULT 0,
					stopResults TEXT
				) ENGINE = INNODB',
			]
		], //processes_to_stop
    ];
}