<?php
/** @noinspection PhpUnused */
function getUpdates22_01_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'curbside_pickup_settings' => [
			'title' => 'Add settings for Curbside Pickup',
			'description' => 'Add settings for Curbside Pickup',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS curbside_pickup_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					alwaysAllowPickups TINYINT(1) DEFAULT 0,
					allowCheckIn TINYINT(1) DEFAULT 1,
					useNote TINYINT(1) DEFAULT 1,
					noteLabel VARCHAR(75) DEFAULT "Note",
					noteInstruction VARCHAR(255) DEFAULT NULL,
					instructionSchedule LONGTEXT DEFAULT NULL,
					instructionNewPickup LONGTEXT DEFAULT NULL,
					contentSuccess LONGTEXT DEFAULT NULL,
					contentCheckedIn LONGTEXT DEFAULT NULL
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Curbside Pickup', 'Administer Curbside Pickup', '', 10, 'Controls if the user can change Curbside Pickup settings.')",
				"ALTER TABLE library ADD COLUMN curbsidePickupSettingId INT(11) DEFAULT -1"
			]
		], //curbside_pickup_settings
		'curbside_pickup_settings_pt2' => [
			'title' => 'Additional settings for Curbside Pickup',
			'description' => 'Add additional settings for curbside pickup to curbside_pickup_settings and location',
			'sql' => [
				"ALTER TABLE curbside_pickup_settings ADD COLUMN timeAllowedBeforeCheckIn INT(5) default 30",
				"ALTER TABLE location ADD COLUMN curbsidePickupInstructions VARCHAR(255)",
			]
		], //curbside_pickup_settings_pt2
		'curbside_pickup_settings_pt3' => [
			'title' => 'Additional settings for Curbside Pickup',
			'description' => 'Add pickup instructions for curbside to curbside_pickup_settings',
			'sql' => [
				"ALTER TABLE curbside_pickup_settings ADD COLUMN curbsidePickupInstructions VARCHAR(255)",
			]
		], //curbside_pickup_settings_pt3
		'curbside_pickup_settings_pt4' => [
			'title' => 'Curbside Pickups Pt 4 - default permissions',
			'description' => 'Give opac admin permissions for curbside pickup',
			'sql' => [
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Curbside Pickup'))",
			]
		], //curbside_pickup_settings_pt3
		'sierra_public_note_export' => [
			'title' => 'Add export of public note from Sierra',
			'description' => 'Add export of public note from Sierra',
			'sql' => [
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN itemPublicNoteExportSubfield VARCHAR(1) DEFAULT ''",
			]
		], //sierra_public_note_export
		'greenhouse_add_ils'=> [
			'title' => 'Greenhouse - Add ILS',
			'description' => 'Track the active ILS for a site within the greenouse',
			'sql' => [
				'ALTER TABLE aspen_sites ADD COLUMN ils INT'
			]
		], //greenhouse_add_ils
		'website_pages_deletionReason' => [
			'title' => 'Add deletion reason to website pages',
			'description' => 'Website Pages - Deletion Reason',
			'sql' => [
				"ALTER TABLE website_pages ADD COLUMN deleteReason VARCHAR(255) DEFAULT ''"
			]
		], //website_pages_deletionReason
		'layout_settings_contrast' => [
			'title' => 'Add custom minimum contrast rule',
			'description' => 'Allow libraries to set higher minimum contrasts for their themes',
			'sql' => [
				"ALTER TABLE layout_settings ADD COLUMN contrastRatio VARCHAR(5) DEFAULT 4.50"
			]
		], //layout_settings_contrast
		'two_factor_auth' => [
			'title' => 'Add tables for 2FA',
			'description' => 'Add tables to store data related to 2FA',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS two_factor_auth_codes (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11),
					sessionId VARCHAR(128),
					code VARCHAR(7),
					dateSent INT(12),
					status VARCHAR(75) DEFAULT NULL /* sent, confirmed, expired, backup? */
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS two_factor_auth_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					isEnabled VARCHAR(25) DEFAULT null,
					authMethods VARCHAR(75) DEFAULT null,
					deniedMessage LONGTEXT,
					defaultRememberMe TINYINT(1) DEFAULT 0
				) ENGINE INNODB',
				"ALTER TABLE user ADD COLUMN twoFactorStatus int(1) DEFAULT 0", // 0 = not set, 1 = opted out, 2 = opted in
				"ALTER TABLE user ADD COLUMN primaryTwoFactor varchar(25) DEFAULT null",
				"ALTER TABLE user ADD COLUMN authLocked TINYINT(1) DEFAULT 0",
				"ALTER TABLE ptype ADD COLUMN twoFactorAuthSettingId int(11) DEFAULT -1",
				"ALTER TABLE library ADD COLUMN twoFactorAuthSettingId int(11) DEFAULT -1",
			]
		], //two_factor_auth
		'two_factor_auth_permission' => [
			'title' => 'Add permission setting for 2FA',
			'description' => 'Add permission setting for managing two-factor authentication',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer Two-Factor Authentication', '', 90, 'Controls if the user can change Two-Factor Authentication settings. <em>This has potential security and cost implications.</em>')",
			]
		], //two_factor_auth_permission
		'login_unless_in_library' => [
			'title' => 'Add option for web builder page access',
			'description' => 'Add option to require login to access a page unless being viewed within the library',
			'sql' => [
				"ALTER TABLE web_builder_basic_page ADD COLUMN requireLoginUnlessInLibrary TINYINT(1) DEFAULT 0",
				"ALTER TABLE web_builder_portal_page ADD COLUMN requireLoginUnlessInLibrary TINYINT(1) DEFAULT 0",
			]
		], //login_unless_in_library
		'course_reserves_library_mappings' => [
			'title' => 'Course Reserves Library Mappings',
			'description' => 'Add library mappings for course reservers',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS course_reserves_library_map (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					settingId INT(11),
					value VARCHAR(50) NOT NULL,
					translation VARCHAR(255) NOT NULL 
				) ENGINE INNODB",
			]
		],
		'requireLogin_webResource' => [
			'title' => 'Add option for requiring login when accessing web resource',
			'description' => 'Add option for requiring login when accessing web resource outside of library',
			'sql' => [
				"ALTER TABLE web_builder_resource ADD COLUMN requireLoginUnlessInLibrary TINYINT(1) DEFAULT 0",
			]
		], //requireLogin_webResource
		'web_resource_usage' => [
			'title' => 'Add web resource usage table',
			'description' => 'Add a table to track usage of web resources',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS web_builder_resource_usage (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							year INT(4) NOT NULL,
							month INT(2) NOT NULL,
							resourceName VARCHAR(100) NOT NULL,
							pageViews INT(11) DEFAULT 0,
							pageViewsByAuthenticatedUsers INT(11) DEFAULT 0,
							pageViewsInLibrary INT(11) DEFAULT 0,
							instance VARCHAR(100)
						) ENGINE = InnoDB',
				'ALTER TABLE web_builder_resource_usage ADD INDEX (instance, year, month)',
			]
		] //web_resource_usage
	];
}