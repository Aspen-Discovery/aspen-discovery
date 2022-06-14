<?php
/** @noinspection PhpUnused */
function getUpdates22_06_04() : array
{
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'remove_id_from_grouped_work_record_item_url' => [
			'title' => 'Remove id from grouped_work_record_item_url',
			'description' => 'Remove id for grouped_work_record_item_url since it is unused',
			'sql' => [
				"ALTER TABLE grouped_work_record_item_url DROP COLUMN id",
			]
		], //remove_id_from_grouped_work_record_item_url
		'reprocess_all_sideloads_22_06_04' => [
			'title' => 'Reprocess all sideloads',
			'description' => 'Reprocess all sideloads to ensure they group properly',
			'sql' => [
				"UPDATE sideloads set runFullUpdate = 1",
			]
		], //reprocess_all_sideloads_22_06_04
		'clear_default_covers_22_06_04' => [
			'title' => 'Clear Default Covers',
			'description' => 'Clear Default Covers',
			'sql' => [
				"DELETE FROM bookcover_info where imageSource = 'default'",
			]
		], //clear_default_covers_22_06_04
	];
}
