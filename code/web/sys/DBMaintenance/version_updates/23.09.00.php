<?php
/** @noinspection PhpUnused */
function getUpdates23_09_00(): array {
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
		'web_builder_quick_polls' => [
			'title' => 'Web Builder Quick Polls',
			'description' => 'Setup tables to allow for quick polling within Web Builder',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE web_builder_quick_poll (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100) NOT NULL,
					urlAlias VARCHAR(100),
					introText MEDIUMTEXT COLLATE utf8mb4_general_ci,
					submissionResultText MEDIUMTEXT COLLATE utf8mb4_general_ci,
					requireLogin TINYINT(1),
					requireName TINYINT(1),
					requireEmail TINYINT(1),
					allowSuggestingNewOptions TINYINT(1),
					allowMultipleSelections TINYINT(1),
					status TINYINT(1) DEFAULT 1
				) ENGINE = InnoDB',
				'CREATE TABLE web_builder_quick_poll_option (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					weight INT DEFAULT 0,
					pollId INT(11) NOT NULL,
					label VARCHAR(100)
				) ENGINE = InnoDB',
				'CREATE TABLE library_web_builder_quick_poll (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL ,
					pollId INT(11) NOT NULL,
					label VARCHAR(100),
					UNIQUE (libraryId, pollId)
				) ENGINE = InnoDB',
				'CREATE TABLE web_builder_quick_poll_submission (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					pollId INT(11) NOT NULL,
					libraryId INT(11) NOT NULL,
					userId INT(11) DEFAULT NULL,
					name VARCHAR(255),
					email VARCHAR(255),
					dateSubmitted INT(11) NOT NULL
				) ENGINE = InnoDB',
				'CREATE TABLE web_builder_quick_poll_submission_selection (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					pollSubmissionId INT(11) NOT NULL,
					pollOptionId INT(11) NOT NULL,
					UNIQUE (pollSubmissionId, pollOptionId)
				) ENGINE = InnoDB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Web Builder', 'Administer All Quick Polls', 'Web Builder', 45, 'Allows the user to administer polls for all libraries.'),
					('Web Builder', 'Administer Library Quick Polls', 'Web Builder', 46, 'Allows the user to administer polls for their home library.')
					",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Quick Polls'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Library Quick Polls'))",
			]
		], //web_builder_quick_polls

		//kodi - ByWater
        'permissions_open_archives_facets' => [
            'title' => 'Alters permissions for Open Archives Facets',
            'description' => 'Create permissions for altering Open Archives facets',
            'continueOnError' => true,
            'sql' => [
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Open Archives', 'Administer All Open Archives Facet Settings', 'Open Archives', 0, 'Allows the user to alter Open Archives facets for all libraries.')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Open Archives Facet Settings'))",
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Open Archives', 'Administer Library Open Archives Facet Settings', 'Open Archives', 0, 'Allows the user to alter Open Archives facets for their library.')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Library Open Archives Facet Settings'))",
            ],
        ],
        //permissions_open_archives_facets
        'open_archives_facets' => [
            'title' => 'Open Archives Facet Tables',
            'description' => 'Adds tables for Open Archives facets',
            'sql' => [
                "CREATE TABLE open_archives_facet_groups (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL UNIQUE
				)",
                "CREATE TABLE open_archives_facets (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					facetGroupId INT NOT NULL, 
					displayName VARCHAR(50) NOT NULL, 
					displayNamePlural VARCHAR(50),
					facetName VARCHAR(50) NOT NULL,
					weight INT NOT NULL DEFAULT '0',
					numEntriesToShowByDefault INT NOT NULL DEFAULT '5',
					sortMode ENUM ('alphabetically', 'num_results') NOT NULL DEFAULT 'num_results',
					collapseByDefault TINYINT DEFAULT 1,
					useMoreFacetPopup TINYINT DEFAULT 1,
					translate TINYINT DEFAULT 1,
					multiSelect TINYINT DEFAULT 1,
					canLock TINYINT DEFAULT 1
				) ENGINE = InnoDB",
                "ALTER TABLE open_archives_facets ADD UNIQUE groupFacet (facetGroupId, facetName)",
            ],
        ], //open_archives_facets
       'open_archives_facets_default' => [
            'title' => 'Open Archives Facet Default Values',
            'description' => 'Adds a default Open Archives facet group that applies to all libraries unless edited',
            'sql' => [
                "INSERT INTO open_archives_facet_groups (id, name) VALUES (1, 'default')",
                "INSERT INTO open_archives_facets VALUES 
                             (1,1, 'Collection', 'Collections', 'collection_name', 1, 5, 'num_results', 1, 1, 1, 1, 1),
                             (2,1, 'Creator', 'Creators', 'creator_facet', 2, 5, 'num_results', 1, 1, 1, 1, 1),
                             (3,1, 'Contributor', 'Contributors', 'contributor_facet', 3, 5, 'num_results', 1, 1, 1, 1, 1),
                             (4,1, 'Type', 'Types', 'type', 4, 5, 'num_results', 1, 1, 1, 1, 1),
                             (5,1, 'Subject', 'Subjects', 'subject_facet', 5, 5, 'num_results', 1, 1, 1, 1, 1),
                             (6,1, 'Publisher', 'Publishers', 'publisher_facet', 6, 5, 'num_results', 1, 1, 1, 1, 1),
                             (7,1, 'Source', 'Sources', 'source', 7, 5, 'num_results', 1, 1, 1, 1, 1)",
            ],
        ], //open_archives_facets_default
        'permissions_website_facets' => [
            'title' => 'Alters Permissions for Website Facets',
            'description' => 'Create permissions for altering website facets',
            'continueOnError' => true,
            'sql' => [
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Website Indexing', 'Administer All Website Facet Settings', 'Website Indexing', 0, 'Allows the user to alter website facets for all libraries.')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Website Facet Settings'))",
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Website Indexing', 'Administer Library Website Facet Settings', 'Website Indexing', 0, 'Allows the user to alter website facets for their library.')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Library Website Facet Settings'))",
            ],
        ],
        //permissions_website_facets
        'website_facets' => [
            'title' => 'Website Facet Tables',
            'description' => 'Adds tables for website facets',
            'sql' => [
                "CREATE TABLE website_facet_groups (
					id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) NOT NULL UNIQUE
				)",
                "CREATE TABLE website_facets (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					facetGroupId INT NOT NULL, 
					displayName VARCHAR(50) NOT NULL, 
					displayNamePlural VARCHAR(50),
					facetName VARCHAR(50) NOT NULL,
					weight INT NOT NULL DEFAULT '0',
					numEntriesToShowByDefault INT NOT NULL DEFAULT '5',
					sortMode ENUM ('alphabetically', 'num_results') NOT NULL DEFAULT 'num_results',
					collapseByDefault TINYINT DEFAULT 1,
					useMoreFacetPopup TINYINT DEFAULT 1,
					translate TINYINT DEFAULT 1,
					multiSelect TINYINT DEFAULT 1,
					canLock TINYINT DEFAULT 1
				) ENGINE = InnoDB",
                "ALTER TABLE website_facets ADD UNIQUE groupFacet (facetGroupId, facetName)",
            ],
        ], //website_facets
        'website_facets_default' => [
            'title' => 'Website Facet Default Values',
            'description' => 'Adds a default website facet group that applies to all libraries unless edited',
            'sql' => [
                "INSERT INTO website_facet_groups (id, name) VALUES (1, 'default')",
                "INSERT INTO website_facets VALUES 
                             (1,1, 'Site Name', 'Site Names', 'website_name', 1, 5, 'num_results', 1, 1, 1, 1, 1),
                             (2,1, 'Website Type', 'Website Types', 'search_category', 2, 5, 'num_results', 1, 1, 1, 1, 1),
                             (3,1, 'Audience', 'Audiences', 'audience_facet', 3, 5, 'num_results', 1, 1, 1, 1, 1),
                             (4,1, 'Category', 'Categories', 'category_facet', 4, 5, 'num_results', 1, 1, 1, 1, 1)",
            ],
        ], //website_facets_default
        'facet_setting_ids' => [
            'title' => "Facet Setting Ids",
            'description' => "Adds facet setting ids for Open Archives and Website Indexing to library and location tables",
            'sql' => [
                "ALTER TABLE library ADD COLUMN openArchivesFacetSettingId INT(11) DEFAULT 1",
                "ALTER TABLE location ADD COLUMN openArchivesFacetSettingId INT(11) DEFAULT 1",
                "ALTER TABLE library ADD COLUMN websiteIndexingFacetSettingId INT(11) DEFAULT 1",
                "ALTER TABLE location ADD COLUMN websiteIndexingFacetSettingId INT(11) DEFAULT 1",
            ],
        ], //facet_setting_ids

		// kirstien - ByWater
		'add_forgot_barcode' => [
			'title' => 'Add Forgot Barcode option to Library Systems',
			'description' => 'Add option to allow users to receive their barcode via text',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library ADD enableForgotBarcode TINYINT(1) default 0',
			],
		],
		//add_forgot_barcode
		'add_restrict_sso_ip' => [
			'title' => 'Add option to restrict SSO by IP',
			'description' => 'Add option to restrict single sign-on login by IP address',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE sso_setting ADD restrictByIP TINYINT(1) default 0',
				'ALTER TABLE ip_lookup ADD ssoLogin TINYINT(1) default 0',
			],
		],
		//add_restrict_sso_ip

        // James Staub
        'donations_disambiguate_library_and_location' => [
            'title' => 'Corrects "Donate to Library" to "Donate to Location"',
            'description' => 'Corrects "Donate to Library" to "Donate to Location"',
            'sql' => [
                // mariadb < 10.5.2:
                 "ALTER TABLE donations CHANGE COLUMN donateToLibrary donateToLocation varchar(60)",
                 "ALTER TABLE donations CHANGE COLUMN donateToLibraryId donateToLocationId int(11)",
                // mariadb >= 10.5.2:
//                "ALTER TABLE donations RENAME COLUMN donateToLibrary TO donateToLocation",
//                "ALTER TABLE donations RENAME COLUMN donateToLibraryId TO donateToLocationId",
            ],
        ], //donations_disambiguate_library_and_location
        'ecommerce_report_permissions_all_vs_home' => [
            'title' => 'Update ecommerce report permissions',
            'description' => 'Update ecommerce report permissions',
            'continueOnError' => true,
            'sql' => [
                "UPDATE permissions
                SET name = 'View eCommerce Reports for All Libraries',
                    weight = 5,
                    description = 'Allows the user to view eCommerce reports for all libraries.'
                WHERE name = 'View eCommerce Reports'
                ",
                "UPDATE permissions
                SET name = 'View Donations Reports for All Libraries',
                    weight = 7,
                    description = 'Allows the user to view donations reports for all libraries.'
                WHERE name = 'View Donations Reports'
                ",
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('eCommerce', 'View eCommerce Reports for Home Library', '', 6, 'Allows the user to view eCommerce reports for their home library'),
					('eCommerce', 'View Donations Reports for Home Library', '', 8, 'Allows the user to view donations reports for their home library')
				",
                "insert into role_permissions (roleId, permissionId) values
                     ((select roleId from roles where name = 'libraryAdmin'), (select id from permissions where name = 'View eCommerce Reports for Home Library')),
                     ((select roleId from roles where name = 'libraryAdmin'), (select id from permissions where name = 'View Donations Reports for Home Library'))
                ",
            ],
        ], //ecommerce_report_permissions
	];
}