<?php
/** @noinspection PhpUnused */
function getUpdates21_12_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
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
		'library_menu_link_languages' => [
			'title' => 'Menu Link Languages',
			'description' => 'Allow Menu LInks to be limited by language',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE library_link_language (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							libraryLinkId INT,
							languageId INT,
							UNIQUE INDEX libraryLinkLanguage(libraryLinkId, languageId)
						) ENGINE = INNODB;',
				'INSERT INTO library_link_language (languageId, libraryLinkId) SELECT languages.id, library_links.id from languages, library_links;'
			]
		], //placard_languages
		'edit_placard_permissions' => [
			'title' => 'Edit Library Placard Permissions',
			'description' => 'Add Library Placard Permissions',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Local Enrichment', 'Edit Library Placards', '', 55, 'Allows the user to edit, but not create placards for their library.')",
			]
		], //edit_placard_permissions
		'suppressRecordsWithUrlsMatching' => [
			'title' => 'Add suppressRecordsWithUrlsMatching to Indexing Profiles',
			'description' => 'Add suppressRecordsWithUrlsMatching to give control over eContent to be suppressed from the ILS',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN suppressRecordsWithUrlsMatching VARCHAR(512) DEFAULT 'overdrive\.com|contentreserve\.com|hoopla|yourcloudlibrary|axis360\.baker-taylor\.com'"
			]
		], //suppressRecordsWithUrlsMatching
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
		'addSiteIdToCachedGreenhouseData' => [
			'title' => 'Add SiteId To Cached Greenhouse Data',
			'description' => 'Add tracking of cached data by site to avoid duplication',
			'sql' => [
				'ALTER TABLE greenhouse_cache ADD COLUMN siteId INT(11)',
				'TRUNCATE TABLE greenhouse_cache'
			]
		], //addSiteIdToCachedGreenhouseData
		'increaseGreenhouseDataNameLength' => [
			'title' => 'Increase name in Cached Greenhouse Data',
			'description' => 'Increase the length of the name for cached Greenhouse data',
			'sql' => [
				'ALTER TABLE greenhouse_cache CHANGE COLUMN name name VARCHAR(100)',
			]
		], //increaseGreenhouseDataNameLength
		'literaryFormIndexingUpdates' => [
			'title' => 'Literary Form Indexing Updates',
			'description' => 'Add additional fields to indexing profiles to determine how literary forms are indexed',
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN determineLiteraryFormBy TINYINT DEFAULT 0',
				"ALTER TABLE indexing_profiles ADD COLUMN literaryFormSubfield CHAR(1) DEFAULT ''",
				'ALTER TABLE indexing_profiles ADD COLUMN hideUnknownLiteraryForm TINYINT DEFAULT 0',
				'ALTER TABLE indexing_profiles ADD COLUMN hideNotCodedLiteraryForm TINYINT DEFAULT 0',
			]
		], //literaryFormIndexingUpdates
		'overdrive_circulationEnabled' => [
			'title' => 'OverDrive Scope Circulation Enabled Switch',
			'description' => 'Add the ability to disable the circulation of OverDrive materials within Aspen',
			'sql' => [
				'ALTER TABLE overdrive_scopes ADD COLUMN circulationEnabled TINYINT DEFAULT 1'
			]
		], //overdrive_circulationEnabled
		'addDefaultCatPassword' => [
			'title' => 'Add Default cat_password',
			'description' => 'Add default cat_password for cases when we are masquerading',
			'sql' => [
				"ALTER TABLE user CHANGE COLUMN cat_password cat_password VARCHAR(256) DEFAULT ''"
			]
		], //addDefaultCatPassword
		'hoopla_regroup_all_records' => [
			'title' => 'Hoopla Add Regroup All Records',
			'description' => 'Add the ability to regroup all records at the beginning of indexing for hoopla',
			'sql' => [
				'ALTER TABLE hoopla_settings ADD COLUMN regroupAllRecords TINYINT(1) DEFAULT 0',
				"ALTER TABLE hoopla_export_log ADD COLUMN numChangedAfterGrouping INT(11) DEFAULT 0",
				"ALTER TABLE hoopla_export_log ADD COLUMN numRegrouped INT(11) DEFAULT 0",
				'UPDATE hoopla_settings set regroupAllRecords = 1'
			]
		],
	];
}