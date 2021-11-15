<?php
/** @noinspection PhpUnused */
function getUpdates21_14_02() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'embiggenDatabaseName' => [
			'title' => 'Embiggen databaseName',
			'description' => 'Embiggen account profiles > database schema name',
			'sql' => [
				'ALTER TABLE account_profiles CHANGE COLUMN databaseName databaseName VARCHAR(75)',
			]
		], //embiggenDatabaseName

	];
}