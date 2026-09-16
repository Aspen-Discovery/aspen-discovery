<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_10_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth
		'omeka_settings' => [
			'title' => 'Omeka Settings Table',
			'description' => 'Create the omeka_settings table to store Omeka server connections',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS omeka_settings (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL DEFAULT \'\',
					baseUrl VARCHAR(255) DEFAULT NULL,
					apiVersion VARCHAR(10) NOT NULL DEFAULT \'s\',
					siteSlug VARCHAR(100) DEFAULT NULL,
					apiKeyIdentity VARCHAR(255) DEFAULT \'\',
					apiKeyCredential VARCHAR(255) DEFAULT \'\',
					runFullUpdate TINYINT(1) DEFAULT 0,
					lastUpdateOfChangedRecords INT(11) DEFAULT 0,
					lastUpdateOfAllRecords INT(11) DEFAULT 0
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			]
		], //omeka_settings
		'omeka_scopes' => [
			'title' => 'Omeka Scopes Table',
			'description' => 'Create the omeka_scopes table to store which item sets each scope includes',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS omeka_scopes (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL,
					settingId INT(11) DEFAULT NULL,
					includeAllItemSets TINYINT(1) NOT NULL DEFAULT 0,
					itemSetIds VARCHAR(500) NOT NULL DEFAULT \'\'
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			]
		], //omeka_scopes
		'library_omeka_scopes' => [
			'title' => 'Library Omeka Scopes Table',
			'description' => 'Create the library_omeka_scopes table to link libraries to Omeka scopes',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS library_omeka_scopes (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					omekaScopeId INT(11) NOT NULL,
					UNIQUE KEY libraryId (libraryId, omekaScopeId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			]
		], //library_omeka_scopes
		'location_omeka_scopes' => [
			'title' => 'Location Omeka Scopes Table',
			'description' => 'Create the location_omeka_scopes table to link locations to Omeka scopes',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS location_omeka_scopes (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					locationId INT(11) NOT NULL,
					omekaScopeId INT(11) NOT NULL,
					UNIQUE KEY locationId (locationId, omekaScopeId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			]
		], //location_omeka_scopes
		'omeka_title' => [
			'title' => 'Omeka Title Table',
			'description' => 'Create the omeka_title table to store items extracted from Omeka',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS omeka_title (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT(11) NOT NULL,
					omekaId INT(11) NOT NULL,
					title VARCHAR(750) DEFAULT NULL,
					mediaType VARCHAR(100) DEFAULT NULL,
					thumbnailUrl VARCHAR(750) DEFAULT NULL,
					itemSetIds VARCHAR(500) NOT NULL DEFAULT \'\',
					rawChecksum BIGINT(20) DEFAULT NULL,
					rawResponse MEDIUMBLOB DEFAULT NULL,
					rawResponseLength INT(11) DEFAULT NULL,
					dateFirstDetected BIGINT(20) DEFAULT NULL,
					lastSeen INT(11) DEFAULT NULL,
					deleted TINYINT(1) DEFAULT 0,
					UNIQUE KEY settingId (settingId, omekaId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			]
		], //omeka_title
		'omeka_export_log' => [
			'title' => 'Omeka Export Log Table',
			'description' => 'Create the omeka_export_log table to record Omeka extractions',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS omeka_export_log (
					id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					startTime INT(11) NOT NULL,
					endTime INT(11) DEFAULT NULL,
					lastUpdate INT(11) DEFAULT NULL,
					notes MEDIUMTEXT DEFAULT NULL,
					numProducts INT(11) DEFAULT 0,
					numErrors INT(11) DEFAULT 0,
					numAdded INT(11) DEFAULT 0,
					numDeleted INT(11) DEFAULT 0,
					numUpdated INT(11) DEFAULT 0,
					numSkipped INT(11) DEFAULT 0,
					numInvalidRecords INT(11) DEFAULT 0,
					settingId INT(11) DEFAULT NULL,
					KEY startTime (startTime)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			]
		], //omeka_export_log
		'omeka_module' => [
			'title' => 'Omeka Module',
			'description' => 'Register the Omeka module',
			'continueOnError' => false,
			'sql' => [
				"INSERT IGNORE INTO modules (name, indexName, backgroundProcess, logClassPath, logClassName, settingsClassPath, settingsClassName) VALUES ('Omeka', 'grouped_works', 'omeka_export', '/sys/Omeka/OmekaExportLogEntry.php', 'OmekaExportLogEntry', '/sys/Omeka/OmekaSetting.php', 'OmekaSetting')",
			]
		], //omeka_module

	];
}