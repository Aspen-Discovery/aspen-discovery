<?php /** @noinspection SqlResolve */
function getApiOAuthUpdates(): array {
	return [
		'user_oauth_enable_system_variable' => [
			'title' => 'Add User OAuth System Variable',
			'description' => 'Add system variable to enable user OAuth key generation',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN enableUserOAuth TINYINT(1) DEFAULT 0',
			]
		], //user_oauth_enable_system_variable
		'user_oauth_keys_table' => [
			'title' => 'Create User OAuth Keys Table',
			'description' => 'Create table to store user-specific OAuth keys for API authentication',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS user_oauth_keys (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT(11) NOT NULL,
					keyName VARCHAR(100) NOT NULL,
					clientId VARCHAR(64) NOT NULL UNIQUE,
					clientSecret VARCHAR(255) NOT NULL,
					created INT(11) NOT NULL,
					lastUsed INT(11) DEFAULT NULL,
					isActive TINYINT(1) DEFAULT 1,
					INDEX (userId),
					INDEX (clientId),
					INDEX (isActive),
					FOREIGN KEY (userId) REFERENCES user(id) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
			]
		], //user_oauth_keys_table
		'user_oauth_permission' => [
			'title' => 'Add Use API Keys Permission',
			'description' => 'Add permission for users to create and manage their own API keys',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('API', 'Use API Keys', '', 10, 'Allows users to create and manage OAuth API keys for their account. Can be assigned to any role including patron roles.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Use API Keys'))",
			]
		], //user_oauth_permission
		'use_all_api_endpoints_permission' => [
			'title' => 'Add Use All API Endpoints Permission',
			'description' => 'Add permission required for OAuth tokens to access API endpoints',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('API', 'Use All API Endpoints', '', 20, 'Required for OAuth API keys to access API endpoints. This is a transitional permission while APIs are migrated to granular permissions.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Use All API Endpoints'))",
			]
		], //use_all_api_endpoints_permission
	];
}
