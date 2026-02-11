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

		'update_plugin_table_add_version_fields' => [
			'title' => 'Update Plugin Table - Add Version and Modified Date Fields',
			'description' => 'Add modifiedDate, minAspenVersion, and maxAspenVersion fields to plugin table',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE plugin ADD COLUMN modifiedDate VARCHAR(20) COMMENT 'When plugin was last modified (from metadata)'",
				"ALTER TABLE plugin ADD COLUMN minAspenVersion VARCHAR(20) COMMENT 'Minimum required Aspen Discovery version'",
				"ALTER TABLE plugin ADD COLUMN maxAspenVersion VARCHAR(20) COMMENT 'Maximum supported Aspen Discovery version'"
			]
		], //update_plugin_table_add_version_fields

		'update_plugin_table_remove_redundant_columns' => [
			'title' => 'Update Plugin Table - Remove Redundant Columns',
			'description' => 'Remove unnecessary columns from the plugin table',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE plugin DROP COLUMN hasJsInjection",
				"ALTER TABLE plugin DROP COLUMN jsFiles",
				"ALTER TABLE plugin DROP COLUMN cssFiles",
				"ALTER TABLE plugin DROP COLUMN hookPoints",
				"ALTER TABLE plugin DROP COLUMN installDate"
			]
		], //update_plugin_table_remove_redundant_columns

		'update_plugin_status_defaults' => [
			'title' => 'Update Plugin Status Column Defaults',
			'description' => 'Ensure plugin status column has proper defaults and no null values',
			'continueOnError' => false,
			'sql' => [
				"UPDATE plugin SET status = 0 WHERE status IS NULL",
				"ALTER TABLE plugin MODIFY COLUMN status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = disabled, 1 = enabled'"
			]
		], //update_plugin_status_defaults

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

		'create_plugin_methods_table' => [
			'title' => 'Create Plugin Methods Table',
			'description' => 'Create the plugin_methods table for registry of methods implemented by each plugin',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS plugin_methods (
					plugin_class VARCHAR(255) NOT NULL COMMENT 'Fully qualified plugin class name',
					plugin_method VARCHAR(255) NOT NULL COMMENT 'Method name in the plugin class',
					method_type ENUM('lifecycle', 'page', 'hook', 'api') DEFAULT 'hook' COMMENT 'Type of method for categorization',
					created BIGINT UNSIGNED DEFAULT NULL COMMENT 'Unix timestamp when method was registered',
					PRIMARY KEY (plugin_class(191), plugin_method(191)),
					INDEX idx_method (plugin_method(191)),
					INDEX idx_type (method_type)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
				COMMENT='Registry of methods implemented by each plugin'"
			]
		], //create_plugin_methods_table
	];
}

function update_plugin_table_add_version_fields() {
	/** @var PDO $aspen_db */
	global $aspen_db;
	
	if ($aspen_db == null) {
		return ['success' => false, 'message' => 'No database connection'];
	}
	
	try {
		// Check if columns already exist
		$stmt = $aspen_db->prepare("SHOW COLUMNS FROM plugin WHERE Field = 'modifiedDate'");
		$stmt->execute();
		$modifiedDateExists = $stmt->fetch() !== false;
		
		$stmt = $aspen_db->prepare("SHOW COLUMNS FROM plugin WHERE Field = 'minAspenVersion'");
		$stmt->execute();
		$minVersionExists = $stmt->fetch() !== false;
		
		$stmt = $aspen_db->prepare("SHOW COLUMNS FROM plugin WHERE Field = 'maxAspenVersion'");
		$stmt->execute();
		$maxVersionExists = $stmt->fetch() !== false;
		
		// Add new columns if they don't exist
		if (!$modifiedDateExists) {
			$aspen_db->exec("ALTER TABLE plugin ADD COLUMN modifiedDate VARCHAR(20) DEFAULT NULL COMMENT 'Plugin modification date from metadata'");
		}
		
		if (!$minVersionExists) {
			$aspen_db->exec("ALTER TABLE plugin ADD COLUMN minAspenVersion VARCHAR(20) DEFAULT NULL COMMENT 'Minimum required Aspen version'");
		}
		
		if (!$maxVersionExists) {
			$aspen_db->exec("ALTER TABLE plugin ADD COLUMN maxAspenVersion VARCHAR(20) DEFAULT NULL COMMENT 'Maximum supported Aspen version'");
		}
		
		return ['success' => true, 'message' => 'Plugin table updated with version fields'];
	} catch (Exception $e) {
		return ['success' => false, 'message' => 'Failed to update plugin table: ' . $e->getMessage()];
	}
}

function update_plugin_table_remove_redundant_columns() {
	/** @var PDO $aspen_db */
	global $aspen_db;
	
	if ($aspen_db == null) {
		return ['success' => false, 'message' => 'No database connection'];
	}
	
	try {
		// Check which columns exist before trying to drop them
		$columnsToCheck = ['hasJsInjection', 'jsFiles', 'cssFiles', 'hookPoints', 'installDate'];
		$columnsToRemove = [];
		
		foreach ($columnsToCheck as $column) {
			$stmt = $aspen_db->prepare("SHOW COLUMNS FROM plugin WHERE Field = ?");
			$stmt->execute([$column]);
			if ($stmt->fetch() !== false) {
				$columnsToRemove[] = $column;
			}
		}
		
		// Remove columns that exist
		foreach ($columnsToRemove as $column) {
			$aspen_db->exec("ALTER TABLE plugin DROP COLUMN $column");
		}
		
		if (!empty($columnsToRemove)) {
			$removedColumns = implode(', ', $columnsToRemove);
			return ['success' => true, 'message' => "Removed redundant plugin table columns: $removedColumns"];
		} else {
			return ['success' => true, 'message' => 'No redundant columns found to remove'];
		}
	} catch (Exception $e) {
		return ['success' => false, 'message' => 'Failed to remove redundant plugin table columns: ' . $e->getMessage()];
	}
} 