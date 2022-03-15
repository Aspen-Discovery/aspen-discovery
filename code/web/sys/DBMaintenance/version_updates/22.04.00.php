<?php
/** @noinspection PhpUnused */
function getUpdates22_04_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'restrictLoginToLibraryMembers' => [
			'title' => 'Restrict Login to Library Members',
			'description' => 'Allow restricting login to patrons of a specific home system',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowLoginToPatronsOfThisLibraryOnly TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN messageForPatronsOfOtherLibraries TEXT'
			]
		], //restrictLoginToLibraryMembers
	];
}