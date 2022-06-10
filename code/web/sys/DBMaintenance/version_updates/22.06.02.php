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
//		'fix_list_entries_for_grouped_works_with_language' =>[
//			'title' => 'Fix List Entries for Grouped Works With Language',
//			'description' => 'Fix List Entries for Grouped Works With Language',
//			'sql' => [
//				"fixListEntriesForGroupedWorksWithLanguage",
//			]
//		], //fix_list_entries_for_grouped_works_with_language
	];
}

//function fixListEntriesForGroupedWorksWithLanguage(){
//	require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
//	UserListEntry
//}
