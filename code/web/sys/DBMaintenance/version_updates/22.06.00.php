<?php
/** @noinspection PhpUnused */
function getUpdates22_06_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'grouped_work_language' => [
			'title' => 'Grouped Work Language',
			'description' => 'Add Language as a differentiator for Grouped Works',
			'sql' => [
				'ALTER TABLE grouped_work ADD COLUMN primary_language VARCHAR(3)'
			]
		], //sample
	];
}
