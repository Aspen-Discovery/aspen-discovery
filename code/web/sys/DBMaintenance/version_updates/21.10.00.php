<?php
/** @noinspection PhpUnused */
function getUpdates21_10_00() : array
{
	return [
		'aspen_sites' => [
			'title' => 'Create Aspen Sites',
			'description' => 'Create Sites for the greenhouse',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_sites (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					baseUrl VARCHAR(255) UNIQUE, 
					siteType INT DEFAULT 0,
					libraryType INT DEFAULT 0,
					libraryServes INT DEFAULT 0,
					implementationStatus INT DEFAULT 0,
					hosting VARCHAR(75), 
					operatingSystem VARCHAR(75), 
					notes TEXT
				) ENGINE INNODB'
			]
		], //aspen_sites
		'add_sorts_for_browsable_objects'=>[
			'title' => 'Add Sorts for Browsable Objects',
			'description' => 'Add new sorts for Browse Categories and Collection Spotlights',
			'sql' => [
				"ALTER TABLE collection_spotlight_lists CHANGE COLUMN defaultSort defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating', 'holds', 'publication_year_desc', 'publication_year_asc') default 'relevance'",
				"ALTER TABLE browse_category CHANGE COLUMN defaultSort defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating', 'holds', 'publication_year_desc', 'publication_year_asc') default 'relevance'"
			]
		], //add_sorts_for_browsable_objects
		'fix_ils_volume_indexes' => [
			'title' => 'Fix ILS Volume Indexes',
			'description' => 'Allow Volume Ids to be non unique',
			'sql' => [
				'ALTER TABLE ils_volume_info DROP index volumeId',
				'ALTER TABLE ils_volume_info DROP index recordId',
				'ALTER TABLE ils_volume_info Add unique index recordVolume(recordId, volumeId)',
			]
		], //add_maxDaysToFreeze
		'add_maxDaysToFreeze' => [
			'title' => 'Add max days to freeze option in library settings',
			'description' => 'Allow libraries to limit the amount of days out a user can freeze a hold',
			'sql' => [
				'ALTER TABLE library ADD COLUMN maxDaysToFreeze INT(11) DEFAULT -1'
			]
		]
	];
}