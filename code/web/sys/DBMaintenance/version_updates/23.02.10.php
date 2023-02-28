<?php
/** @noinspection PhpUnused */
function getUpdates23_02_10(): array {
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

		'add_parent_child_info_to_records' => [
			'title' => 'Add Parent Child Info to Records',
			'description' => 'Add Parent Child Info to Records',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE grouped_work_records ADD COLUMN hasParentRecord TINYINT(1) NOT NULL DEFAULT 0;",
				"ALTER TABLE grouped_work_records ADD COLUMN hasChildRecord TINYINT(1) NOT NULL DEFAULT 0;",
			]
		], //add_parent_child_info_to_records
		'add_continuesRecords_more_details_section' => [
			'title' => 'Add Child Records Section to More Details',
			'description' => 'Add Child Records Section to More Details',
			'sql' => [
				"UPDATE grouped_work_more_details SET weight = (weight + 1) where weight >= 4",
				"INSERT INTO grouped_work_more_details (groupedWorkSettingsId, source, collapseByDefault, weight) select grouped_work_display_settings.id, 'continuesRecords', 0, 4 from grouped_work_display_settings where grouped_work_display_settings.id in (SELECT distinct groupedWorkSettingsId from grouped_work_more_details)",
			],
		], //add_continuesRecords_more_details_section
		//add_child_title_to_record_parents
		'add_continuedByRecords_more_details_section' => [
			'title' => 'Add Parent Records Section to More Details',
			'description' => 'Add Parent Records Section to More Details',
			'sql' => [
				"UPDATE grouped_work_more_details SET weight = (weight + 1) where weight >= 5",
				"INSERT INTO grouped_work_more_details (groupedWorkSettingsId, source, collapseByDefault, weight) select grouped_work_display_settings.id, 'continuedByRecords', 0, 5 from grouped_work_display_settings where grouped_work_display_settings.id in (SELECT distinct groupedWorkSettingsId from grouped_work_more_details)",
			],
		], //add_continuedByRecords_more_details_section
		'add_is_virtual_info_to_items' => [
			'title' => 'Add Is Virtual Info to Items',
			'description' => 'Add Is Virtual Info to Items',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE grouped_work_record_items ADD COLUMN isVirtual TINYINT(1) NOT NULL DEFAULT 0;",
			]
		], //add_is_virtual_info_to_items
		'includePersonalAndCorporateNamesInTopics' => [
			'title' => 'Include Personal And Corporate Names In Topics',
			'description' => 'Add includePersonalAndCorporateNamesInTopics to System Variables',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN includePersonalAndCorporateNamesInTopics TINYINT(1) NOT NULL DEFAULT 1;",
			]
		], //includePersonalAndCorporateNamesInTopics
		'index856Links' => [
			'title' => 'Index 856 links',
			'description' => 'Add the ability to disable or enable the indexing of 856 links from the MARC record',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN index856Links TINYINT(1) NOT NULL DEFAULT 0;",
				"updateIndex856Links",
				"ALTER TABLE grouped_work_display_settings DROP COLUMN show856LinksAsAccessOnlineButtons",
			]
		], //includePersonalAndCorporateNamesInTopics
    ];
}

function updateIndex856Links(&$update) {
	$groupedWorkDisplay = new GroupedWorkDisplaySetting();
	$groupedWorkDisplay->whereAdd('show856LinksAsAccessOnlineButtons = 1');
	$numMatches = $groupedWorkDisplay->count();
	if ($numMatches > 0) {
		$indexingProfile = new IndexingProfile();
		$indexingProfile->find();
		/** @var IndexingProfile[] $allIndexingProfiles */
		$allIndexingProfiles = $indexingProfile->fetchAll();
		foreach ($allIndexingProfiles as $indexingProfile) {
			$indexingProfile->index856Links = 1;
			$indexingProfile->update();
			$update['status'] .= "Set Indexing Profile $indexingProfile->name to index 856 links.<br/>";
		}
	}

	$update['success'] = true;
}