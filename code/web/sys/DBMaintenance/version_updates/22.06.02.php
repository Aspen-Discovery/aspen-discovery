<?php
/** @noinspection PhpUnused */
function getUpdates22_06_02() : array
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
		'force_reindex_of_records_with_pipe_language' => [
			'title' => 'Force records to reindex if the language was |||',
			'description' => 'Force records to reindex if the language was |||',
			'sql' => [
				"INSERT INTO grouped_work_scheduled_index (permanent_id,processed, indexAfter) SELECT permanent_id, 0, $curTime FROM grouped_work where permanent_id like '%|||'",
			]
		], //force_reindex_of_records_with_pipe_language
	];
}
