<?php /** @noinspection SqlResolve */
function getGrapesWebBuilderUpdates() {
	return [
		'grapes_web_builder' => [
			'title' => 'Web Builder Basic Grapes JS Pages',
			'description' => 'Setup Basic Grapes JS Pages within Web Builder',
			'continueOnError' => true,
			'sql' => [
				"DROP TABLE IF EXISTS grapes_web_builder",
				"CREATE TABLE IF NOT EXISTS grapes_web_builder (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100) NOT NULL,
					urlAlias VARCHAR(100),
					teaser VARCHAR(512),
					templatesSelect INT(11) DEFAULT -1,
					templateContent TEXT,
					grapesGenId VARCHAR(100) NOT NULL DEFAULT '',
					htmlData TEXT,
					cssData TEXT
				) ENGINE=INNODB",
			],
		],
		'templates_for_grapes_web_builder' => [
			'title' => 'Templates for Grapes Web Builder',
			'description' => 'Store templates for Grapes Web Builder',
			'continueOnError' => true,
			'sql' => [
				"DROP TABLE IF EXISTS templates",
				"CREATE TABLE IF NOT EXISTS grapes_templates (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					templateName VARCHAR(100) NOT NULL DEFAULT ' ',
					templateContent TEXT NOT NULL,
					htmlData TEXT,
					cssData TEXT
				)ENGINE=INNODB",
			],
		],
		'grapes_page_web_builder_scope_by_library' => [
			'title' => 'Web Builder Grapes Page Scope By Library',
			'description' => 'Add the ability to scope Grapes Pages By Library',
			'continueOnError' => true,
			'sql' => [
				"DROP TABLE IF EXISTS library_web_builder_grapes_page",
				'CREATE TABLE IF NOT EXISTS library_web_builder_grapes_page (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					grapesPageId INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX grapesPageId(grapesPageId)
				) ENGINE INNODB',
			],
		],
		'grapes_js_web_builder_roles' => [
			'title' => 'Grapes JS Web Builder Roles and Permissions',
			'description' => 'Setup roles and permissions for the Grapes JS Web Builder Pages',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
				('Web Builder', 'Administer All Grapes Pages', 'Web Builder', 150, 'Allows the user to define grapes pages for all libraries.'),
				('Web Builder', 'Administer Library Grapes Pages', 'Web Builder', 151, 'Allows the user to define grapes pages for their home library.')
				",
			],
		],
		'grapes_js_web_builder_roles_for_permissions' => [
			'title' => 'Grapes JS Web Builder Roles',
			'description' => 'Setup roles for Grapes Js Pages',
			'sql' => [
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Grapes Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Web Admin'), (SELECT id from permissions where name='Administer All Grapes Pages'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='Library Web Admin'), (SELECT id from permissions where name='Administer Library Grapes Pages'))"
			],
		],
		'add_switch_for_grapes_editor' => [
			'title' => 'Add Switch for Grapes Editor',
			'description' => 'Add Switch for Grapes Editor',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN enableGrapesEditor TINYINT(1) DEFAULT 0"
			],
		],
		'add_blank_tempalte_to_grapes_templates' => [
			'title' => 'Add Blank Tempalte to Grapes Templates',
			'description' => 'Add blank template to grapes templates',
			'sql' => [
				"INSERT INTO grapes_templates (templateName, templateContent) VALUES ('No Template', ' ')",
			],
		],
   ];
}