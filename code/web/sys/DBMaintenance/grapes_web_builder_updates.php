<?php /** @noinspection SqlResolve */
function getGrapesWebBuilderUpdates() {
	return [
		'grapes_web_builder' => [
			'title' => 'Web Builder Basic Pages',
			'description' => 'Setup Basic Pages within Web Builder',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS grapes_web_builder (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(100) NOT NULL,
					urlAlias VARCHAR(100),
					teaser VARCHAR(512)
				) ENGINE=INNODB",
			],
		],
		'add_template_options' => [
			'title' => 'Add Template Options',
			'description' => 'Add Template Options for Grapes Pages',
			'sql' => [
				"ALTER TABLE grapes_web_builder ADD COLUMN IF NOT EXISTS pageType INT",
			],
		],
		'template_options_for_grapes_web_builder' => [
			'title' => 'Template Options for Grapes Web Builder',
			'description' => 'Store templates for Grapes Web Builder',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS template_options (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					templateName VARCHAR(100) NOT NULL,
					contents TEXT NOT NULL
				)ENGINE=INNODB",
			],
		],
		'templates_for_grapes_web_builder' => [
			'title' => 'Templates for Grapes Web Builder',
			'description' => 'Store templates for Grapes Web Builder',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS templates (
					id INT(11) AUTO_INCREMENT PRIMARY KEY,
					templateName VARCHAR(255) NOT NULL UNIQUE,
					templateDescription TEXT,
					templateFilePath VARCHAR(255) NOT NULL
				)ENGINE=INNODB",
			],
		],
		'alter_template_option_type_remove_and_re_add' => [
			'title' => 'Alter Type of Template Options',
			'description' => 'Alter Template Options for Grapes Pages',
			'sql' => [
				"ALTER TABLE grapes_web_builder DROP COLUMN pageType",
			],
		],
		're_add_page_type_with_new_data_type' => [
			'title' => 'Alter Type of Template Options',
			'description' => 'Alter Template Options for Grapes Pages',
			'sql' => [
				"ALTER TABLE grapes_web_builder ADD COLUMN pageType VARCHAR(512)",
			],
		],
		'alter_templates_table_remove_description' => [
			'title' => 'Remove Description From Templates Table',
			'description' => 'Remove the description column from the template table',
			'sql' => [
				"ALTER TABLE templates DROP COLUMN IF EXISTS templateDescription",
			],
		],
		'alter_temapltes_table_add_content' => [
			'title' => 'Add Content to Templates Table',
			'description' => 'Add content column to templates table',
			'sql' => [
				"ALTER TABLE templates ADD COLUMN IF NOT EXISTS templateContent TEXT",
			],
		],
		'add_default_for_template_file_path' => [
			'title' => 'Add Default to Template File Path',
			'description' => 'Add default value to template file path in templates table',
			'sql' => [
				"ALTER TABLE templates MODIFY COLUMN templateFilePath VARCHAR(255) DEFAULT NULL",
			],
		],
		'add_template_content_column_to_the_grapes_page_table' => [
			'title' => 'Add Template Content Column to Grapes Table',
			'description' => 'Add a column to the Grapes table to store the content of the chosen template',
			'sql' => [
				'ALTER TABLE grapes_web_builder ADD COLUMN IF NOT EXISTS templateContent TEXT',
			],
		],
		'alter_contents_of_grapes_page_table' => [
			'title' => 'Alter the contents of the Grapes page table',
			'description' => 'Remove columns pageType and templateContent from the Grapes table',
			'sql' => [
				'ALTER TABLE grapes_web_builder DROP COLUMN pageType',
				'ALTER TABLE grapes_web_builder DROP COLUMN templateContent',
				'ALTER TABLE grapes_web_builder ADD COLUMN templateId INT(11) DEFAULT -1',
			],
		],
		'rename_templateId_to_tempalte_names_and_add_new_temaplate_id_column' => [
			'title' => 'Modify a column and add a new column',
			'description' => 'Add a new column for template names and modify the templateID column to alter its purpose.',
			'sql' => [
				'ALTER TABLE grapes_web_builder ADD COLUMN IF NOT EXISTS templateNames INT(11) DEFAULT -1',
				'ALTER TABLE grapes_web_builder MODIFY COLUMN templateId VARCHAR(250) UNIQUE',
			],
		],
		'add_templateId_column_to_templates_table' => [
			'title' => 'Add templateId column to templates table',
			'description' => 'Add a new column to store the templateId in the templates table',
			'sql' => [
				'ALTER TABLE templates ADD COLUMN templateId VARCHAR(250) UNIQUE',
			],
		],
		'change_template_name_data_type' => [
			'title' => 'Change Template Name Data Type',
			'description' => 'Change template name data type to varchar',
			'sql' => [
				'ALTER TABLE grapes_web_builder MODIFY COLUMN templateNames INT(11)',
			],
		],
		'modify_template_name_column' => [
			'title' => 'Change Template Column',
			'description' => 'Change template name column to not allow NULL',
			'sql' => [
				'ALTER TABLE grapes_web_builder MODIFY COLUMN templateNames INT(11) NOT NULL',
			],
		],
		'modify_template_name_column_add_default' => [
			'title' => 'Change Template Column',
			'description' => 'Change template name column to add default',
			'sql' => [
				'ALTER TABLE grapes_web_builder MODIFY COLUMN templateNames INT(11) DEFAULT -1',
			],
		],
		'add_new_template_column' => [
			'title' => 'Add New Template Column',
			'description' => 'Add  template column to grapes table',
			'sql' => [
				'ALTER TABLE grapes_web_builder ADD COLUMN IF NOT EXISTS templatesSelect INT(11) DEFAULT -1',
			],
		],
		'add_template_content_to_grapes_web_builder' => [
			'title' => 'add_column_for_template_content',
			'description' => 'add_column_in_grapes_web_builder_table_for_template_content',
			'sql' => [
				'ALTER TABLE grapes_web_builder ADD COLUMN templateContent TEXT',
			],
		],
		'remove_temaplteID_column' => [
			'title' => 'Remove templateId column from templates table',
			'description' => 'Remove tempalteId column from templates table',
			'sql' => [
				'ALTER TABLE templates DROP COLUMN templateId',
			],
		],
		'delete_column_from_grapes_web_builder' => [
			'title' => 'Delete column from grapes_web_builder table',
			'description' => 'Delte templateId from grapes_web_builder table',
			'sql' => [
				'ALTER TABLE grapes_web_builder DROP COLUMN templateId',
			],
		],
		'alterations_to_templates_table' => [
			'title' => 'Alterations to Templates Table',
			'description' => 'Make changes to templates table to handle addition of templates built in grapes editor',
			'sql' => [
				'ALTER TABLE templates ADD COLUMN htmlData TEXT NOT NULL DEFAULT " "',
				'ALTER TABLE templates ADD COLUMN cssData TEXT NOT NULL DEFAULT " "',
				'ALTER TABLE templates ADD COLUMN assets TEXT NOT NULL DEFAULT "[]"',
				'ALTER TABLE templates ADD COLUMN components TEXT NOT NULL DEFAULT "[]"',
				'ALTER TABLE templates ADD COLUMN styles TEXT NOT NULL DEFAULT "[]"',
			],
		],
		'add_id_grapes_page_id_column_to_template_table' => [
			'title' => 'Add grapes_page_id Column to Template Table',
			'description' => 'Add a column to the tempaltes table to store the id given by grapes',
			'sql' => [
				'ALTER TABLE templates ADD COLUMN grapes_page_id VARCHAR(100) NOT NULL DEFAULT " "',
			],
		],
		'modify_templateName_column_in_templates' => [
			'title' => 'Modify Tempalte Name Column in Templates',
			'description' => 'Modify templateName column in templates to have a default',
			'sql' => [
				"ALTER TABLE templates MODIFY COLUMN templateName VARCHAR(100) NOT NULL DEFAULT ' '",
				"ALTER TABLE templates MODIFY COLUMN templateContent TEXT NOT NULL DEFAULT ' '",

			],
		],
		'add_grapesPageId_to_grapes_web_builder' => [
			'title' => 'Add a Column to Store Page ID',
			'description' => 'Add a column to the grapes_web_builder table',
			'sql' => [
				'ALTER TABLE grapes_web_builder ADD COLUMN IF NOT EXISTS grapesGenId VARCHAR(100) Not NULL DEFAULT ""',
			],
		],
		'grapes_page_web_builder_scope_by_library' => [
			'title' => 'Web Builder Grapes Page Scope By Library',
			'description' => 'Add the ability to scope Grapes Pages By Library',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS library_web_builder_grapes_page (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL,
					grapesPageId INT(11) NOT NULL,
					INDEX libraryId(libraryId),
					INDEX grapesPageId(grapesPageId)
				) ENGINE INNODB',
			],
		],
		'add_html_and_css_columns_to_grapes_web_builder' => [
			'title' => 'Add columns to Grapes Web Builder Table',
			'description' => 'Add columns to Grapes Web Builder Table',
			'sql' => [
				"ALTER TABLE grapes_web_builder ADD COLUMN IF NOT EXISTS htmlData TEXT NOT NULL DEFAULT ' '",
				"ALTER TABLE grapes_web_builder ADD COLUMN IF NOT EXISTS cssData TEXT NOT NULL DEFAULT ' '",
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
	];
}
	
