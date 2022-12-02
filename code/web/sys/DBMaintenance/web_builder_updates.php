<?php /** @noinspection SqlResolve */
function getWebBuilderUpdates() {
	return [
		'web_builder_module' => [
			'title' => 'Web Builder Module',
			'description' => 'Create Web Builder Module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Web Builder', 'web_builder', '')",
			],
		],

		'web_builder_module_monitoring_and_indexing' => [
			'title' => 'Web Builder Module - Monitoring, indexing',
			'description' => 'Update Web Builder module to monitor logs and start indexer',
			'sql' => [
				"UPDATE modules set backgroundProcess='web_indexer', logClassPath='/sys/WebsiteIndexing/WebsiteIndexLogEntry.php', logClassName='WebsiteIndexLogEntry' WHERE name = 'Web Builder'",
			],
		],

		'web_builder_basic_pages' => [
			'title' => 'Web Builder Basic Pages',
			'description' => 'Setup Basic Pages within Web Builder',
			'sql' => [
				"CREATE TABLE web_builder_basic_page  (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100) NOT NULL,
					urlAlias VARCHAR(100),
					showSidebar TINYINT(1),
					contents MEDIUMTEXT
				) ENGINE=INNODB",
			],
		],

		'web_builder_basic_page_teaser' => [
			'title' => 'Web Builder Basic Page Teaser',
			'description' => 'Add Teaser to Basic Page',
			'sql' => [
				'ALTER TABLE web_builder_basic_page ADD COLUMN teaser VARCHAR(512)',
			],
		],

		'web_builder_menu' => [
			'title' => 'Web Builder Menu',
			'description' => 'Setup Menu for the Web Builder',
			'sql' => [
				"CREATE TABLE web_builder_menu (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					label VARCHAR(50) NOT NULL,
					parentMenuId INT(11) DEFAULT -1,
					url VARCHAR(255),
					INDEX (parentMenuId)
				) ENGINE=INNODB",
			],
		],

		'web_builder_menu_sorting' => [
			'title' => 'Web Builder Menu Sorting',
			'description' => 'Add a weight to the Web Builder Menu',
			'sql' => [
				"ALTER TABLE web_builder_menu ADD COLUMN weight INT DEFAULT 0",
			],
		],

		'web_builder_menu_show_when' => [
			'title' => 'Web Builder Menu Show When',
			'description' => 'Add a showWhen to the Web Builder Menu',
			'sql' => [
				"ALTER TABLE web_builder_menu ADD COLUMN showWhen TINYINT DEFAULT 0",
			],
		],

		'staff_members' => [
			'title' => 'Staff Members',
			'description' => 'Add staff members so we can automatically display a directory',
			'sql' => [
				"CREATE TABLE staff_members (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100),
					role VARCHAR(100),
					email VARCHAR(255),
					phone VARCHAR(13),
					libraryId INT(11),
					photo VARCHAR(255),
					description MEDIUMTEXT
				) ENGINE INNODB",
			],
		],

		'web_builder_portal' => [
			'title' => 'Web Builder Portal',
			'description' => 'Setup tables to create portal pages',
			'sql' => [
				'CREATE TABLE web_builder_portal_page (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(255),
					urlAlias VARCHAR(100),
					showSidebar TINYINT(1)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_portal_row(
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					portalPageId INT(11),
					rowTitle VARCHAR(255),
					INDEX (portalPageId)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_portal_cell(
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					portalRowId INT(11),
					widthTiny INT,
					widthXs INT,
					widthSm INT,
					widthMd INT,
					widthLg INT,
					horizontalJustification VARCHAR(20),
					verticalAlignment VARCHAR(20),
					sourceType VARCHAR(30),
					sourceId VARCHAR(30),
					INDEX (portalRowId)
				)',
			],
		],
		'web_builder_portal_weights' => [
			'title' => 'Web Builder Portal Weights',
			'description' => 'Add weights to Portal Rows and cells',
			'sql' => [
				'ALTER TABLE web_builder_portal_row ADD COLUMN weight INT DEFAULT 0',
				'ALTER TABLE web_builder_portal_cell ADD COLUMN weight INT DEFAULT 0',
			],
		],

		'web_builder_portal_cell_title' => [
			'title' => 'Web Builder Portal Cell Title',
			'description' => 'Add title to web builder portal cell',
			'sql' => [
				"ALTER TABLE web_builder_portal_cell ADD COLUMN title VARCHAR(255) DEFAULT ''",
			],
		],

		'web_builder_image_upload' => [
			'title' => 'Create Image Uploads Table',
			'description' => 'Create Image Uploads Table to store information about images that have been uploaded to Aspen',
			'sql' => [
				'CREATE TABLE image_uploads (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(255) NOT NULL,
					fullSizePath VARCHAR(512) NOT NULL,
					generateMediumSize TINYINT(1) NOT NULL default 0,
					mediumSizePath VARCHAR(512),
					generateSmallSize TINYINT(1) NOT NULL default 0,
					smallSizePath VARCHAR(512),
					type VARCHAR(25) NOT NULL,
					INDEX (type, title)
				) ENGINE INNODB',
			],
		],

		'web_builder_image_upload_additional_sizes' => [
			'title' => 'Add additional derivative sizes for image uploads',
			'description' => 'Add additional derivative sizes for image uploads',
			'sql' => [
				'ALTER TABLE image_uploads  add COLUMN generateLargeSize TINYINT(1) NOT NULL default 1',
				"ALTER TABLE image_uploads  add COLUMN largeSizePath VARCHAR(512) DEFAULT ''",
				"ALTER TABLE image_uploads  add COLUMN generateXLargeSize TINYINT(1) NOT NULL default 1",
				"ALTER TABLE image_uploads  add COLUMN xLargeSizePath VARCHAR(512) DEFAULT ''",
			],
		],

		'web_builder_resources' => [
			'title' => 'Create Web Builder Resources Table',
			'description' => 'Create Resources table to store information about resources the library provides access to',
			'sql' => [
				'CREATE TABLE web_builder_resource (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					url VARCHAR(255) NOT NULL,
					logo VARCHAR(200),
					featured TINYINT(1) NOT NULL default 0,
					category VARCHAR(100),
					requiresLibraryCard TINYINT(1) NOT NULL default 0,
					description MEDIUMTEXT,
					INDEX (featured),
					INDEX (category)
				) ENGINE INNODB',
			],
		],

		'web_builder_resource_teaser' => [
			'title' => 'Add teaser to web builder resources',
			'description' => 'Add teaser to web builder resources for display in search results',
			'sql' => [
				'ALTER TABLE web_builder_resource ADD COLUMN teaser VARCHAR(512)',
			],
		],

		'web_builder_scope_by_library' => [
			'title' => 'Web Builder add Library Scoping',
			'description' => 'Add the ability to scope web builder content',
			'sql' => [
				'CREATE TABLE library_web_builder_resource (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					webResourceId  INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX webResourceId(webResourceId)
				) ENGINE INNODB',
				'CREATE TABLE library_web_builder_basic_page (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					basicPageId  INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX basicPageId(basicPageId)
				) ENGINE INNODB',
				'ALTER TABLE web_builder_menu ADD COLUMN libraryId INT(11)',
			],
		],

		'web_builder_last_update_timestamps' => [
			'title' => 'Web Builder Add Last Update Times',
			'description' => 'Add additional fields to resources to make indexing easier',
			'sql' => [
				'ALTER TABLE web_builder_resource ADD COLUMN lastUpdate INT(11) DEFAULT 0',
				'ALTER TABLE web_builder_basic_page ADD COLUMN lastUpdate INT(11) DEFAULT 0',
			],
		],

		'web_builder_categories_and_audiences' => [
			'title' => 'Web Builder Categories and Audiences',
			'description' => 'Setup categories and audiences for web content',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE web_builder_audience (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) UNIQUE
				) ENGINE INNODB',
				'CREATE TABLE web_builder_category (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) UNIQUE
				) ENGINE INNODB',
				"INSERT INTO web_builder_audience (name) VALUES ('Adults')",
				"INSERT INTO web_builder_audience (name) VALUES ('Teens')",
				"INSERT INTO web_builder_audience (name) VALUES ('Tweens')",
				"INSERT INTO web_builder_audience (name) VALUES ('Children')",
				"INSERT INTO web_builder_audience (name) VALUES ('Parents')",
				"INSERT INTO web_builder_audience (name) VALUES ('Seniors')",
				"INSERT INTO web_builder_audience (name) VALUES ('Everyone')",
				"INSERT INTO web_builder_category (name) VALUES ('eBooks and Audiobooks')",
				"INSERT INTO web_builder_category (name) VALUES ('Languages and Culture')",
				"INSERT INTO web_builder_category (name) VALUES ('Lifelong Learning')",
				"INSERT INTO web_builder_category (name) VALUES ('Newspapers and Magazines')",
				"INSERT INTO web_builder_category (name) VALUES ('Reading Recommendations')",
				"INSERT INTO web_builder_category (name) VALUES ('Reference and Research')",
				"INSERT INTO web_builder_category (name) VALUES ('Video Streaming')",
				"INSERT INTO web_builder_category (name) VALUES ('Local History')",
				"INSERT INTO web_builder_category (name) VALUES ('Homework Help')",
				"INSERT INTO web_builder_category (name) VALUES ('Arts and Music')",
				"INSERT INTO web_builder_category (name) VALUES ('Library Documents and Policies')",
				'CREATE TABLE web_builder_basic_page_audience (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					basicPageId INT(11) NOT NULL, 
					audienceId INT(11) NOT NULL,
					UNIQUE INDEX (basicPageId, audienceId)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_basic_page_category (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					basicPageId INT(11) NOT NULL, 
					categoryId INT(11) NOT NULL,
					UNIQUE INDEX (basicPageId, categoryId)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_resource_audience (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					webResourceId INT(11) NOT NULL, 
					audienceId INT(11) NOT NULL,
					UNIQUE INDEX (webResourceId, audienceId)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_resource_category (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					webResourceId INT(11) NOT NULL, 
					categoryId INT(11) NOT NULL,
					UNIQUE INDEX (webResourceId, categoryId)
				) ENGINE INNODB',
				'ALTER TABLE web_builder_resource DROP COLUMN category',
			],
		],

		'web_builder_portal_cell_markdown' => [
			'title' => 'Web Builder Portal Cell Markdown',
			'description' => 'Allow Portal Cells to contain markdown',
			'sql' => [
				'ALTER TABLE web_builder_portal_cell ADD column markdown MEDIUMTEXT',
			],
		],

		'web_builder_portal_cell_source_info' => [
			'title' => 'Web Builder Portal Cell Source Info',
			'description' => 'Add additional info for a portal cell to include things like YouTube Videos',
			'sql' => [
				'ALTER TABLE web_builder_portal_cell ADD column sourceInfo VARCHAR(512)',
			],
		],

		'web_builder_resource_in_library' => [
			'title' => 'Web Builder add inLibraryUseOnly to Resources',
			'description' => 'Add in library use only flag to web resources',
			'sql' => [
				'ALTER TABLE web_builder_resource ADD COLUMN inLibraryUseOnly TINYINT(1) DEFAULT 0',
			],
		],

		'web_builder_resource_open_in_new_tab' => [
			'title' => 'Web Builder add openInNewTab to Resources',
			'description' => 'Add open in new window flag to web resources',
			'sql' => [
				'ALTER TABLE web_builder_resource ADD COLUMN openInNewTab TINYINT(1) DEFAULT 0',
			],
		],

		'web_builder_custom_forms' => [
			'title' => 'Web Builder Custom Forms',
			'description' => 'Add the ability for a library to define custom forms',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE web_builder_custom_form (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100) NOT NULL,
					urlAlias VARCHAR(100),
					emailResultsTo VARCHAR(100),
					requireLogin TINYINT(1),
					introText MEDIUMTEXT,
					submissionResultText MEDIUMTEXT
				) ENGINE INNODB',
				'CREATE TABLE web_builder_custom_form_field (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					formId INT(11) NOT NULL,
					weight INT DEFAULT 0,
					label VARCHAR(100) NOT NULL,
					description VARCHAR(255) default \'\',
					fieldType INT NOT NULL default 0,
					enumValues VARCHAR(255),
					defaultValue VARCHAR(255),
					required TINYINT(1) NOT NULL DEFAULT 0,
					INDEX formId(formId)
				) ENGINE INNODB',
				'CREATE TABLE library_web_builder_custom_form (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					formId  INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX formId(formId)
				) ENGINE INNODB',
				'CREATE TABLE web_builder_custom_from_submission (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					formId INT(11) NOT NULL,
					libraryId INT(11) NOT NULL,
					userId INT(11) NOT NULL, 
					dateSubmitted INT(11) NOT NULL,
					submission MEDIUMTEXT,
					INDEX (formId, libraryId)
				) ENGINE INNODB',
			],
		],

		'web_builder_custom_page_categories' => [
			'title' => 'Web Builder - Custom Page categories',
			'description' => 'Add audience and categories to custom pages',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE web_builder_portal_page ADD COLUMN lastUpdate INT(11) DEFAULT 0',
				'CREATE TABLE IF NOT EXISTS web_builder_portal_page_audience (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					portalPageId INT(11) NOT NULL, 
					audienceId INT(11) NOT NULL,
					UNIQUE INDEX (portalPageId, audienceId)
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS web_builder_portal_page_category (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					portalPageId INT(11) NOT NULL, 
					categoryId INT(11) NOT NULL,
					UNIQUE INDEX (portalPageId, categoryId)
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS library_web_builder_portal_page (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					portalPageId  INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX portalPageId(portalPageId)
				) ENGINE INNODB',
			],
		],

		'web_builder_roles' => [
			'title' => 'Web Builder Roles and Permissions',
			'description' => 'Setup roles and permissions for the Web Builder',
			'sql' => [
				"INSERT INTO roles (name, description) VALUES 
					('Web Admin', 'Allows the user to administer web content for all libraries'),
					('Library Web Admin', 'Allows the user to administer web content for their library')
					",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Web Builder', 'Administer All Menus', 'Web Builder', 0, 'Allows the user to define the menu for all libraries.'),
					('Web Builder', 'Administer Library Menus', 'Web Builder', 1, 'Allows the user to define the menu for their home library.'),
					('Web Builder', 'Administer All Basic Pages', 'Web Builder', 10, 'Allows the user to define basic pages for all libraries.'),
					('Web Builder', 'Administer Library Basic Pages', 'Web Builder', 11, 'Allows the user to define basic pages for their home library.'),
					('Web Builder', 'Administer All Custom Pages', 'Web Builder', 20, 'Allows the user to define custom pages for all libraries.'),
					('Web Builder', 'Administer Library Custom Pages', 'Web Builder', 21, 'Allows the user to define custom pages for their home library.'),
					('Web Builder', 'Administer All Custom Forms', 'Web Builder', 30, 'Allows the user to define custom forms for all libraries.'),
					('Web Builder', 'Administer Library Custom Forms', 'Web Builder', 31, 'Allows the user to define custom forms for their home library.'),
					('Web Builder', 'Administer All Web Resources', 'Web Builder', 40, 'Allows the user to add web resources for all libraries.'),
					('Web Builder', 'Administer Library Web Resources', 'Web Builder', 41, 'Allows the user to add web resources for their home library.'),
					('Web Builder', 'Administer All Staff Members', 'Web Builder', 50, 'Allows the user to add staff members for all libraries.'),
					('Web Builder', 'Administer Library Staff Members', 'Web Builder', 51, 'Allows the user to add staff members for their home library.'),
					('Web Builder', 'Administer All Web Content', 'Web Builder', 60, 'Allows the user to add images, pdfs, and videos.'),
					('Web Builder', 'Administer All Web Categories', 'Web Builder', 70, 'Allows the user to define audiences and categories for content.')
					",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Menus'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Basic Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Custom Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Custom Forms'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Web Resources'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Staff Members'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Web Content'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Web Categories'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Menus'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Basic Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Custom Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Custom Forms'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Web Resources'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Staff Members'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Web Content'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Web Categories'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Menus'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Basic Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Custom Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Custom Forms'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Web Resources'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Staff Members'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer All Web Content'))",
			],
		],

		'web_builder_remove_show_sidebar' => [
			'title' => 'Web Builder Remove Show Sidebar',
			'description' => 'Remove Show Sidebar from Web Builder Pages',
			'sql' => [
				'ALTER TABLE web_builder_portal_page DROP COLUMN showSidebar',
				'ALTER TABLE web_builder_basic_page DROP COLUMN showSidebar',
			],
		],

		'web_builder_add_frameHeight' => [
			'title' => 'Web Builder add frame height for iframe cell type',
			'description' => 'Add frameHeight for iframes from Web Builder Pages',
			'sql' => [
				'ALTER TABLE web_builder_portal_cell ADD COLUMN frameHeight INT DEFAULT 0',
			],
		],

		'web_builder_add_cell_makeCellAccordion' => [
			'title' => 'Web Builder add makeCellAccordion for cell layout options',
			'description' => 'Add makeCellAccordion for layout settings in a cell for Web Builder Pages',
			'sql' => [
				'ALTER TABLE web_builder_portal_cell ADD COLUMN makeCellAccordion TINYINT NOT NULL DEFAULT 0',
			],
		],

		'web_builder_add_cell_imageURL' => [
			'title' => 'Add Image URL to Web Builder custom page options',
			'description' => 'Add Image URL field to add links to image Web Builder cell types',
			'sql' => [
				'ALTER TABLE web_builder_portal_cell ADD COLUMN imageURL VARCHAR(255)',
			],
		],
	];
}