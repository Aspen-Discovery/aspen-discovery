<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_06_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n
		'options_for_earliest_publication_date' => [
			'title' => 'Earliest Publication Date Visibility',
			'description' => 'Add options for when to show the earliest publication date',
			'sql' => [
				'ALTER TABLE grouped_work_display_settings ADD COLUMN showEarliestPublicationDateSearchResults TINYINT(1) UNSIGNED NOT NULL DEFAULT 1',
				'ALTER TABLE grouped_work_display_settings ADD COLUMN showEarliestPublicationDateFullRecord TINYINT(1) UNSIGNED NOT NULL DEFAULT 1',
			]
		], //options_for_earliest_publication_date

		//kirstien
		'addForceReadingHistoryOptIn' => [
			'title' => 'Add option force patrons to opt-in to reading history',
			'description' => 'Add option to ignore Koha/ILS settings and force new patrons to opt-in to reading history',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN forceReadingHistoryOptIn TINYINT(1) DEFAULT 0',
			]
		],
		//addForceReadingHistoryOptIn
		'extend2FAforTOTP' => [
			'title' => 'Extend 2FA to support TOTP apps',
			'description' => 'Allow libraries to select TOTP as an option for 2FA method',
			'sql' => [
				"ALTER TABLE two_factor_auth_settings ADD COLUMN allowedMethod VARCHAR(255) DEFAULT 'totp'",
				//Default to email for previous setups
				"UPDATE two_factor_auth_settings SET allowedMethod = 'email' WHERE 1",
			]
		],
		//extend2FAforTOTP
		'addTOTPSecretsTable' => [
			'title' => 'Add table to store TOTP user secrets',
			'description' => 'Add table to store TOTP user secrets',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS two_factor_auth_totp_secrets (
				  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  userId INT NOT NULL,
				  secretKey VARCHAR(32) NOT NULL COLLATE utf8mb4_unicode_ci,
				  createdDate INT NOT NULL,
				  verified TINYINT(1) NOT NULL DEFAULT 0
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
			]
		],
		//addTOTPSecretsTable
		'addTwoFactorMethodToUserTable' => [
			'title' => 'Add TwoFactorMethod to User table',
			'description' => 'Add a column to store what 2FA method the user has setup. If the user had 2FA setup prior to TOTP, set the twoFactorMethod to email, else null.',
			'sql' => [
				'ALTER TABLE user ADD COLUMN twoFactorMethod VARCHAR(75) DEFAULT NULL',
				"UPDATE user
				 SET twoFactorMethod = CASE
				   WHEN twoFactorStatus = 1 THEN 'email'
				   ELSE NULL
				 END
				 WHERE twoFactorMethod IS NULL
					OR (twoFactorStatus = 1 AND twoFactorMethod <> 'email')
					OR (twoFactorStatus <> 1 AND twoFactorMethod IS NOT NULL)",
			]
		],
		//addTwoFactorMethodToUserTable
		'Add OAuth2 Server' => [
			'title' => 'Add OAuth2 Server',
			'description' => 'Add OAuth2 Server',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS oauth2_clients (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(255) NOT NULL,
				client_id VARCHAR(255) NOT NULL UNIQUE,
				client_secret VARCHAR(255) NOT NULL,
				client_type ENUM('web_application', 'native_application', 'service_application') DEFAULT 'web_application',
				scopes TEXT,
				redirect_uri VARCHAR(2000),
				is_active TINYINT(1) DEFAULT 1,
				created_by INT,
				created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				INDEX idx_client_id (client_id),
				INDEX idx_client_type (client_type),
				INDEX idx_is_active (is_active),
				INDEX idx_created_by (created_by)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			
			CREATE TABLE IF NOT EXISTS oauth2_access_tokens (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				token_id VARCHAR(100) NOT NULL UNIQUE,
				user_id INT,
				client_id VARCHAR(255) NOT NULL,
				scopes TEXT,
				revoked TINYINT(1) DEFAULT 0,
				expires_at TIMESTAMP NULL,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_token_id (token_id),
				INDEX idx_user_id (user_id),
				INDEX idx_client_id (client_id),
				INDEX idx_revoked (revoked),
				INDEX idx_expires_at (expires_at),
				FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			
			CREATE TABLE IF NOT EXISTS oauth2_auth_codes (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				code_id VARCHAR(100) NOT NULL UNIQUE,
				user_id INT,
				client_id VARCHAR(255) NOT NULL,
				scopes TEXT,
				redirect_uri VARCHAR(2000),
				code_challenge VARCHAR(128),
				code_challenge_method VARCHAR(10),
				revoked TINYINT(1) DEFAULT 0,
				expires_at TIMESTAMP NULL,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_code_id (code_id),
				INDEX idx_user_id (user_id),
				INDEX idx_client_id (client_id),
				INDEX idx_revoked (revoked),
				INDEX idx_expires_at (expires_at),
				FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			
			CREATE TABLE IF NOT EXISTS oauth2_refresh_tokens (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				token_id VARCHAR(100) NOT NULL UNIQUE,
				access_token_id VARCHAR(100) NOT NULL,
				revoked TINYINT(1) DEFAULT 0,
				expires_at TIMESTAMP NULL,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_token_id (token_id),
				INDEX idx_access_token_id (access_token_id),
				INDEX idx_revoked (revoked),
				INDEX idx_expires_at (expires_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			
			CREATE TABLE IF NOT EXISTS oauth2_rate_limits (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				client_id VARCHAR(255) NOT NULL,
				ip_address VARCHAR(45) NOT NULL,
				endpoint VARCHAR(100) NOT NULL,
				request_count INT DEFAULT 0,
				window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				UNIQUE KEY unique_rate_limit (client_id, ip_address, endpoint),
				INDEX idx_client_id (client_id),
				INDEX idx_endpoint (endpoint),
				INDEX idx_window_start (window_start),
				INDEX idx_last_request (last_request)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			
			INSERT IGNORE INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
			('System Administration', 'Administer OAuth2', '', 325, 'Controls if the user can manage OAuth2 clients and view OAuth2 settings.');
			
			ALTER TABLE oauth2_access_tokens ADD INDEX idx_user_client (user_id, client_id);
			ALTER TABLE oauth2_auth_codes ADD INDEX idx_user_client (user_id, client_id);
			"
			]
		],
		//Add OAuth2 Server
		'Add OpenID Connect' => [
			'title' => 'Add OpenID Connect',
			'description' => 'Add OpenID Connect support to OAuth2',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS oauth2_openid_clients (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(255) NOT NULL,
				client_id VARCHAR(255) NOT NULL UNIQUE,
				client_secret VARCHAR(255) NOT NULL,
				allowed_claims TEXT,
				redirect_uri VARCHAR(2000),
				is_active TINYINT(1) DEFAULT 1,
				created_by INT,
				created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				INDEX idx_client_id (client_id),
				INDEX idx_is_active (is_active),
				INDEX idx_created_by (created_by)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
			]
		],
		//Add OpenID Connect
		'Add OpenID Connect Permissions' => [
			'title' => 'Add OpenID Connect Permissions',
			'description' => 'Add OpenID Connect permissions',
			'continueOnError' => false,
			'sql' => [
				"INSERT IGNORE INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
			('System Administration', 'Administer OpenID Connect', '', 325, 'Controls if the user can manage OpenID Connect clients');
			"
			]
		],
		//Add OpenID Connect Permissions

		//kodi
		'series_columns' => [
			'title' => 'Add Columns in Series Table',
			'description' => 'Add columns in series table for permanent id and language',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE series ADD COLUMN seriesPermanentId CHAR(40)',
				'ALTER TABLE series ADD COLUMN seriesLanguage VARCHAR(20)',
			]
		], //series_columns
		'series_setting_version' => [
			'title' => 'Add Column in Series Indexing Settings Table',
			'description' => 'Add column in series settings indexing table for version',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE series_indexing_settings ADD COLUMN version tinyint(1) DEFAULT 0',
				'ALTER TABLE series_indexing_settings ADD COLUMN truncateForVersionSwitch TINYINT(1) NOT NULL DEFAULT 0',
			]
		], //series_setting_version
		'permissions_create_events_localhop' => [
			'title' => 'Alters permissions for Events',
			'description' => 'Create permissions for LocalHop',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Events', 'Administer LocalHop Settings', 'Events', 20, 'Allows the user to administer integration with LocalHop for all libraries.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer LocalHop Settings'))",
			],
		],
		// permissions_create_events_localhop
		'localhop_settings' => [
			'title' => 'Define events settings for LocalHop integration',
			'description' => 'Initial setup of the LocalHop integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS localhop_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL UNIQUE,
					baseUrl VARCHAR(255) NOT NULL,
					eventsInLists tinyint(1) default 1,
					bypassAspenEventPages tinyint(1) default 0,
					registrationModalBody mediumtext,
					registrationModalBodyApp varchar(500),
					numberOfDaysToIndex INT DEFAULT 365
				) ENGINE INNODB',
			],
		], // localhop_settings
		'localhop_events' => [
			'title' => 'LocalHop Event Data',
			'description' => 'Set up table to store events data for LocalHop',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS localhop_events (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingsId INT NOT NULL,
					externalId varchar(150) NOT NULL,
					title varchar(255) NOT NULL,
					rawChecksum BIGINT,
					rawResponse MEDIUMTEXT,
					deleted TINYINT default 0,
					UNIQUE (settingsId, externalId)
				)',
			],
		], // localhop_events
		'scheduled_offline_mode' => [
			'title' => 'Scheduled Offline Mode',
			'description' => 'Add columns to system variables table for scheduling offline mode.',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN scheduledOfflineStart int(11) DEFAULT NULL',
				'ALTER TABLE system_variables ADD COLUMN scheduledOfflineEnd int(11) NULL DEFAULT NULL',
				'ALTER TABLE system_variables ADD COLUMN scheduledEcontentAccess TINYINT(1) NOT NULL DEFAULT 0',
			]
		], //scheduled_offline_mode
		'scoped_more_like_this' => [
			'title' => 'Scoped More Like This',
			'description' => 'Add setting for scoping options for More Like This feature.',
			'sql' => [
				'ALTER TABLE library ADD COLUMN moreLikeThisSettings tinyint(1) DEFAULT 1',
			]
		], //scoped_more_like_this

		//yanjun
		'add_overdriveAdvantageId' => [
			'title' => 'Add overdriveAdvantageId column',
			'description' => 'Add overdriveAdvantageId column to library_overdrive_settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_overdrive_settings ADD COLUMN overdriveAdvantageId int(11) DEFAULT 0'
			]
		],//add_overdriveAdvantageId
		'allow_to_renew_ill_items' => [
			'title' => 'Allow Renewing ILL Items',
			'description' => 'Add allowToRenewILL to the library table to control whether patrons can renew ILL items.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowToRenewILL TINYINT(1) DEFAULT 1'
			]
		], //allow_to_renew_ill_items
		'add_overdrive_advantage_products_key_additional' => [
			'title' => 'Add OverDrive Advantage Products Key Additional',
			'description' => 'Add a field for additional advantage collection tokens per library',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_overdrive_settings ADD COLUMN additionalAdvantageProductsKey varchar(255) DEFAULT \'\''
			]
		], //add_overdrive_advantage_products_key_additional
		'add_overdrive_advantage_products_id_additional' => [
			'title' => 'Add OverDrive Advantage Products ID Additional',
			'description' => 'Add a field for additional advantage collection ID per library',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library_overdrive_settings ADD COLUMN additionalAdvantageId int(11) DEFAULT 0'
			]
		], //add_overdrive_advantage_products_id_additional
		'store_original_cover_urls_by_size' => [
			'title' => 'Store Original Cover URLs by Size',
			'description' => 'Store original cover URLs separately for small, medium, and large cover requests.',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE bookcover_info ADD COLUMN IF NOT EXISTS original_url_small TEXT DEFAULT NULL",
				"ALTER TABLE bookcover_info ADD COLUMN IF NOT EXISTS original_url_medium TEXT DEFAULT NULL",
				"ALTER TABLE bookcover_info ADD COLUMN IF NOT EXISTS original_url_large TEXT DEFAULT NULL",
			]
		], //store_original_cover_urls_by_size

		//imani

		//galen

		//chloe

		//pedro

		//mark j
		'load_libraries_and_locations_from_ils' => [
				'title' => 'Add "load libraries and locations from ILS" to the indexing_profiles table',
				'description' => 'Adds a checkbox to control whether library/location data is imported from the ILS during Polaris export',
				'continueOnError' => false,
				'sql' => [
						"ALTER TABLE indexing_profiles ADD COLUMN loadLibrariesAndLocationsFromIls TINYINT(1) NOT NULL DEFAULT 1"
				]
		], //load_libraries_and_locations_from_ils

		//lucas
		'language_add_is_default' => [
			'title' => 'Add Default Language Flag',
			'description' => 'Adds an isDefault column to the languages table to allow admins to designate a default language for unauthenticated users. English is set as the initial default to preserve existing behavior.',
			'sql' => [
				'ALTER TABLE languages ADD COLUMN isDefault TINYINT(1) NOT NULL DEFAULT 0',
				"UPDATE languages SET isDefault = 1 WHERE code = 'en' LIMIT 1",
			],
		], //language_add_is_default

		//tomas

		// stephen

		'user_payments_receipt_url_rename' => [
			'title' => 'Rename Receipt URL Column',
			'description' => 'Rename column from stripeReceiptUrl to receiptUrl.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_payments CHANGE stripeReceiptUrl receiptUrl VARCHAR(255) DEFAULT NULL'
			]
		], //user_payments_receipt_url_rename
		'search_add_send_notification' => [
			'title' => 'Add column sendNotification to search table',
			'description' => 'Adds a column to toggle saved search emails.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE search ADD COLUMN sendNotification TINYINT(1) DEFAULT 1',
			]
		], //search_add_send_notification

		//other

	];
}
