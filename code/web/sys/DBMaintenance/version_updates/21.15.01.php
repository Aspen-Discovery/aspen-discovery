<?php
/** @noinspection PhpUnused */
function getUpdates21_15_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'iTypesToSuppress' => [
			'title' => 'ITypes To Suppress',
			'description' => 'Updates Indexing Profiles to allow suppressing by IType',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN iTypesToSuppress VARCHAR(100) DEFAULT ''",
			]
		], //iTypesToSuppress
		'iCode2sToSuppress' => [
			'title' => 'ICode2 To Suppress',
			'description' => 'Updates Indexing Profiles to allow suppressing by ICode2',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN iCode2sToSuppress VARCHAR(100) DEFAULT ''",
			]
		], //iCode2sToSuppress
		'bCode3sToSuppress' => [
			'title' => 'BCode3 To Suppress',
			'description' => 'Updates Indexing Profiles to allow suppressing by BCode3',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN bCode3sToSuppress VARCHAR(100) DEFAULT ''",
			]
		], //bCode3sToSuppress
	];
}
