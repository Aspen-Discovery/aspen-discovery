<?php
/** @noinspection PhpUnused */
function getUpdates22_06_03() : array
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
		'add_default_system_variables' =>[
			'title' => 'Add default System Variables',
			'description' => 'Add default System Variables if ',
			'sql' => [
				"addDefaultSystemVariables",
			]
		], //fix_list_entries_for_grouped_works_with_language
		'fix_list_entries_for_grouped_works_with_language' =>[
			'title' => 'Fix List Entries for Grouped Works With Language',
			'description' => 'Fix List Entries for Grouped Works With Language',
			'sql' => [
				"fixListEntriesForGroupedWorksWithLanguage",
			]
		], //fix_list_entries_for_grouped_works_with_language
		'remove_grouped_work_solr_core' => [
			'title' => 'Remove Version 1 Grouped Work core',
			'description' => 'Removed version 1 grouped works core',
			'sql' => [
				"removeV1GroupedWorkCore",
			]
		], //force_reindex_of_records_with_pipe_language
		'facet_counts_to_show' => [
			'title' => 'Facet Counts to show',
			'description' => 'Allow configuration of which facets are shown to users',
			'sql' => [
				"ALTER TABLE grouped_work_display_settings add COLUMN facetCountsToShow TINYINT DEFAULT 1",
			]
		]
	];
}

function addDefaultSystemVariables(&$update) {
	$systemVariables = SystemVariables::getSystemVariables();
	if ($systemVariables == false){
		$systemVariables = new SystemVariables();
		$systemVariables->searchVersion = 2;
		if ($systemVariables->insert()){
			$update['status'] = '<strong>Added System Variables</strong><br/>';
			$update['success'] = true;
		}else{
			$update['status'] = '<strong>System Variables could not be created</strong><br/>';
			$update['success'] = true;
		}
	}else{
		$update['status'] = '<strong>System Variables already exist</strong><br/>';
		$update['success'] = true;
	}
}

function removeV1GroupedWorkCore(&$update){
	global $configArray;
	$solrBaseUrl = $configArray['Index']['url'];

	$systemVariables = SystemVariables::getSystemVariables();
	if ($systemVariables->searchVersion == 2) {

		//Update suggesters for v2
		$opts = array('http' =>
			array(
				'timeout' => 1200
			)
		);
		$context = stream_context_create($opts);

		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if (!file_get_contents($solrBaseUrl . '/grouped_works_v2/suggest?suggest.build=true', false, $context)) {
			echo("Could not update suggesters for grouped_works_v2");
			$update['status'] = '<strong>Could not update suggesters for grouped_works_v2</strong><br/>';
			$update['success'] = false;
			return;
		}

		//Unload the core
		if (!file_get_contents($solrBaseUrl . '/admin/cores?action=UNLOAD&core=grouped_works', false, $context)) {
			$update['status'] = '<strong>Could not unload grouped_works core</strong><br/>';
			$update['success'] = false;
			return;
		}

		global $serverName;
		//Remove the data files for the core
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		if (SystemUtils::recursive_rmdir("/data/aspen-discovery/$serverName/solr7/grouped_works/data")){
			$update['status'] = 'Removed Grouped Work core<br/>';
			$update['success'] = true;
		}else{
			$update['status'] = 'Could not remove data directory /data/aspen-discovery/$serverName/solr7/grouped_works/data<br/>';
			$update['success'] = false;
		}
	}else{
		$update['status'] = 'Search version was incorrect, not deleting core because it is in use.<br/>';
		$update['success'] = false;
	}
}

function fixListEntriesForGroupedWorksWithLanguage(&$update){
	require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
	$listEntry = new UserListEntry();
	$listEntry->selectAdd();
	$listEntry->selectAdd('sourceId');
	$listEntry->selectAdd('GROUP_CONCAT(id) as relatedIds');
	$listEntry->source = 'GroupedWork';
	$listEntry->whereAdd('LENGTH(sourceId) = 36');
	$listEntry->groupBy('sourceId');
	$listEntry->find();
	$oldIds = [];
	while ($listEntry->fetch()){
		/** @noinspection PhpUndefinedFieldInspection */
		$oldIds[$listEntry->sourceId] = $listEntry->relatedIds;
	}
	$numUpdated = 0;
	foreach ($oldIds as $oldId => $relatedIds){
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->whereAdd("permanent_id LIKE '$oldId-%'");
		$groupedWork->find();
		$relatedListIds = explode(',', $relatedIds);
		$newId = null;
		if ($groupedWork->getNumResults() == 0) {
			//This grouped work is deleted
			$oldGroupedWork = new GroupedWork();
			$oldGroupedWork->permanent_id = $oldId;
			if ($oldGroupedWork->find(true)){
				$newGroupedWork = new GroupedWork();
				$newGroupedWork->full_title = $oldGroupedWork->full_title;
				$newGroupedWork->author = $oldGroupedWork->author;
				$newGroupedWork->grouping_category = $oldGroupedWork->grouping_category;
				$newGroupedWork->find();
				while ($newGroupedWork->fetch()){
					if (strlen($newGroupedWork->permanent_id) == 40){
						$newId = $newGroupedWork->permanent_id;
					}
				}
			}else {
				continue;
			}
		}else if ($groupedWork->getNumResults() == 1){
			$groupedWork->fetch();
			$newId = $groupedWork->permanent_id;
		}else{
			$newIds = [];
			while ($groupedWork->fetch()){
				if (substr($groupedWork->permanent_id, 37, 3) == 'eng'){
					$newId = $groupedWork->permanent_id;
					break;
				}else{
					$newIds[] = $groupedWork->permanent_id;
				}
			}
			if ($newId == null){
				$newId = reset($newIds);
			}
		}
		if ($newId){
			foreach ($relatedListIds as $index=>$listEntryId){
				$listEntry = new UserListEntry();
				$listEntry->id = $listEntryId;
				if ($listEntry->find(true)) {
					$listEntry->sourceId = $newId;
					$listEntry->update();
					$numUpdated++;
				}
			}
		}
	}
	$numOldWorks = count($oldIds);
	$update['status'] = "Updated $numUpdated of $numOldWorks works from old to new user grouped work id<br/>";
	$update['success'] = true;
}
