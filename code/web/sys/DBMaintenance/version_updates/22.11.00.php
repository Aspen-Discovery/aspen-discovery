<?php
/** @noinspection PhpUnused */
function getUpdates22_11_00(): array
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

		//mark
		'add_numInvalidRecords_to_indexing_logs' => [
			'title' => 'Add Num Invalid Records To Indexing Logs',
			'description' => 'Add Num Invalid Records To Indexing Logs',
			'sql' => [
				'ALTER TABLE axis360_export_log ADD COLUMN numInvalidRecords INT(11) DEFAULT 0',
				'ALTER TABLE cloud_library_export_log ADD COLUMN numInvalidRecords INT(11) DEFAULT 0',
				'ALTER TABLE hoopla_export_log ADD COLUMN numInvalidRecords INT(11) DEFAULT 0',
				'ALTER TABLE ils_extract_log ADD COLUMN numInvalidRecords INT(11) DEFAULT 0',
				'ALTER TABLE overdrive_extract_log ADD COLUMN numInvalidRecords INT(11) DEFAULT 0',
				'ALTER TABLE reindex_log ADD COLUMN numInvalidRecords INT(11) DEFAULT 0',
			]
		], //add_numInvalidRecords_to_indexing_logs
		'sso_setting_add_entity_id' => [
			'title' => 'SSO - Add Entity ID',
			'description' => 'SSO - Add Entity ID',
			'sql' => [
				"ALTER TABLE sso_setting ADD column ssoEntityId VARCHAR(255)"
			]
		], //sso_setting_add_entity_id
		'materials_request_format_active_for_new_requests' => [
			'title' => 'Materials Request Format - Active For New Requests',
			'description' => 'Allow specific formats to be disabled for a materials request',
			'sql' => [
				"ALTER TABLE materials_request_formats ADD column activeForNewRequests TINYINT(1) DEFAULT 1"
			]
		], //materials_request_format_active_for_new_requests
		'add_numInvalidRecords_to_sideload_logs' => [
			'title' => 'Add Num Invalid Records To Sideload Logs',
			'description' => 'Add Num Invalid Records To Sideload Logs',
			'sql' => [
				'ALTER TABLE sideload_log ADD COLUMN numInvalidRecords INT(11) DEFAULT 0',
			]
		], //add_numInvalidRecords_to_sideload_logs
		'hoopla_index_by_day' => [
			'title' => 'Hoopla - Index By Day',
			'description' => 'Add an option for hoopla to index by day',
			'sql' => [
				'ALTER TABLE hoopla_settings ADD COLUMN indexByDay TINYINT(1) DEFAULT 0',
				'UPDATE hoopla_settings set indexByDay = 1',
				'UPDATE hoopla_settings set lastUpdateOfChangedRecords = 1664236800',
			]
		], //hoopla_index_by_day

		//kirstien

		//kodi

		//other
	];
}