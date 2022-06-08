<?php
/** @noinspection PhpUnused */
function getUpdates22_06_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'aspen_site_timezone' => [
			'title' => 'Add timezone to Aspen site',
			'description' => 'Add timezone to Aspen site',
			'sql' => [
				'ALTER TABLE aspen_sites ADD COLUMN timezone TINYINT(1) DEFAULT 0',
			]
		], //aspen_site_timezone
	];
}
