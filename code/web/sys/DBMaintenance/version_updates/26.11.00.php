<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
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

		'series_facet_settings' => [
			'title' => 'Series Facet Settings',
			'description' => 'Adds tables to allow series search facets to be customized per library',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS series_facet_groups (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL UNIQUE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS series_facet (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					facetGroupId INT NOT NULL,
					displayName VARCHAR(50) NOT NULL,
					displayNamePlural VARCHAR(50),
					facetName VARCHAR(50) NOT NULL,
					weight INT NOT NULL DEFAULT '0',
					numEntriesToShowByDefault INT NOT NULL DEFAULT '5',
					showAsDropDown TINYINT NOT NULL DEFAULT '0',
					sortMode ENUM ('alphabetically', 'num_results') NOT NULL DEFAULT 'num_results',
					showAboveResults TINYINT NOT NULL DEFAULT '0',
					showInResults TINYINT NOT NULL DEFAULT '1',
					showInAdvancedSearch TINYINT NOT NULL DEFAULT '1',
					collapseByDefault TINYINT DEFAULT '1',
					useMoreFacetPopup TINYINT DEFAULT 1,
					translate TINYINT DEFAULT 0,
					multiSelect TINYINT DEFAULT 0,
					canLock TINYINT DEFAULT 0,
					UNIQUE groupFacet (facetGroupId, facetName)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
				"CREATE TABLE IF NOT EXISTS library_series_facet_setting (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT NOT NULL,
					seriesFacetGroupId INT NOT NULL,
					UNIQUE library (libraryId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			],
		], //series_facet_settings
		'permissions_series_facets' => [
			'title' => 'Series Facet Permissions',
			'description' => 'Create permissions for altering series facets',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Local Enrichment', 'Administer Series Facet Settings', 'Series', 20, 'Allows the user to define facets for series searches.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Series Facet Settings'))",
			],
		], //permissions_series_facets

	];
}