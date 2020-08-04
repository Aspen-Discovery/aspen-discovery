<?php
/** @noinspection SqlResolve */
function getAxis360Updates(){
	return [
		'createAxis360Module' => [
			'title' => 'Create EBSCO modules',
			'description' => 'Setup modules for EBSCO Integration',
			'sql' =>[
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('Axis 360', 'grouped_works', 'axis_360_export','/sys/Axis360/Axis360LogEntry.php', 'Axis360LogEntry')",
			]
		],
	];
}
