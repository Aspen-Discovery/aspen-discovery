<?php
/** @noinspection PhpUnused */
function getUpdates21_15_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'donations_report_permissions' => [
			'title' => 'Add permissions for Donations report',
			'description' => 'Add permissions for Donations report',
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'View Donations Reports', '', 6, 'Controls if the user can view the Donations REport.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Donations Reports'))",
			]
		], //donations_report_permissions
	];
}