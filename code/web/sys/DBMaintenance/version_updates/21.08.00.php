<?php
/** @noinspection PhpUnused */
function getUpdates21_08_00() : array
{
	return [
		'library_archive_permission' => [
			'title' => 'Fix Library Archive Permission name',
			'description' => 'Fix library archive permission',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE permissions set name = 'Library Archive Options' where name = 'Library Open Archive Options'",
			]
		], //upload_list_cover_permissions
	];
}