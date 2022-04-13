<?php
/** @noinspection PhpUnused */
function getUpdates22_04_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'restrictLoginOfLibraryMembers' => [
			'title' => 'Restrict Login of Library Members',
			'description' => 'Allow restricting login by patrons of a specific home system',
			'sql' => [
				'ALTER TABLE library ADD COLUMN preventLogin TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN preventLoginMessage TEXT'
			]
		], //restrictLoginOfLibraryMembers
	];
}
