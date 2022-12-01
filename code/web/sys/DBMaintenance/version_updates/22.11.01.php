<?php
/** @noinspection PhpUnused */
function getUpdates22_11_01(): array
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

		'library_showVolumesWithLocalCopiesFirst' => [
			'title' => 'Add showVolumesWithLocalCopiesFirst to Library settings',
			'description' => 'Add showVolumesWithLocalCopiesFirst to Library settings',
			'sql' => [
				'ALTER TABLE library ADD COLUMN showVolumesWithLocalCopiesFirst TINYINT DEFAULT 0',
			]
		], //library_showVolumesWithLocalCopiesFirst
		'content_cafe_disable' => [
			'title' => 'Content Cafe Disabling',
			'description' => 'Allow disabling content cafe when the service is down',
			'sql' => [
				'ALTER TABLE contentcafe_settings ADD COLUMN enabled TINYINT(1) DEFAULT 1',
			]
		], //content_cafe_disable
	];
}