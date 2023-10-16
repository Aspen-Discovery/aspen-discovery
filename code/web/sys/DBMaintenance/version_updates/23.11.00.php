<?php

function getUpdates23_11_00(): array {
	$curTime = time();
	return [
        		//Alexander - PTFS
			'display_list_author_control' => [
				'title' => 'User List Author Control',
				'description' => 'Add a setting to allow users to control whether their name appears on public lists they have created.',
				'continueOnError' => true,
				'sql' => [
					'ALTER TABLE  user_list ADD COLUMN displayListAuthor TINYINT(1) DEFAULT 1',
					'ALTER TABLE user ADD COLUMN displayListAuthor TINYINT(1) DEFAULT 1',
				],
		],
    ];
}

