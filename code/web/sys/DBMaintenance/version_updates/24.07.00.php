<?php

function getUpdates24_07_00(): array {
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


		//kirstien - ByWater


		//kodi - ByWater

		//katherine - ByWater

		//pedro - PTFS-Europe
		'smtp_settings' => [
			'title' => 'SMTP Settings',
			'description' => 'Allow configuration of SMTP to send mail from',
			'sql' => [
				"CREATE TABLE smtp_settings (
					id int(11) NOT NULL AUTO_INCREMENT,
					name varchar(80) NOT NULL,
					host varchar(80) NOT NULL DEFAULT 'localhost',
					port int(11) NOT NULL DEFAULT 25,
					ssl_mode enum('disabled','ssl','tls') NOT NULL,
					from_address varchar(80) DEFAULT NULL,
					from_name varchar(80) DEFAULT NULL,
					user_name varchar(80) DEFAULT NULL,
					password varchar(80) DEFAULT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB",
			],
		], //smtp_settings

		'permissions_create_administer_smtp' => [
			'title' => 'Create Administer SMTP Permission',
			'description' => 'Controls if the user can change SMTP settings',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Primary Configuration', 'Administer SMTP', '', 30, 'Controls if the user can change SMTP settings.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer SMTP'))",
			],
		], //permissions_create_administer_smtp

		//other



	];
}