<?php

function getCollectionSpotlightUpdates(){
	/** @noinspection SpellCheckingInspection */
	return array(
		'list_widgets' => array(
			'title' => 'Setup Configurable List Widgets',
			'description' => 'Create list widgets tables',
			'sql' => array(
				"CREATE TABLE IF NOT EXISTS list_widgets (" .
				"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
				"`name` VARCHAR(50) NOT NULL, " .
				"`description` TEXT, " .
				"`showTitleDescriptions` TINYINT DEFAULT 1, " .
				"`onSelectCallback` VARCHAR(255) DEFAULT '' " .
				") ENGINE = InnoDB COMMENT = 'A widget that can be displayed within VuFind or within other sites' ",
				"CREATE TABLE IF NOT EXISTS list_widget_lists (" .
				"`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
				"`listWidgetId` INT NOT NULL, " .
				"`weight` INT NOT NULL DEFAULT 0, " .
				"`displayFor` ENUM('all', 'loggedIn', 'notLoggedIn') NOT NULL DEFAULT 'all', " .
				"`name` VARCHAR(50) NOT NULL, " .
				"`source` VARCHAR(500) NOT NULL, " .
				"`fullListLink` VARCHAR(500) DEFAULT '' " .
				") ENGINE = InnoDB COMMENT = 'The lists that should appear within the widget' ",
			),
		),

		'list_widgets_update_1' => array(
			'title' => 'List Widget List Update 1',
			'description' => 'Add additional functionality to list widgets (auto rotate and single title view)',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `autoRotate` TINYINT NOT NULL DEFAULT '0'",
				"ALTER TABLE `list_widgets` ADD COLUMN `showMultipleTitles` TINYINT NOT NULL DEFAULT '1'",
			),
		),

		'list_widgets_update_2' => array(
			'title' => 'List Widget Update 2',
			'description' => 'Add library id to list widget',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `libraryId` INT(11) NOT NULL DEFAULT '-1'",
			),
		),

		'list_widgets_home' => array(
			'title' => 'List Widget Home',
			'description' => 'Create the default homepage widget',
			'sql' => array(
				"INSERT INTO list_widgets (name, description, showTitleDescriptions, onSelectCallback, customCss) VALUES ('home', 'Default example widget.', '1','','')",
				"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor) VALUES ('1', '1', 'highestRated', 'Highest Rated', 'all')",
				"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor) VALUES ('1', '2', 'recentlyReviewed', 'Recently Reviewed', 'all')",
			),
		),

		'list_wdiget_list_update_1' => array(
			'title' => 'List Widget List Source Length Update',
			'description' => 'Update length of source field to accommodate search source type',
			'sql' => array(
				"ALTER TABLE `list_widget_lists` CHANGE `source` `source` VARCHAR( 500 ) NOT NULL "
			),
		),

		'list_wdiget_update_1' => array(
			'title' => 'Update List Widget 1',
			'description' => 'Update List Widget to allow custom css files to be included and allow lists do be displayed in dropdown rather than tabs',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `customCss` VARCHAR( 500 ) NOT NULL ",
				"ALTER TABLE `list_widgets` ADD COLUMN `listDisplayType` ENUM('tabs', 'dropdown') NOT NULL DEFAULT 'tabs'"
			),
		),

		'list_widget_update_2' => array(
			'title' => 'Update List Widget 2',
			'description' => 'Update List Widget to add vertical widgets',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `style` ENUM('vertical', 'horizontal', 'single') NOT NULL DEFAULT 'horizontal'",
				"UPDATE `list_widgets` SET `style` = 'single' WHERE showMultipleTitles = 0",
			),
		),

		'list_widget_update_3' => array(
			'title' => 'List Widget Update 3',
			'description' => 'New functionality for widgets - ratings, cover size, new display option',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `coverSize` ENUM('small', 'medium') NOT NULL DEFAULT 'small'",
				"ALTER TABLE `list_widgets` ADD COLUMN `showRatings` TINYINT NOT NULL DEFAULT '0'",
				"ALTER TABLE `list_widgets` CHANGE `style` `style` ENUM('vertical', 'horizontal', 'single', 'single-with-next') NOT NULL DEFAULT 'horizontal'",
				"ALTER TABLE `list_widgets` ADD COLUMN `showTitle` TINYINT NOT NULL DEFAULT '1'",
				"ALTER TABLE `list_widgets` ADD COLUMN `showAuthor` TINYINT NOT NULL DEFAULT '1'",
			),
		),

		'list_widget_update_4' => array(
			'title' => 'List Widget Update 4',
			'description' => 'Additional options for ',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `showViewMoreLink` TINYINT NOT NULL DEFAULT '0'",
				"ALTER TABLE `list_widgets` ADD COLUMN `viewMoreLinkMode` ENUM('covers', 'list') NOT NULL DEFAULT 'list'",
			),
		),

		'list_widget_style_update' => array(
			'title' => 'List Widget Style Update',
			'description' => 'Add Text-Only List as a style option.',
			'sql' => array(
				"ALTER TABLE `list_widgets` CHANGE `style` `style` ENUM('vertical', 'horizontal', 'single', 'single-with-next', 'text-list') NOT NULL DEFAULT 'horizontal'",
				"ALTER TABLE `list_widgets` COMMENT = 'A widget that can be displayed within Aspen Discovery or within other sites'",
			),
		),

		'list_widget_update_5' => array(
			'title' => 'List Widget Update 5',
			'description' => 'Switch for displaying or not displaying a widget\'s title bar.',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `showListWidgetTitle` TINYINT NOT NULL DEFAULT '1'",
			),
		),

		'list_widget_num_results' => array(
			'title' => 'List Widget Number of titles to show',
			'description' => 'Add the ability to determine how many results should be shown for a list.',
			'sql' => array(
				"ALTER TABLE `list_widgets` ADD COLUMN `numTitlesToShow` INT NOT NULL DEFAULT '25'",
			),
		),

		'list_widget_search_terms' => [
			'title' => 'List Widget Number of titles to show',
			'description' => 'Add the ability to determine how many results should be shown for a list.',
			'sql' => array(
				"ALTER TABLE list_widget_lists ADD COLUMN defaultFilter TEXT",
				"ALTER TABLE list_widget_lists ADD COLUMN defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating')",
				"ALTER TABLE list_widget_lists ADD COLUMN searchTerm VARCHAR(500) NOT NULL DEFAULT ''",
				"ALTER TABLE list_widget_lists ADD COLUMN sourceListId MEDIUMINT NULL DEFAULT NULL",
			),
		],

		'remove_list_widget_list_links' => [
			'title' => 'Remove Widget List Links',
			'description' => 'Remove table list_widget_lists_links',
			'sql' => [
				"DROP TABLE IF EXISTS list_widget_lists_links"
			],
		],

		'rename_to_collection_spotlight' => [
			'title' => 'Rename List Widgets to Collection Spotlight',
			'description' => 'Rename tables and columns from list widgets to collection spotlight for consistency.',
			'sql' => [
				"ALTER TABLE list_widgets CHANGE COLUMN showListWidgetTitle showSpotlightTitle TINYINT NOT NULL DEFAULT '1'",
				"ALTER TABLE list_widget_lists CHANGE COLUMN listWidgetId collectionSpotlightId INT NOT NULL",
				"RENAME TABLE list_widgets TO collection_spotlights",
				"RENAME TABLE list_widget_lists TO collection_spotlight_lists",
			]
		],

		'update_spotlight_sources' => [
			'title' => 'Update Spotlight sources',
			'description' => 'Update Spotlight Sources to make it easier to add other types of searches.',
			'sql' => [
				'updateSpotlightSources'
			]
		],

		'collection_spotlights_carousel_style' => [
			'title' => 'Collection Spotlights Carousel Style',
			'description' => 'Add carousel style to collection spotlights',
			'sql' => [
				"ALTER TABLE `collection_spotlights` CHANGE `style` `style` ENUM('vertical', 'horizontal', 'single', 'single-with-next', 'text-list', 'horizontal-carousel') NOT NULL DEFAULT 'horizontal'",
			]
		]
	);
}

