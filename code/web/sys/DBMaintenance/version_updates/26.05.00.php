<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_05_00(): array {
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
		'municipality_extend_registration' => [
			'title' => 'Allow Extending Registration In Sierra Municipality',
			'description' => 'Convert array to traditional syntax',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE self_reg_municipality_values_sierra ADD COLUMN extendExpirationToMonthEnd TINYINT(1) DEFAULT 0',
			]
		], //municipality_extend_registration

		'create_plugin_table' => [
			'title' => 'Create Plugin Table',
			'description' => 'Create the plugin table for storing plugin information and configuration',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE plugin (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					version VARCHAR(20) NOT NULL,
					description TEXT,
					author VARCHAR(100),
					enabled TINYINT(1) NOT NULL DEFAULT 1,
					updateDate INT(11),
					minAspenVersion VARCHAR(20) COMMENT 'Minimum required Aspen Discovery version',
					INDEX idx_plugin_slug (name, enabled)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
			]
		], //create_plugin_table

		'create_plugin_permission' => [
			'title' => 'Create Plugin Administration Permission',
			'description' => 'Add permission to administer plugins',
			'continueOnError' => false,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('System Administration', 'Administer Plugins', '', 80, 'Controls if the user can administer plugins.')"
			]
		], //create_plugin_permission

		//kirstien

		//kodi

		//yanjun

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen


		//pedro

		//other

	];
}
