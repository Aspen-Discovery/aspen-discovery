<?php

function getUpdates21_07_01(): array {
	return [
		'library_field_permission_updates_21_07_01' => [
			'title' => 'Library Field Permission Updates 21.07.01',
			'description' => 'Fix Library Field Permission Updates 21.07.01',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions set name = 'Library Archive Options' where name = 'Library Open Archive Options'",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Archive Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Archive Options'))",
			],
		],
		//library_field_permission_updates_21_07_01
		'library_default_materials_request_permissions' => [
			'title' => 'Library Field Permission updates for materials request',
			'description' => 'Library Field Permission updates for materials request',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Materials Request Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Materials Request Options'))",
			],
		],
		//library_default_materials_request_permissions
		'grouped_work_display_showItemDueDates' => [
			'title' => 'Grouped Work Display Show Item Due Dates',
			'description' => 'Add whether or not due dates are show within item details',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE grouped_work_display_settings ADD COLUMN showItemDueDates TINYINT(1) DEFAULT 1",
			],
		],
		//grouped_work_display_showItemDueDates
	];
}