<?php

/** @noinspection PhpUnused */
function getUpdates24_05_02(): array {
	/** @noinspection SqlWithoutWhere */
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
		'remove_used_triggers' => [
			'title' => 'Remove Unused Triggers',
			'description' => 'Remove Unused Triggers',
			'continueOnError' => true,
			'sql' => [
				'DROP TRIGGER after_grouped_work_record_items_delete',
				'DROP TRIGGER after_grouped_work_records_delete',
				'DROP TRIGGER after_grouped_work_delete',
				'DROP TRIGGER after_scope_delete',
			]
		], //remove_used_triggers

	];
}