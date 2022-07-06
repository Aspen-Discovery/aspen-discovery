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
                "ALTER TABLE `themes` ADD COLUMN browseCategoryImageSize TINYINT(1) DEFAULT 0",
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
		'closed_captioning_in_records' => [
			'title' => 'Closed Captioning in Records',
			'description' => 'Store if a record is closed captioned',
			'sql' => [
				"ALTER TABLE grouped_work_records ADD COLUMN isClosedCaptioned TINYINT(1) DEFAULT 0",
			]
		], //closed_captioning_in_records
		'greenhouse_cpu_and_memory_monitoring' => [
			'title' => 'CPU and memory monitoring in Greenhouse',
			'description' => 'Add tracking of CPU and memory within the Greenhouse',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS aspen_site_cpu_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					aspenSiteId INT(11) NOT NULL,
					loadPerCpu FLOAT NOT NULL,
					timestamp INT(11),
					UNIQUE (aspenSiteId, timestamp)
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS aspen_site_memory_usage (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					aspenSiteId INT(11) NOT NULL,
					percentMemoryUsage FLOAT NOT NULL,
					totalMemory FLOAT NOT NULL,
					availableMemory FLOAT NOT NULL,
					timestamp INT(11),
					UNIQUE (aspenSiteId, timestamp)
				) ENGINE INNODB',
				'CREATE TABLE IF NOT EXISTS aspen_site_stats (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					aspenSiteId INT(11) NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					day INT(2) NOT NULL,
					minDataDiskSpace FLOAT NOT NULL,
					minUsrDiskSpace FLOAT NOT NULL,
					minAvailableMemory FLOAT NOT NULL,
					maxAvailableMemory FLOAT NOT NULL,
					minLoadPerCPU FLOAT NOT NULL,
					maxLoadPerCPU FLOAT NOT NULL,
					maxWaitTime FLOAT NOT NULL,
					UNIQUE (aspenSiteId, year, month, day)
				) ENGINE INNODB',
			]
		],//greenhouse cpu and monitoring
        'remove_detailed_hold_notice_configuration' => array(
            'title' => 'Remove Detailed Hold Notice Configuration',
            'description' => 'Remove Detailed Hold Notice Configuration',
            'sql' => array(
                "ALTER TABLE library DROP COLUMN showDetailedHoldNoticeInformation",
                "ALTER TABLE library DROP COLUMN treatPrintNoticesAsPhoneNotices",
            )
        ), //remove_detailed_hold_notice_configuration
        'remove_empty_MyFavorites_lists' => [
            'title' => 'Remove Empty My Favorites Lists',
            'description' => 'Remove empty My Favorites lists to cleanup past bug where duplicate empty My Favorites lists were created',
            'sql' => [
                "DELETE FROM user_list WHERE id NOT IN (SELECT l.listId FROM user_list_entry l) AND title='My Favorites'",
            ]
        ], //remove empty My Favorites lists
        'themes_browse_image_layout' => [
            'title' => 'Theme - browse image layout',
            'description' => 'Choose layout of cover images for browse categories (masonry or grid)',
            'sql' => [
                "ALTER TABLE `themes` ADD COLUMN browseImageLayout TINYINT(1) DEFAULT -1",
            ]
        ], //browse image layout theme
		'remove_list_entries_for_deleted_lists' => [
			'title' => 'Remove list entries for deleted lists',
			'description' => 'Remove any list entires for lists that were previously deleted.',
			'sql' => [
				"DELETE e FROM user_list_entry e left join user_list l on e.listId = l.id where l.id is NULL",
			]
		]
	];
}
