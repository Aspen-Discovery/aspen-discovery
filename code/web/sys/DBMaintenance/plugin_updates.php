<?php
/**@noinspection SqlResolve*/
function getPluginUpdates() {
	return [
		'create_plugin_table' => [
			'title' => 'Create Plugin Table',
			'description' => 'Create the plugin table for storing plugin information and configuration',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE plugin (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					slug VARCHAR(50) NOT NULL UNIQUE,
					version VARCHAR(20) NOT NULL,
					description TEXT,
					author VARCHAR(100),
					status TINYINT(1) DEFAULT 0 COMMENT '0 = disabled, 1 = enabled',
					installDate INT(11),
					updateDate INT(11),
					configData LONGTEXT COMMENT 'JSON configuration data',
					hasJsInjection TINYINT(1) DEFAULT 0,
					jsFiles LONGTEXT COMMENT 'JSON array of JS files',
					cssFiles LONGTEXT COMMENT 'JSON array of CSS files',
					hookPoints LONGTEXT COMMENT 'JSON array of hook points',
					INDEX idx_plugin_slug (slug),
					INDEX idx_plugin_status (status)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
			]
		], //create_plugin_table

		'create_plugin_permission' => [
			'title' => 'Create Plugin Administration Permission',
			'description' => 'Add permission to administer plugins',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Administer Plugins', '', 80, 'Controls if the user can install, uninstall, enable and disable plugins.')"
			]
		], //create_plugin_permission
	];
} 