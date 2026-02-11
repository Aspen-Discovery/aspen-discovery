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
					status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = disabled, 1 = enabled',
					updateDate INT(11),
					configData LONGTEXT COMMENT 'JSON configuration data',
					modifiedDate VARCHAR(20) COMMENT 'When plugin was last modified (from metadata)',
					minAspenVersion VARCHAR(20) COMMENT 'Minimum required Aspen Discovery version',
					maxAspenVersion VARCHAR(20) COMMENT 'Maximum supported Aspen Discovery version',
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

		'create_plugin_data_table' => [
			'title' => 'Create Plugin Data Table',
			'description' => 'Create the plugin_data table for flexible key-value storage of plugin operational data',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS plugin_data (
					plugin_class VARCHAR(255) NOT NULL COMMENT 'Fully qualified plugin class name',
					plugin_key VARCHAR(255) NOT NULL COMMENT 'Key for the data value',
					plugin_value MEDIUMTEXT DEFAULT NULL COMMENT 'Data value - supports up to 16MB per value',
					created BIGINT UNSIGNED DEFAULT NULL COMMENT 'Unix timestamp when record was created',
					updated BIGINT UNSIGNED DEFAULT NULL COMMENT 'Unix timestamp when record was last updated',
					PRIMARY KEY (plugin_class(191), plugin_key(191)),
					INDEX idx_plugin_class (plugin_class(191))
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
				COMMENT='Flexible key-value storage for plugin data'"
			]
		], //create_plugin_data_table

		'create_plugin_usage_permission' => [
			'title' => 'Create Plugin Usage Permission',
			'description' => 'Add permission for using plugin tools and reports',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Use Plugins', '', 81, 'Controls if the user can use plugin tools and reports.')"
			]
		], //create_plugin_usage_permission

		'create_plugins_module' => [
			'title' => 'Create Plugins Module',
			'description' => 'Add Plugins as a module that can be enabled/disabled',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Plugins', '', '')",
				"UPDATE permissions SET sectionName = 'Plugins', requiredModule = 'Plugins' WHERE name IN ('Administer Plugins', 'Use Plugins')"
			]
		], //create_plugins_module
	];
}