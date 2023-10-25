<?php
/** @noinspection PhpUnused */
function getUpdates23_10_10(): array {
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

		//mark - ByWater
		'emailTemplates' => [
			'title' => 'Setup Email Templates',
			'description' => 'Add initial work for setting up email templates',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS email_template(
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					name varchar(50) COLLATE utf8mb4_general_ci NOT NULL UNIQUE,
					templateType VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
					languageCode CHAR(3) NOT NULL,
					subject VARCHAR(998) NOT NULL, 
					plainTextBody MEDIUMTEXT
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
				'CREATE TABLE library_email_template (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					libraryId INT(11) NOT NULL ,
					emailTemplateId INT(11) NOT NULL,
					UNIQUE (libraryId, emailTemplateId)
				) ENGINE = InnoDB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
					('Email', 'Administer All Email Templates', '', 10, 'Allows the user to edit all email templates in the system.'),
					('Email', 'Administer Library Email Templates', '', 20, 'Allows the user to edit email templates for their library.')
					",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Email Templates'))",
			]
		], //emailTemplates

		//kodi - ByWater
		'self_reg_barcode_prefix' => [
			'title' => 'Barcode Prefixes',
			'description' => 'Set barcode prefixes for symphony self registration',
			'sql' => [
				"ALTER TABLE self_registration_form ADD COLUMN selfRegistrationBarcodePrefix VARCHAR(10) default ''",
				"ALTER TABLE self_registration_form ADD COLUMN selfRegBarcodeSuffixLength INT(2) default 0",
			],
		],
		//self_reg_barcode_prefix
	];
}