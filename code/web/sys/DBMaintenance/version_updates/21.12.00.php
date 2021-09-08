<?php
/** @noinspection PhpUnused */
function getUpdates21_12_00() : array
{
	return [
		'placard_languages' => [
			'title' => 'Placard Languages',
			'description' => 'Allow Placards to be limited by language',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE placard_language (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							placardId INT,
							languageId INT,
							UNIQUE INDEX placardLanguage(placardId, languageId)
						) ENGINE = INNODB;',
				'INSERT INTO placard_language (languageId, placardId) SELECT languages.id, placards.id from languages, placards;'
			]
		], //placard_languages
		'edit_placard_permissions' => [
			'title' => 'Edit Library Placard Permissions',
			'description' => 'Add Library Placard Permissions',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Local Enrichment', 'Edit Library Placards', '', 55, 'Allows the user to edit, but not create placards for their library.')",
			]
		], //edit_placard_permissions
		'cacheGreenhouseData' => [
			'title' => 'Allow caching the library location data',
			'description' => 'Caches library location data for quicker app access via the Greenhouse',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS greenhouse_cache (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50),
					locationId INT(3),
					libraryId INT(3),
					solrScope VARCHAR(75),
					latitude VARCHAR(75),
					longitude VARCHAR(75), 
					unit VARCHAR(3),
					baseUrl VARCHAR(255), 
					lastUpdated INT(11)
				) ENGINE INNODB'
			]
		], //cacheGreenhouseData

	];
}