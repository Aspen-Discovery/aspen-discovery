<?php

function getUpdates24_09_00(): array {
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

		//katherine - ByWater

		//kirstien - ByWater
		'add_defaultContent_field' => [
			'title' => 'Add defaultContent to user_ils_messages',
			'description' => 'Add defaultContent to user_ils_messages',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_ils_messages ADD COLUMN defaultContent mediumtext',
			]
		], //add_defaultContent_field

		//kodi - ByWater

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library


		//other

	];
}