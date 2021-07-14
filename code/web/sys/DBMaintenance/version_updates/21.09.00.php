<?php
/** @noinspection PhpUnused */
function getUpdates21_09_00() : array
{
	return [
		'storeNYTLastUpdated' => [
			'title' => 'Store the date a NYT List was last modified',
			'description' => 'Store the date that a NYT List was last modified by NYT',
			'sql' => [
				'ALTER TABLE user_list ADD COLUMN nytListModified int(11) DEFAULT NULL',
			]
		], //storeNYTLastUpdated
		'fileUploadsThumb' => [
			'title' => 'Store the path to the thumbnail for uploaded PDF',
			'description' => 'Store the path to the thumbnail for uploaded PDF',
			'sql' => [
				'ALTER TABLE file_uploads ADD COLUMN thumbFullPath varchar(512) DEFAULT NULL',
			]
		], //fileUploadsThumb
		'pdfView' => [
			'title' => 'Store preferred PDF view for web builder cells',
			'description' => 'Store how an uploaded PDF should appear in a web builder cell',
			'sql' => [
				'ALTER TABLE web_builder_portal_cell ADD COLUMN pdfView varchar(12) DEFAULT NULL',
			]
		], //pdfView
	];
}