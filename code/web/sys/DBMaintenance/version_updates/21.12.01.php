<?php
/** @noinspection PhpUnused */
function getUpdates21_12_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'overdrive_max_extraction_threads' => [
			'title' => 'OverDrive Max Extraction Threads',
			'description' => 'Add a number of extraction threads to use when extracting from OverDrive',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN numExtractionThreads INT(11) DEFAULT 10'
			]
		], //overdrive_max_extraction_threads
	];
}