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
	];
}