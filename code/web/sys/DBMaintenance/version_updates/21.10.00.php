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
	];
}