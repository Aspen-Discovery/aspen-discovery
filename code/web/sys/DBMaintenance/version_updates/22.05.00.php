<?php
/** @noinspection PhpUnused */
function getUpdates22_05_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'footerText' => [
			'title' => 'Add Footer Text to Library',
			'description' => 'Add Footer Text to Library',
			'sql' => [
				'ALTER TABLE library ADD COLUMN footerText MEDIUMTEXT',
			]
		], //footerText
	];
}
