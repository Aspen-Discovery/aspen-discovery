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
		]
	];
}