<?php

function getUpdates25_07_00(): array {
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

		//mark - Grove
		'account_profile_enable_fetching_ils_messages' => [
			'title' => 'Add Enable Fetching ILS Messages to Account Profile',
			'description' => 'Add Enable Fetching ILS Messages to Account Profile',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE account_profiles ADD COLUMN enableFetchingIlsMessages TINYINT(1) DEFAULT 0'
			]
		], //account_profile_enable_fetching_ils_messages
		'branded_app_notification_access_token' => [
			'title' => 'Add Notification Access Token To Branded App Settings',
			'description' => 'Add Notification Access Token To Branded App Settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_lida_branded_settings ADD COLUMN notificationAccessToken varchar(256) DEFAULT NULL',
			]
		], //branded_app_notification_access_token
		'ils_notification_setting_account_profile' => [
			'title' => 'Link ILS Notification Setting to Account Profile',
			'description' => 'Link ILS Notification Setting to Account Profile',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE ils_notification_setting ADD COLUMN accountProfileId INT(11) DEFAULT -1',
				"UPDATE ils_notification_setting SET accountProfileId = (SELECT id from account_profiles where name <> 'admin' and name <> 'admin_sso' LIMIT 1)",
			]
		], //ils_notification_setting_account_profile
		'remove_vendor_specific_defaults' => [
			'title' => 'Remove Vendor Specific Defaults',
			'description' => 'Remove Vendor Specific Default Values',
			'sql' => [
				"ALTER TABLE system_variables CHANGE COLUMN supportingCompany supportingCompany varchar(72) DEFAULT ''",
			]
		], //remove_vendor_specific_defaults
		'remember_page_defaults_for_user' => [
			'title' => 'Remember Page Size and Sort For User',
			'description' => 'Remember Page Size and Sort for User',
			'sql' => [
				'CREATE TABLE user_page_defaults (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11),
					module VARCHAR(100),
					action VARCHAR(100),
					objectId INT(11),
					pageSize INT(11),
					pageSort VARCHAR(25),
					UNIQUE INDEX (userId, module, action, objectId)
				)'
			]
		], //remember_page_defaults_for_user
		'increase_allowable_sort_length' => [
			'title' => 'Increase Allowable Sort Length for User page defaults',
			'description' => 'Increase Allowable Sort Length for User page defaults',
			'sql' => [
				'ALTER TABLE user_page_defaults CHANGE COLUMN pageSort pageSort VARCHAR(50)'
			]
		], //increase_allowable_sort_length
		'increase_record_to_include_fields' => [
			'title' => 'Increase Record To Include Field Lengths',
			'description' => 'Increase Record To Include Field Lengths',
			'sql' => [
				'ALTER TABLE library_records_to_include CHANGE COLUMN location location VARCHAR(250) NOT NULL',
				'ALTER TABLE location_records_to_include CHANGE COLUMN location location VARCHAR(250) NOT NULL',
				"ALTER TABLE library_records_to_include CHANGE COLUMN subLocation subLocation VARCHAR(250) NOT NULL DEFAULT ''",
				"ALTER TABLE location_records_to_include CHANGE COLUMN subLocation subLocation VARCHAR(250) NOT NULL DEFAULT ''",
				"ALTER TABLE library_records_to_include CHANGE COLUMN iType iType VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN iType iType VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN iTypesToExclude iTypesToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN iTypesToExclude iTypesToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN audience audience VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN audience audience VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN audiencesToExclude audiencesToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN audiencesToExclude audiencesToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN format format VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN format format VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN formatsToExclude formatsToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN formatsToExclude formatsToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN shelfLocation shelfLocation VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN shelfLocation shelfLocation VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN shelfLocationsToExclude shelfLocationsToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN shelfLocationsToExclude shelfLocationsToExclude VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE library_records_to_include CHANGE COLUMN collectionCode collectionCode VARCHAR(250) DEFAULT NULL",
				"ALTER TABLE location_records_to_include CHANGE COLUMN collectionCode collectionCode VARCHAR(250) DEFAULT NULL",
			]
		], //increase_record_to_include_fields


		//katherine - Grove
		'add_series_member_priority_score' => [
			'title' => 'Add a priority score to series member table',
			'description' => 'Add a priority score to series members to sort series prioritizing MARC field 800 over 830',
			'sql' => [
				"ALTER TABLE series_member ADD COLUMN priorityScore TINYINT NOT NULL DEFAULT 1;",
			]
		], //add_series_member_priority_score

		//kirstien - Grove

		//kodi - Grove
		'image_pdf_owning_sharing' => [
			'title' => 'Owning and Sharing for Images and PDFs',
			'description' => 'Add owning and sharing columns to file_uploads and image_uploads.',
			'sql' => [
				"ALTER TABLE file_uploads ADD COLUMN owningLibrary INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE file_uploads ADD COLUMN sharing INT(11) NOT NULL DEFAULT 2",
				"ALTER TABLE file_uploads ADD COLUMN sharedWithLibrary INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE image_uploads ADD COLUMN owningLibrary INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE image_uploads ADD COLUMN sharing INT(11) NOT NULL DEFAULT 2",
				"ALTER TABLE image_uploads ADD COLUMN sharedWithLibrary INT(11) NOT NULL DEFAULT -1",
			],
		], //image_pdf_owning_sharing
		'web_content_permissions' => [
			'title' => 'Web Content Permissions',
			'description' => 'Add restricted (home library only) permissions for web content.',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
					('Web Builder', 'Administer Web Content for Home Library', 'Web Builder', 61, 'Allows the user to manage images and pdfs for their home library only.')",
			],
		], //custom_web_resource_pages_roles
		'rename_web_builder_image_uploads' => [
			'title' => 'Rename Web Builder Image Uploads',
			'description' => 'Rename Web Builder image uploads so they do not conflict with other image uploads',
			'sql' => [
				'renameUploadedWBImages'
			]
		], //rename_web_builder_image_uploads
		'rename_web_builder_pdf_uploads' => [
			'title' => 'Rename Web Builder PDF Uploads',
			'description' => 'Rename Web Builder PDF uploads so they do not conflict with other PDF uploads',
			'sql' => [
				'renameUploadedWBFiles'
			]
		], //rename_web_builder_pdf_uploads
		'rename_theme_image_uploads' => [
			'title' => 'Rename Theme Image Uploads',
			'description' => 'Rename images uploaded for themes so they do not conflict with other image uploads',
			'sql' => [
				'renameUploadedThemeImages'
			]
		], //rename_theme_image_uploads
		'rename_web_resource_placard_images' => [
			'title' => 'Rename Web Resource and Placard Images',
			'description' => 'Rename Web Resource and Placard image uploads so they do not conflict with other image uploads',
			'sql' => [
				'renameUploadedWebResourceAndPlacardImages'
			]
		], //rename_web_resource_placard_images

		// Myranda - Grove

		//Yanjun Li - ByWater
		'add_comprise_donation_settings' => [
			'title' => 'Add Comprise Donation Settings',
			'description' => 'Add customer name and id for donation in Comprise Settings',
			'sql' => [
				"ALTER TABLE comprise_settings ADD COLUMN customerNameForDonation VARCHAR(50) DEFAULT NULL",
				"ALTER TABLE comprise_settings ADD COLUMN customerIdForDonation INT(11) DEFAULT NULL",
			]
		], //add_comprise_donation_settings
		'remove_starRating_from_overdrive_api_product_metadata' => [
			'title' => 'Remove Star Rating from overdrive_api_product_metadata',
			'description' => 'Remove starRating from overdrive_api_product_metadata table.',
			'sql' => [
				"ALTER TABLE overdrive_api_product_metadata DROP COLUMN starRating",
			]
		], //remove_starRating_from_overdrive_api_product_metadata

		// Leo Stoyanov - BWS
		'enable_web_builder_for_libraries_using_it' => [
			'title' => 'Enable Web Builder for Libraries Using Web Builder Features',
			'description' => 'Enable Web Builder only for libraries that have associated Web Builder content (resources, pages, forms, etc.).',
			'continueOnError' => true,
			'sql' => [
				"UPDATE library SET enableWebBuilder = 1 
				WHERE libraryId IN (
					-- Libraries with Web Resources
					SELECT DISTINCT libraryId FROM library_web_builder_resource
					UNION
					-- Libraries with Basic Pages  
					SELECT DISTINCT libraryId FROM library_web_builder_basic_page
					UNION
					-- Libraries with Portal Pages (Custom Pages)
					SELECT DISTINCT libraryId FROM library_web_builder_portal_page
					UNION
					-- Libraries with Custom Web Resource Pages
					SELECT DISTINCT libraryId FROM library_web_builder_custom_web_resource_page
					UNION
					-- Libraries with Custom Forms
					SELECT DISTINCT libraryId FROM library_web_builder_custom_form
					UNION
					-- Libraries with Grapes Pages
					SELECT DISTINCT libraryId FROM library_web_builder_grapes_page
					UNION
					-- Libraries with Quick Polls
					SELECT DISTINCT libraryId FROM library_web_builder_quick_poll
				)",
			],
		], //enable_web_builder_for_libraries_using_it
		'rollback_administer_side_loads_name' => [
			'title' => 'Clean Slate: Revert Permission Changes and Drop Permission Group Tables',
			'description' => 'Provides a clean slate by reverting permission name changes and dropping permission group tables if they exist. 
			This is primarily for those testing and allows complete rollback of permission group functionality.',
			'continueOnError' => false,
			'sql' => [
				"UPDATE permissions SET name = 'Administer Side Loads' WHERE name = 'Administer All Side Loads'",
				"DROP TABLE IF EXISTS permission_group_permissions",
				"DROP TABLE IF EXISTS permission_groups",
			],
		], // rollback_administer_side_loads_name
		'permission_groups_and_mappings' => [
			'title' => 'Create Permission Groups and Mappings',
			'description' => 'Create tables for permission groups and seed initial groups and mappings.',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS `permission_groups` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`groupKey` varchar(100) NOT NULL,
					`sectionName` varchar(75) NOT NULL,
					`label` varchar(100) NOT NULL,
					`description` varchar(250) DEFAULT '',
					PRIMARY KEY (`id`),
					UNIQUE KEY `groupKey` (`groupKey`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
				"CREATE TABLE IF NOT EXISTS `permission_group_permissions` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`groupId` int(11) NOT NULL,
					`permissionId` int(11) NOT NULL,
					PRIMARY KEY (`id`),
					KEY `groupId` (`groupId`),
					KEY `permissionId` (`permissionId`),
					CONSTRAINT `fk_permission_group` FOREIGN KEY (`groupId`) REFERENCES `permission_groups` (`id`) ON DELETE CASCADE,
					CONSTRAINT `fk_permission_group_permission` FOREIGN KEY (`permissionId`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
				"INSERT IGNORE INTO `permission_groups` (`groupKey`,`sectionName`,`label`,`description`) VALUES
					('adminBrowseCategories','Local Enrichment','Administer Browse Categories','Specify whether the role can manage all browse categories, only those for its library, or specific category groups.'),
					('admincollectionSpotlights','Local Enrichment','Administer Collection Spotlights','Specify whether the role can manage all collection spotlights or only those for its library.'),
					('adminPlacards','Local Enrichment','Administer Placards','Specify whether the role can manage all placards or only those for its library.'),
					('adminJavaScriptSnippets','Local Enrichment','Administer JavaScript Snippets','Specify whether the role can manage all JavaScript snippets or only those scoped to its library.'),
					('adminSystemMessages','Local Enrichment','Administer System Messages','Specify whether the role can manage all system messages or only those scoped to its library.'),
					('adminThemes','Theme & Layout','Administer Themes','Specify whether the role can manage all themes or only those scoped to its library.'),
					('adminLayoutSettings','Theme & Layout','Administer Layout Settings','Specify whether the role can manage all layout settings or only those scoped to its library.'),
					('adminLibraries','Primary Configuration','Administer Libraries','Specify whether the role can manage all libraries or only its assigned home library.'),
					('adminLocations','Primary Configuration','Administer Locations','Specify whether the role can manage all locations, only its library locations, or only the home location.'),
					('adminHoldsReports','Circulation Reports','View Holds Reports','Specify whether the role can view all holds reports or only those for its library.'),
					('adminStudentReports','Circulation Reports','View Student Reports','Specify whether the role can view all student reports or only those for its library.'),
					('adminCollectionReports','Circulation Reports','View Collection Reports','Specify whether the role can view all collection reports or only those for its library.'),
					('adminEcomReports','eCommerce','View eCommerce Reports','Specify whether the role can view eCommerce reports for all libraries or only those for its library.'),
					('adminDonationsReports','eCommerce','View Donations Reports','Specify whether the role can view donation reports for all libraries or only those for its library.'),
					('adminEmailTemplates','Email','Administer Email Templates','Specify whether the role can manage all email templates or only those for its library.'),
					('adminEventsAdmin','Events','Administer Events','Specify whether the role can manage events across all locations, library locations, or only the home location.'),
					('adminPrivateEvents','Events','View Private Events','Specify whether the role can view private events across all locations, library locations, or only the home location.'),
					('adminEventsReports','Events','View Event Reports','Specify whether the role can view event reports for all libraries or only those for its library.'),
					('adminGroupedWorkDisplaySettings','Grouped Work Display','Administer Grouped Work Display Settings','Specify whether the role can manage grouped work display settings across all libraries or only those for its library.'),
					('adminGroupedWorkFacets','Grouped Work Display','Administer Grouped Work Facets','Specify whether the role can manage grouped work facets across all libraries or only those for its library.'),
					('adminFormatSorting','Grouped Work Display','Administer Format Sorting','Specify whether the role can manage format sorting options across all libraries or only those for its library.'),
					('adminVdxForms','ILL Integration','Administer VDX Forms','Specify whether the role can manage all VDX forms or only those for its library.'),
					('adminLocalIllForms','ILL Integration','Administer Local ILL Forms','Specify whether the role can manage all local ILL forms or only those for its library.'),
					('adminSublocations','Primary Configuration - Location Sublocations','Administer Sublocations','Specify whether the role can manage sublocations for all libraries or only those for its library.'),
					('adminWebBuilderMenus','Web Builder','Administer Menus','Specify whether the role can manage all web builder menus or only those for its library.'),
					('adminBasicPages','Web Builder','Administer Basic Pages','Specify whether the role can manage all basic pages or only those for its library.'),
					('adminCustomPages','Web Builder','Administer Custom Pages','Specify whether the role can manage all custom pages or only those for its library.'),
					('adminCustomForms','Web Builder','Administer Custom Forms','Specify whether the role can manage all custom forms or only those for its library.'),
					('adminWebResources','Web Builder','Administer Web Resources','Specify whether the role can manage all web resources or only those for its library.'),
					('adminStaffMembers','Web Builder','Administer Staff Members','Specify whether the role can manage all staff members or only those for its library.'),
					('adminWebsiteFacets','Website Indexing','Administer Website Facets','Specify whether the role can manage website facet settings across all libraries or only those for its library.'),
					('adminYearInReview','Year in Review','Administer Year in Review','Specify whether the role can manage year-in-review settings for all libraries or only those for its library.'),
					('adminQuickPolls','Web Builder','Administer Quick Polls','Specify whether the role can manage all quick polls or only those for its library.'),
					('adminGrapesPages','Web Builder','Administer Grapes Pages','Specify whether the role can manage all Grapes pages or only those for its library.'),
					('adminCustomWebResourcePages','Web Builder','Administer Custom Web Resource Pages','Specify whether the role can manage all custom web resource pages or only those for its library.'),
					('adminSideLoads','Cataloging & eContent','Administer Side Loads','Specify whether the role can manage side loads globally, for its home library, or manage side load scopes for its home library.'),
					('adminWebContent','Web Builder','Administer Web Content','Specify whether the role can manage all web content (i.e., images and PDFs) or only those for its library.')",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Browse Categories','Administer Library Browse Categories','Administer Selected Browse Category Groups') WHERE pg.groupKey = 'adminBrowseCategories'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Collection Spotlights','Administer Library Collection Spotlights') WHERE pg.groupKey = 'admincollectionSpotlights'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Placards','Administer Library Placards', 'Edit Library Placards') WHERE pg.groupKey = 'adminPlacards'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All JavaScript Snippets','Administer Library JavaScript Snippets') WHERE pg.groupKey = 'adminJavaScriptSnippets'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All System Messages','Administer Library System Messages') WHERE pg.groupKey = 'adminSystemMessages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Themes','Administer Library Themes') WHERE pg.groupKey = 'adminThemes'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Layout Settings','Administer Library Layout Settings') WHERE pg.groupKey = 'adminLayoutSettings'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Libraries','Administer Home Library') WHERE pg.groupKey = 'adminLibraries'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Locations','Administer Home Library Locations', 'Administer Home Location') WHERE pg.groupKey = 'adminLocations'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Location Holds Reports','View All Holds Reports') WHERE pg.groupKey = 'adminHoldsReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Location Student Reports','View All Student Reports') WHERE pg.groupKey = 'adminStudentReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Location Collection Reports','View All Collection Reports') WHERE pg.groupKey = 'adminCollectionReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View eCommerce Reports for All Libraries','View eCommerce Reports for Home Library') WHERE pg.groupKey = 'adminEcomReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Donations Reports for All Libraries','View Donations Reports for Home Library') WHERE pg.groupKey = 'adminDonationsReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Email Templates','Administer Library Email Templates') WHERE pg.groupKey = 'adminEmailTemplates'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Events for All Locations','Administer Events for Home Library Locations','Administer Events for Home Location') WHERE pg.groupKey = 'adminEventsAdmin'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Private Events for All Locations','View Private Events for Home Library Locations','View Private Events for Home Location') WHERE pg.groupKey = 'adminPrivateEvents'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('View Event Reports for All Libraries','View Event Reports for Home Library') WHERE pg.groupKey = 'adminEventsReports'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Grouped Work Display Settings','Administer Library Grouped Work Display Settings') WHERE pg.groupKey = 'adminGroupedWorkDisplaySettings'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Grouped Work Facets','Administer Library Grouped Work Facets') WHERE pg.groupKey = 'adminGroupedWorkFacets'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Format Sorting','Administer Library Format Sorting') WHERE pg.groupKey = 'adminFormatSorting'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All VDX Forms','Administer Library VDX Forms') WHERE pg.groupKey = 'adminVdxForms'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Local ILL Forms','Administer Library Local ILL Forms') WHERE pg.groupKey = 'adminLocalIllForms'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Sublocations for All Libraries','Administer Sublocations for Home Library') WHERE pg.groupKey = 'adminSublocations'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Menus','Administer Library Menus') WHERE pg.groupKey = 'adminWebBuilderMenus'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Basic Pages','Administer Library Basic Pages') WHERE pg.groupKey = 'adminBasicPages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Custom Pages','Administer Library Custom Pages') WHERE pg.groupKey = 'adminCustomPages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Custom Forms','Administer Library Custom Forms') WHERE pg.groupKey = 'adminCustomForms'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Web Resources','Administer Library Web Resources') WHERE pg.groupKey = 'adminWebResources'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Staff Members','Administer Library Staff Members') WHERE pg.groupKey = 'adminStaffMembers'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Website Facet Settings','Administer Library Website Facet Settings') WHERE pg.groupKey = 'adminWebsiteFacets'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Year in Review for All Libraries','Administer Year in Review for Home Library') WHERE pg.groupKey = 'adminYearInReview'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Quick Polls','Administer Library Quick Polls') WHERE pg.groupKey = 'adminQuickPolls'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Grapes Pages','Administer Library Grapes Pages') WHERE pg.groupKey = 'adminGrapesPages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Custom Web Resource Pages','Administer Library Custom Web Resource Pages') WHERE pg.groupKey = 'adminCustomWebResourcePages'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer Side Loads','Administer Side Loads for Home Library', 'Administer Side Load Scopes for Home Library') WHERE pg.groupKey = 'adminSideLoads'",
				"INSERT IGNORE INTO `permission_group_permissions` (`groupId`,`permissionId`) SELECT pg.id, p.id FROM `permission_groups` pg JOIN `permissions` p ON p.name IN ('Administer All Web Content','Administer Web Content for Home Library') WHERE pg.groupKey = 'adminWebContent'",
			],
		], // permission_groups_and_mappings
		'cleanup_mutually_exclusive_permissions' => [
			'title' => 'Cleanup Mutually Exclusive Permissions',
			'description' => 'Remove duplicate permissions from roles where multiple permissions from the same permission group are assigned, keeping only the broadest permission.',
			'continueOnError' => true,
			'sql' => [
				// adminBrowseCategories - priority: All > Library > Selected
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Library Browse Categories', 'Administer Selected Browse Category Groups')
				 AND p2.name = 'Administer All Browse Categories'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Selected Browse Category Groups'
				 AND p2.name = 'Administer Library Browse Categories'",

				// admincollectionSpotlights - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Collection Spotlights'
				 AND p2.name = 'Administer All Collection Spotlights'",

				// adminPlacards - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Library Placards', 'Edit Library Placards')
				 AND p2.name = 'Administer All Placards'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Edit Library Placards'
				 AND p2.name = 'Administer Library Placards'",

				// adminJavaScriptSnippets - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library JavaScript Snippets'
				 AND p2.name = 'Administer All JavaScript Snippets'",

				// adminSystemMessages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library System Messages'
				 AND p2.name = 'Administer All System Messages'",

				// adminThemes - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Themes'
				 AND p2.name = 'Administer All Themes'",

				// adminLayoutSettings - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Layout Settings'
				 AND p2.name = 'Administer All Layout Settings'",

				// adminLibraries - priority: All > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Home Library'
				 AND p2.name = 'Administer All Libraries'",

				// adminLocations - priority: All > Home Library Locations > Home Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Home Library Locations', 'Administer Home Location')
				 AND p2.name = 'Administer All Locations'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Home Location'
				 AND p2.name = 'Administer Home Library Locations'",

				// adminHoldsReports - priority: All > Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Location Holds Reports'
				 AND p2.name = 'View All Holds Reports'",

				// adminStudentReports - priority: All > Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Location Student Reports'
				 AND p2.name = 'View All Student Reports'",

				// adminCollectionReports - priority: All > Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Location Collection Reports'
				 AND p2.name = 'View All Collection Reports'",

				// adminEcomReports - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View eCommerce Reports for Home Library'
				 AND p2.name = 'View eCommerce Reports for All Libraries'",

				// adminDonationsReports - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Donations Reports for Home Library'
				 AND p2.name = 'View Donations Reports for All Libraries'",

				// adminEmailTemplates - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Email Templates'
				 AND p2.name = 'Administer All Email Templates'",

				// adminEventsAdmin - priority: All Locations > Home Library Locations > Home Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Events for Home Library Locations', 'Administer Events for Home Location')
				 AND p2.name = 'Administer Events for All Locations'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Events for Home Location'
				 AND p2.name = 'Administer Events for Home Library Locations'",

				// adminPrivateEvents - priority: All Locations > Home Library Locations > Home Location
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('View Private Events for Home Library Locations','View Private Events for Home Location')
				 AND p2.name = 'View Private Events for All Locations'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Private Events for Home Location'
				 AND p2.name = 'View Private Events for Home Library Locations'",

				// adminEventsReports - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'View Event Reports for Home Library'
				 AND p2.name = 'View Event Reports for All Libraries'",

				// adminGroupedWorkDisplaySettings - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Grouped Work Display Settings'
				 AND p2.name = 'Administer All Grouped Work Display Settings'",

				// adminGroupedWorkFacets - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Grouped Work Facets'
				 AND p2.name = 'Administer All Grouped Work Facets'",

				// adminFormatSorting - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Format Sorting'
				 AND p2.name = 'Administer All Format Sorting'",

				// adminVdxForms - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library VDX Forms'
				 AND p2.name = 'Administer All VDX Forms'",

				// adminLocalIllForms - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Local ILL Forms'
				 AND p2.name = 'Administer All Local ILL Forms'",

				// adminSublocations - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Sublocations for Home Library'
				 AND p2.name = 'Administer Sublocations for All Libraries'",

				// adminWebBuilderMenus - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Menus'
				 AND p2.name = 'Administer All Menus'",

				// adminBasicPages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Basic Pages'
				 AND p2.name = 'Administer All Basic Pages'",

				// adminCustomPages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Custom Pages'
				 AND p2.name = 'Administer All Custom Pages'",

				// adminCustomForms - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Custom Forms'
				 AND p2.name = 'Administer All Custom Forms'",

				// adminWebResources - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Web Resources'
				 AND p2.name = 'Administer All Web Resources'",

				// adminStaffMembers - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Staff Members'
				 AND p2.name = 'Administer All Staff Members'",

				// adminWebsiteFacets - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Website Facet Settings'
				 AND p2.name = 'Administer All Website Facet Settings'",

				// adminYearInReview - priority: All Libraries > Home Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Year in Review for Home Library'
				 AND p2.name = 'Administer Year in Review for All Libraries'",

				// adminQuickPolls - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Quick Polls'
				 AND p2.name = 'Administer All Quick Polls'",

				// adminGrapesPages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Grapes Pages'
				 AND p2.name = 'Administer All Grapes Pages'",

				// adminCustomWebResourcePages - priority: All > Library
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Library Custom Web Resource Pages'
				 AND p2.name = 'Administer All Custom Web Resource Pages'",

				// adminSideLoads - priority: All > Library > Library Scopes
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name IN ('Administer Side Loads for Home Library', 'Administer Side Load Scopes for Home Library')
				 AND p2.name = 'Administer Side Loads'",

				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Side Load Scopes for Home Library'
				 AND p2.name = 'Administer Side Loads for Home Library'",

				// adminWebContent - priority: All > Home Library
				// "Administer Web Content for Home Library" is a new permission as of 25.07.00, so this
				// DELETE operation should not be necessary, but best to make any corrections just in case.
				"DELETE rp1
				 FROM role_permissions rp1
				 JOIN permissions p1 ON rp1.permissionId = p1.id
				 JOIN role_permissions rp2 ON rp1.roleId = rp2.roleId
				 JOIN permissions p2 ON rp2.permissionId = p2.id
				 WHERE p1.name = 'Administer Web Content for Home Library'
				 AND p2.name = 'Administer All Web Content'",
			],
		], // cleanup_mutually_exclusive_permissions
		'update_administer_side_loads_name' => [
			'title' => 'Update "Administer Side Loads" Permission Name to "Administer All Side Loads"',
			'description' => 'Update the "Administer Side Loads" permission name to "Administer All Side Loads" to clarify its broad scope.',
			'continueOnError' => false,
			'sql' => [
				"UPDATE permissions SET name = 'Administer All Side Loads' WHERE name = 'Administer Side Loads'",
			],
		], // update_administer_side_loads_name

		// Laura Escamilla - ByWater Solutions

		//alexander - Open Fifth
		'add_grapes_templates_to_db' => [
			'title' => 'Add Grapes Temaplates To DB',
			'description' => 'Add Grapes templates to db',
			'sql' => [
				'addTemplateFromJson'
			]
		], //add_grapes_templates_to_db

		//chloe - Open Fifth
		'move_heycentric_permission' => [
			 'title' => 'Move HeyCentric Permission',
			 'description' => 'Move the Administrer HeyCentric Settings permission into the existing eCommerce section',
			 'continueOnError' => false,
			 'sql' => [
				"UPDATE permissions SET name='Administer HeyCentric', sectionName='eCommerce', description='Allows the user to administer the integration with HeyCentric <em>This has potential security and cost implications.</em>' WHERE name='Administer HeyCentric Settings' AND sectionName='ecommerce'",
			 ],
			 
		 ], // move_heycentric_permission


		//Jacob - Open Fifth
		'sso_do_not_create_user_in_ils' => [
			'title' => 'Do not create SSO user in ils',
			'description' => 'Ability to stop SSO from creating users in the ils',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE sso_setting ADD COLUMN createUserInIls int(11) DEFAULT 1',
			]
		],
		//sso_do_not_create_user_in_ils

		//Jacob - Open Fifth
		'disable_user_agent_logging' => [
			'title' => 'Disable User Agent Logging',
			'description' => 'Add system variable to control user agent logging',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN disable_user_agent_logging tinyint(1) DEFAULT 0",
			]
		], //disable_user_agent_logging

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

		//Talpa Search
		'talpa_settings_defaults_update_07_25' => [
			'title' => 'Update to Talpa Default "Other Results" Explainer Text',
			'description' => 'Updates the default value of talpaOtherResultsExplainerText to clarify results are not owned by the user’s library.',
			'sql' => [
				"ALTER TABLE talpa_settings MODIFY COLUMN talpaOtherResultsExplainerText VARCHAR(180) DEFAULT 'Talpa Search found these other results not owned by your library.'"
			]
		]

	];
}

 function addTemplateFromJson(&$update) {
	require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';

	$jsonFile = './web_builder/templates.json';
	if(file_exists($jsonFile)){
		$jsonData = file_get_contents($jsonFile);
		$jsonDecoded = json_decode($jsonData, true);
		$templates = $jsonDecoded['templates'];

		foreach($templates as $preMadeTemplate) {
			$template = new GrapesTemplate();
			$template->addTemplate($preMadeTemplate['templateName'], $preMadeTemplate['templateContent'], $preMadeTemplate['htmlData'] ?? '', $preMadeTemplate['cssData'] ?? '');
		}
		$update['success'] = true;
	}
}

