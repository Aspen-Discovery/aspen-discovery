<?php

function getFileUploadUpdates() {
	return [
		'file_uploads_table' => [
			'title' => 'Create File Uploads Table',
			'description' => 'Create File Uploads Table to store information about files that have been uploaded to aspen',
			'sql' => [
				'CREATE TABLE file_uploads (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					title VARCHAR(255) NOT NULL,
					fullPath VARCHAR(512) NOT NULL,
					type VARCHAR(25) NOT NULL,
					INDEX (type)
				) ENGINE INNODB',
			],
		],

		'record_files_table' => [
			'title' => 'Create record files table',
			'description' => 'Create a table so files can be attached to individual records',
			'sql' => [
				'CREATE TABLE record_files (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					type VARCHAR(50),
					identifier VARCHAR(50),
					fileId INT(11),
					INDEX (fileId),
					INDEX (type, identifier)
				) ENGINE INNODB',
			],
		],
	];
}