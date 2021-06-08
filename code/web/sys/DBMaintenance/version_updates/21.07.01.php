<?php

function getUpdates21_07_01() : array
{
	return [

		'library_field_permission_updates_21_07_01' => [
			'title' => 'Library Field Permission Updates 21.07.01',
			'description' => 'Fix Library Field Permission Updates 21.07.01',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions set name = 'Library Archive Options' where name = 'Library Open Archive Options'",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'opacAdmin'), (SELECT id from permissions where name='Library Archive Options'))",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name  = 'libraryAdmin'), (SELECT id from permissions where name='Library Archive Options'))",
			]
		], //library_archive_permission_21_07_01
	];
}