function renameUploadedWBImages(&$update) : void {
	global $configArray;
	global $aspen_db;

	require_once ROOT_DIR . '/sys/File/ImageUpload.php';
	$imageUploadFilesUpdatedFull = 0;
	$imageUploadFilesUpdatedSmall = 0;
	$imageUploadFilesUpdatedMedium = 0;
	$imageUploadFilesUpdatedLarge = 0;
	$imageUploadFilesUpdatedXLarge = 0;
	$imageSizes = ['full', 'small', 'medium', 'large', 'xLarge'];

	$uploadedImage = new ImageUpload();
	$uploadedImage->find();
	while ($uploadedImage->fetch()){
		$imageId = $uploadedImage->id;
		$uploadedImagePattern = 'web_builder_image_' . $imageId;
		foreach($imageSizes as $imageSize){
			$sizePath = $imageSize."SizePath";
			if (substr($uploadedImage->$sizePath, 0, -4) != $uploadedImagePattern){
				if ($imageSize == 'xLarge') {
					$originalPath = substr($configArray['Site']['coverPath'], 0, -6)."uploads/web_builder_image/x-large/".$uploadedImage->fullSizePath;
				} else {
					$originalPath = substr($configArray['Site']['coverPath'], 0, -6)."uploads/web_builder_image/$imageSize/".$uploadedImage->fullSizePath;
				}
				$fileType = substr($uploadedImage->$sizePath, -3);
				$fileType = match($fileType){
					'gif' => ".gif",
					'png' => ".png",
					'svg' => ".svg",
					default => ".jpg",
				};
				if ($imageSize == 'xLarge') {
					$newPath = substr($configArray['Site']['coverPath'], 0, -6)."uploads/web_builder_image/x-large/web_builder_image_".$imageId.$fileType;
				} else {
					$newPath = substr($configArray['Site']['coverPath'], 0, -6)."uploads/web_builder_image/$imageSize/web_builder_image_".$imageId.$fileType;
				}
				$newImageName = "web_builder_image_".$imageId.$fileType;

				if (file_exists($originalPath) && !file_exists($newPath)) {
					rename($originalPath, $newPath);
				}

				$aspen_db->query("UPDATE image_uploads set $sizePath = '$newImageName' WHERE id=$imageId");
				switch ($imageSize) {
					case 'full':
						$imageUploadFilesUpdatedFull++;
						break;
					case 'small':
						$imageUploadFilesUpdatedSmall++;
						break;
					case 'medium':
						$imageUploadFilesUpdatedMedium++;
						break;
					case 'large':
						$imageUploadFilesUpdatedLarge++;
						break;
					case 'xLarge':
						$imageUploadFilesUpdatedXLarge++;
						break;
				}
			}
		}
	}
	$update['status'] = "Renamed $imageUploadFilesUpdatedFull full, $imageUploadFilesUpdatedSmall small, $imageUploadFilesUpdatedMedium medium, $imageUploadFilesUpdatedLarge large, and $imageUploadFilesUpdatedXLarge x-large image uploads so they will not conflict with other image uploads.";
	$update['success'] = true;
}

