<?php
/** @noinspection PhpUnused */
function getUpdates23_08_10(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //name*/


		//mark - ByWater
		'split_user_fields' => [
			'title' => 'Split User Fields',
			'description' => 'Split up user fields including barcode and username',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE user ADD COLUMN unique_ils_id varchar(36) COLLATE utf8mb4_general_ci NOT NULL",
				"ALTER TABLE user ADD COLUMN ils_barcode varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"ALTER TABLE user ADD COLUMN ils_username varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"ALTER TABLE user ADD COLUMN ils_password varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL",
				"UPDATE user set unique_ils_id = username where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set ils_barcode = cat_username where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set ils_password = cat_password where source NOT IN ('admin', 'admin_sso')",
				"UPDATE user set cat_username = '' where source IN ('admin', 'admin_sso')",
				"UPDATE user set cat_password = '' where source IN ('admin', 'admin_sso')",
			]
		], //split_user_fields

        //kodi - ByWater
        'permissions_events_facets' => [
            'title' => 'Alters permissions for Events Facets',
            'description' => 'Create permissions for altering events facets',
            'sql' => [
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer Events Facet Settings', 'Events', 20, 'Allows the user to alter events facets for all libraries.')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Events Facet Settings'))",
            ],
        ], //permissions_events_facets
        'events_facets' => [
            'title' => 'Events Facet Tables',
            'description' => 'Adds tables for events facets',
            'sql' => [
                "CREATE TABLE events_facet_groups (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL UNIQUE
				)",
                "CREATE TABLE events_facet (
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
					canLock TINYINT DEFAULT 0
				) ENGINE = InnoDB",
                "ALTER TABLE events_facet ADD UNIQUE groupFacet (facetGroupId, facetName)",
            ],
        ],//events_facets
	];
}