/** @noinspection PhpUnused */
function updateSpotlightSources(){
	require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';
	$validSources = BaseBrowsable::getBrowseSources();
	$spotlightLists = new CollectionSpotlightList();
	$spotlightLists->find();
	$allSpotlights = [];
	while ($spotlightLists->fetch()) {
		$allSpotlights[] = clone $spotlightLists;
	}
	foreach ($allSpotlights as $index => $spotlightList){
		if (!empty($spotlightList->source)){
			$source = $spotlightList->source;
			if (strpos($source, ':') > 0){
				list($sourceName, $sourceId) = explode(':', $source);
				if ($sourceName == 'list'){
					$spotlightList->source = 'List';
					if (empty($spotlightList->sourceListId)){
						$spotlightList->source = $sourceId;
					}
					$spotlightList->update();
				}elseif ($sourceName == 'search') {
					if (empty($spotlightList->sourceListId)){
						/** @var SearchObject_GroupedWorkSearcher $searcher */
						$searcher = SearchObjectFactory::initSearchObject('GroupedWork');
						$savedSearch = $searcher->restoreSavedSearch($sourceId, false, true);
						if ($savedSearch !== false) {
							$spotlightList->updateFromSearch($savedSearch);
							$spotlightList->update();
						}
					}
				}
			}
		}else{
			if (!array_key_exists($spotlightList->source, $validSources)) {
				if (!empty($spotlightList->sourceListId) && $spotlightList->sourceListId != -1) {
					$spotlightList->source = 'List';
				} else {
					$spotlightList->source = 'GroupedWork';
				}
				$spotlightList->update();
			}
		}
	}
}