function renameUploadedWBFiles(&$update) : void {
	global $configArray;
	global $aspen_db;
	require_once ROOT_DIR . '/sys/File/FileUpload.php';
	$fileUploadsUpdated = 0;
	$fileThumbnailsUpdated = 0;

	$uploadedFile = new FileUpload();
	$uploadedFile->find();
	while ($uploadedFile->fetch()){
		$fileId = $uploadedFile->id;
		$fullPathLength = strlen(substr($configArray['Site']['coverPath'], 0, -6)."uploads/web_builder_pdf/");
		$fileUploaded = substr($uploadedFile->fullPath, $fullPathLength);
		$uploadedFilePattern = 'web_builder_pdf_'.$fileId.".pdf";
		if ($fileUploaded != $uploadedFilePattern) {
			$originalPath = $uploadedFile->fullPath;
			$newPath = substr($configArray['Site']['coverPath'], 0, -6)."uploads/web_builder_image/full/web_builder_pdf_".$fileId.".pdf";
			if (file_exists($originalPath) && !file_exists($newPath)) {
				rename($originalPath, $newPath);
			}

			$aspen_db->query("UPDATE file_uploads set fullPath = '$newPath' WHERE id=$fileId");
			$fileUploadsUpdated++;
		}
		//check for thumbnail as well
		if (!empty($uploadedFile->thumbFullPath)){
			$thumbUploaded = substr($uploadedFile->thumbFullPath, strrpos($uploadedFile->thumbFullPath, '/') + 1);
			$fileType = substr($uploadedFile->thumbFullPath, -3);
			$fileType = match($fileType){
				'gif' => ".gif",
				'png' => ".png",
				'svg' => ".svg",
				default => ".jpg",
			};
			$uploadedThumbPattern = 'web_builder_pdf_'.$fileId.$fileType;
			if ($thumbUploaded != $uploadedThumbPattern) {
				$originalThumbPath = $uploadedFile->thumbFullPath;
				$newThumbPath = $configArray['Site']['local'] . "/files/thumbnail/".$uploadedThumbPattern;

				$fileRenamed = false;
				if (file_exists($originalThumbPath) && !file_exists($newThumbPath)) {
					$fileRenamed = rename($originalThumbPath, $newThumbPath);
				}

				if ($fileRenamed) {
					$aspen_db->query("UPDATE file_uploads set thumbFullPath = '$newThumbPath' WHERE id=$fileId");
					$fileThumbnailsUpdated++;
				}
			}

		}
	}
	$update['status'] = "Renamed $fileUploadsUpdated file uploads and $fileThumbnailsUpdated thumbnails so they will not conflict with other file uploads.";
	$update['success'] = true;
}
function renameUploadedThemeImages(&$update) : void {
	global $aspen_db;
	global $configArray;
	require_once ROOT_DIR . '/sys/Theming/Theme.php';
	$themeImagesUpdated = 0;

	$theme = new Theme();
	$theme->find();
	while ($theme->fetch()){
		$themeId = $theme->id;
		$themeImagesToCheck = [
			['dbName' => 'logoName', 'prefix' => 'discovery_logo_'.$themeId],
			['dbName' => 'favicon', 'prefix' => 'favicon_'.$themeId],
			['dbName' => 'defaultCover', 'prefix' => 'default_cover_'.$themeId],
			['dbName' => 'headerBackgroundImage', 'prefix' => 'header_background_image_'.$themeId],
			['dbName' => 'footerLogo', 'prefix' => 'footer_logo_'.$themeId],
			['dbName' => 'logoApp', 'prefix' => 'logo_app_'.$themeId],
			['dbName' => 'headerLogoApp', 'prefix' => 'header_logo_app_'.$themeId],
			['dbName' => 'booksImage', 'prefix' => 'books_image_'.$themeId],
			['dbName' => 'booksImageSelected', 'prefix' => 'books_image_selected_'.$themeId],
			['dbName' => 'eBooksImage', 'prefix' => 'ebooks_image_'.$themeId],
			['dbName' => 'eBooksImageSelected', 'prefix' => 'ebooks_image_selected_'.$themeId],
			['dbName' => 'audioBooksImage', 'prefix' => 'audioBooks_image_'.$themeId],
			['dbName' => 'audioBooksImageSelected', 'prefix' => 'audioBooks_image_selected_'.$themeId],
			['dbName' => 'musicImage', 'prefix' => 'music_image_'.$themeId],
			['dbName' => 'musicImageSelected', 'prefix' => 'music_image_selected_'.$themeId],
			['dbName' => 'moviesImage', 'prefix' => 'movies_image_'.$themeId],
			['dbName' => 'moviesImageSelected', 'prefix' => 'movies_image_selected_'.$themeId],
			['dbName' => 'catalogImage', 'prefix' => 'catalog_image_'.$themeId],
			['dbName' => 'genealogyImage', 'prefix' => 'genealogy_image_'.$themeId],
			['dbName' => 'articlesDBImage', 'prefix' => 'articles_db_image_'.$themeId],
			['dbName' => 'eventsImage', 'prefix' => 'events_image_'.$themeId],
			['dbName' => 'listsImage', 'prefix' => 'lists_image_'.$themeId],
			['dbName' => 'seriesImage', 'prefix' => 'series_image_'.$themeId],
			['dbName' => 'libraryWebsiteImage', 'prefix' => 'library_website_image_'.$themeId],
			['dbName' => 'historyArchivesImage', 'prefix' => 'history_archives_image_'.$themeId],
		];

		foreach ($themeImagesToCheck as $image) {
			$dbName = $image['dbName'];
			if (!empty($theme->$dbName) && substr($theme->$dbName, 0, -4) != $image['prefix']) {
				$originalPath = $configArray['Site']['local'] . "/files/original/".$theme->$dbName;
				$originalThumbnailPath = $configArray['Site']['local'] . "/files/thumbnail/".$theme->$dbName;
				$fileType = substr($theme->$dbName, -3);
				$fileType = match($fileType){
					'gif' => ".gif",
					'png' => ".png",
					'svg' => ".svg",
					default => ".jpg",
				};
				$newPathOriginal = $configArray['Site']['local'] . "/files/original/".$image['prefix'].$fileType;
				$newPathThumbnail = $configArray['Site']['local'] . "/files/thumbnail/".$image['prefix'].$fileType;
				$newFileName = $image['prefix'].$fileType;
				$mainFileRenamed = false;
				if (file_exists($originalPath) && !file_exists($newPathOriginal)) {
					$mainFileRenamed = rename($originalPath, $newPathOriginal);
				}
				if (file_exists($originalThumbnailPath) && !file_exists($newPathThumbnail)) {
					rename($originalThumbnailPath, $newPathThumbnail);
				}

				if ($mainFileRenamed) {
					$aspen_db->query("UPDATE themes set $dbName = '$newFileName' WHERE id=$themeId");
					$themeImagesUpdated++;
				}
			}
		}
	}
	$update['status'] = "Renamed $themeImagesUpdated images in themes so they will not conflict with other image uploads.";
	$update['success'] = true;
}
function renameUploadedWebResourceAndPlacardImages(&$update) : void {
	global $aspen_db;
	global $configArray;

	//Web Resources
	require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
	$webResourceImagesUpdated = 0;
	$linkedPlacardsUpdated = 0;

	$webResource = new WebResource();
	$webResource->find();
	while ($webResource->fetch()){
		$resourceId = $webResource->id;
		$uploadedFilePattern = "web_resource_image_".$resourceId;

		if (!empty($webResource->logo) && substr($webResource->logo, 0, -4) != $uploadedFilePattern) {
			$originalPath =  $configArray['Site']['local'] . "/files/original/".$webResource->logo;
			$originalThumbnailPath = $configArray['Site']['local'] . "/files/thumbnail/".$webResource->logo;
			$fileType = substr($webResource->logo, -3);
			$fileType = match($fileType){
				'gif' => ".gif",
				'png' => ".png",
				'svg' => ".svg",
				default => ".jpg",
			};
			$newFileName = "web_resource_image_".$resourceId.$fileType;
			$newPathOriginal = $configArray['Site']['local'] . "/files/original/".$newFileName;
			$newPathThumbnail = $configArray['Site']['local'] . "/files/thumbnail/".$newFileName;

			$fileRenamed = false;
			if (file_exists($originalPath) && !file_exists($newPathOriginal)) {
				$fileRenamed = rename($originalPath, $newPathOriginal);
			}
			if (file_exists($originalThumbnailPath) && !file_exists($newPathThumbnail)) {
				rename($originalThumbnailPath, $newPathThumbnail);
			}

			if ($fileRenamed) {
				require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
				$linkedPlacard = new Placard();
				$linkedPlacard->sourceId = $resourceId;
				$linkedPlacard->sourceType = 'web_resource';
				if ($linkedPlacard->find(true)) {
					//check if linked placard is customized, if yes only update the image if it's using the same one as the web resource
					if (($linkedPlacard->isCustomized && $linkedPlacard->image = $webResource->logo) || !$linkedPlacard->isCustomized) {
						$aspen_db->query("UPDATE placards set image = '$newFileName' WHERE id=$linkedPlacard->id");
						$linkedPlacardsUpdated++;
					}
				}

				$aspen_db->query("UPDATE web_builder_resource set logo = '$newFileName' WHERE id=$resourceId");
				$webResourceImagesUpdated++;
			}

		}
	}

	//Placards
	require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
	$placardImagesUpdated = 0;

	$placard = new Placard();
	$placard->find();
	while ($placard->fetch()){
		$placardId = $placard->id;
		$uploadedFilePattern = "placard_image_".$placardId;

		if (!empty($placard->image) && substr($placard->image, 0, -4) != $uploadedFilePattern && !str_starts_with($placard->image, 'web_resource_image')) { //don't update if it's a linked web resource image
			$fileType = substr($placard->image, -3);
			$fileType = match($fileType){
				'gif' => ".gif",
				'png' => ".png",
				'svg' => ".svg",
				default => ".jpg",
			};
			$newFileName = "placard_image_".$placardId.$fileType;
			$originalPath =  $configArray['Site']['local'] . "/files/original/".$placard->image;
			$originalThumbnailPath = $configArray['Site']['local'] . "/files/thumbnail/".$placard->image;
			$newPathOriginal = $configArray['Site']['local'] . "/files/original/".$newFileName;
			$newPathThumbnail = $configArray['Site']['local'] . "/files/thumbnail/".$newFileName;
			$fileRenamed = false;
			if (file_exists($originalPath) && !file_exists($newPathOriginal)) {
				$fileRenamed = rename($originalPath, $newPathOriginal);
			}
			if (file_exists($originalThumbnailPath) && !file_exists($newPathThumbnail)) {
				rename($originalThumbnailPath, $newPathThumbnail);
			}

			if ($fileRenamed) {
				$aspen_db->query("UPDATE placards set image = '$newFileName' WHERE id=$placardId");
				$placardImagesUpdated++;
			}

		}
	}
	$update['status'] = "Renamed $webResourceImagesUpdated Web Resource image uploads and $linkedPlacardsUpdated linked placards so they will not conflict with other file uploads. ";
	$update['status'] .= "<br>Renamed $placardImagesUpdated Placard image uploads so they will not conflict with other file uploads.";
	$update['success'] = true;
}