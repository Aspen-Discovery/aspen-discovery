<?php

function getUpdates25_03_00(): array {
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

		//mark - Grove

		//katherine - Grove

		//kirstien - Grove

		// Leo Stoyanov - BWS
		'deduplicate_reading_history' => [
			'title' => 'De-Duplicate Reading History & Add Unique Key',
			'description' => 'Combine multiple rows per (userId, sourceId, source) into one row before adding unique key.',
			'continueOnError' => true,
			'sql' => [
				// 1. Keep the latest entry of the duplicated titles.
				"DELETE t1 FROM user_reading_history_work t1
				INNER JOIN user_reading_history_work t2 
				WHERE 
					t1.id < t2.id AND
					t1.userId = t2.userId AND
					t1.sourceId = t2.sourceId AND
					t1.source = t2.source;",

				// 2. Remove existing index if present (usually if an Admin is re-running a DB maintenance).
				"ALTER TABLE user_reading_history_work 
				DROP INDEX IF EXISTS user_source;",

				// 3. Create the new unique index
				"ALTER TABLE user_reading_history_work 
				ADD UNIQUE INDEX user_source (userId, sourceId, source);"
			],
		], //deduplicate_reading_history

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}