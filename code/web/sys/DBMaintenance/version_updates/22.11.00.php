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
		] //sso_setting_add_entity_id

		//kirstien

		//kodi

		//other
	];
}