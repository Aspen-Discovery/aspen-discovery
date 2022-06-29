<?php
/** @noinspection PhpUnused */
function getUpdates22_07_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'evolve_module' => [
			'title' => 'Add module for Evolve',
			'description' => 'Add module for Evolve',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess,logClassPath,logClassName) VALUES ('Evolve', 'grouped_works', 'evolve_export','/sys/ILS/IlsExtractLogEntry.php', 'IlsExtractLogEntry')",
			]
		], //evolve_module
        'themes_browse_category_image_size' => [
            'title' => 'Theme - browse category image size',
            'description' => 'Define cover image size for browse categories',
            'sql' => [
                "ALTER TABLE `themes` ADD COLUMN browseCategoryImageSize TINYINT(1) DEFAULT -1",
            ]
        ], //browse_category_image_size
		'axis_360_options' => [
			'title' => 'Add Axis 360 Options',
			'description' => 'Add options for Axis 360 hold email',
			'sql' => [
				"ALTER TABLE user ADD axis360Email VARCHAR( 250 ) NOT NULL DEFAULT ''",
				"ALTER TABLE user ADD promptForAxis360Email TINYINT DEFAULT 1",
				"UPDATE user SET axis360Email = email WHERE axis360Email = ''"
			]
		], //axis_360_options
	];
}
