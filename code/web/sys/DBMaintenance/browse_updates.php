<?php

function getBrowseUpdates() {
	return [
		'browse_categories' => [
			'title' => 'Browse Categories',
			'description' => 'Setup Browse Category Table',
			'sql' => [
				"CREATE TABLE browse_category (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							textId VARCHAR(60) NOT NULL DEFAULT -1,
							userId INT(11),
							sharing ENUM('private', 'location', 'library', 'everyone') DEFAULT 'everyone',
							label VARCHAR(50) NOT NULL,
							description MEDIUMTEXT,
							defaultFilter TEXT,
							defaultSort ENUM('relevance', 'popularity', 'newest_to_oldest', 'oldest_to_newest', 'author', 'title', 'user_rating'),
							UNIQUE (textId)
						) ENGINE = InnoDB",
			],
		],

		'browse_categories_search_term_and_stats' => [
			'title' => 'Browse Categories Search Term and Stats',
			'description' => 'Add a search term and statistics to browse categories',
			'sql' => [
				"ALTER TABLE browse_category ADD searchTerm VARCHAR(100) NOT NULL DEFAULT ''",
				"ALTER TABLE browse_category ADD numTimesShown MEDIUMINT NOT NULL DEFAULT 0",
				"ALTER TABLE browse_category ADD numTitlesClickedOn MEDIUMINT NOT NULL DEFAULT 0",
			],
		],

		'browse_categories_search_term_length' => [
			'title' => 'Browse Category Search Term Length',
			'description' => 'Increase the length of the search term field',
			'sql' => [
				"ALTER TABLE browse_category CHANGE searchTerm searchTerm VARCHAR(500) NOT NULL DEFAULT ''",
			],
		],

		'browse_categories_lists' => [
			'title' => 'Browse Categories from Lists',
			'description' => 'Add a the ability to define a browse category from a list',
			'sql' => [
				"ALTER TABLE browse_category ADD sourceListId MEDIUMINT NULL DEFAULT NULL",
			],
		],

		'sub-browse_categories' => [
			'title' => 'Enable Browse Sub-Categories',
			'description' => 'Add a the ability to define a browse category from a list',
			'sql' => [
				"CREATE TABLE `browse_category_subcategories` (
							  `id` int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
							  `browseCategoryId` int(11) NOT NULL,
							  `subCategoryId` int(11) NOT NULL,
							  `weight` SMALLINT(2) UNSIGNED NOT NULL DEFAULT '0',
							  UNIQUE (`subCategoryId`,`browseCategoryId`)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			],
		],

		'localized_browse_categories' => [
			'title' => 'Localized Browse Categories',
			'description' => 'Setup Localized Browse Category Tables',
			'sql' => [
				"CREATE TABLE browse_category_library (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							libraryId INT(11) NOT NULL,
							browseCategoryTextId VARCHAR(60) NOT NULL DEFAULT -1,
							weight INT NOT NULL DEFAULT '0',
							UNIQUE (libraryId, browseCategoryTextId)
						) ENGINE = InnoDB",
				"CREATE TABLE browse_category_location (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							locationId INT(11) NOT NULL,
							browseCategoryTextId VARCHAR(60) NOT NULL DEFAULT -1,
							weight INT NOT NULL DEFAULT '0',
							UNIQUE (locationId, browseCategoryTextId)
						) ENGINE = InnoDB",
			],
		],

		'browse_category_groups' => [
			'title' => 'Browse Category Groups',
			'description' => 'Extract Browse Categories into groups to make them easier to reuse',
			'sql' => [
				"CREATE TABLE browse_category_group (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL,
							defaultBrowseMode TINYINT(1) DEFAULT 0, 
							browseCategoryRatingsMode TINYINT(1) DEFAULT 1,
							UNIQUE (name)
						) ENGINE = InnoDB",
				"CREATE TABLE browse_category_group_entry (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							browseCategoryGroupId INT(11) NOT NULL,
							browseCategoryId INT(11) NOT NULL,
							weight INT NOT NULL DEFAULT '0',
							UNIQUE (browseCategoryGroupId, browseCategoryId)
						) ENGINE InnoDB",
				"ALTER TABLE library ADD COLUMN browseCategoryGroupId INT(11) NOT NULL",
				"ALTER TABLE location ADD COLUMN browseCategoryGroupId INT(11) NOT NULL DEFAULT -1",
				//Convert from the old way to the new way
				'populateBrowseCategoryGroups',
				//Cleanup the old values
				"DROP TABLE browse_category_library",
				"DROP TABLE browse_category_location",
				"ALTER TABLE library DROP COLUMN defaultBrowseMode",
				"ALTER TABLE library DROP COLUMN browseCategoryRatingsMode",
				"ALTER TABLE location DROP COLUMN defaultBrowseMode",
				"ALTER TABLE location DROP COLUMN browseCategoryRatingsMode",
			],
		],

		'browse_category_source' => [
			'title' => 'Browse Category Source',
			'description' => 'Add source to Browse Category so searches from archives, events, etc can be shown',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE browse_category ADD COLUMN source VARCHAR(50) NOT NULL',
				'updateBrowseCategorySources',
			],
		],

		'browse_category_library_updates' => [
			'title' => 'Browse Category Library Updates',
			'description' => 'Update Browse Category to store the library the category belongs to',
			'sql' => [
				'ALTER TABLE browse_category ADD COLUMN libraryId INT(11) DEFAULT -1',
				'updateBrowseCategoryLibraries',
			],
		],
	];
}

/** @noinspection PhpUnused */
function populateBrowseCategoryGroups() {
	//Convert library browse categories to browse category groups
	global $aspen_db;

	//Create a browse category for recommended for you
	require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
	$browseCategory = new BrowseCategory();
	$browseCategory->textId = 'system_recommended_for_you';
	$browseCategory->label = 'Recommended For You';
	$browseCategory->insert();

	$librarySQL = "SELECT libraryId, displayName, defaultBrowseMode, browseCategoryRatingsMode FROM library";
	$librariesRS = $aspen_db->query($librarySQL, PDO::FETCH_ASSOC);
	$libraryRow = $librariesRS->fetch();

	require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
	while ($libraryRow != null) {
		$defaultBrowseMode = $libraryRow['defaultBrowseMode'];
		if ($defaultBrowseMode == 'covers') {
			$defaultBrowseMode = 0;
		} else {
			$defaultBrowseMode = 1;
		}
		$browseCategoryRatingsMode = $libraryRow['browseCategoryRatingsMode'];
		if ($browseCategoryRatingsMode == 'popup') {
			$browseCategoryRatingsMode = 1;
		} elseif ($browseCategoryRatingsMode == 'stars') {
			$browseCategoryRatingsMode = 2;
		} else {
			$browseCategoryRatingsMode = 0;
		}

		$libraryBrowseCategories = [];
		$libraryBrowseCategorySQL = "SELECT browse_category.id as browse_category_id from browse_category_library inner join browse_category on browseCategoryTextId = textId WHERE libraryId = {$libraryRow['libraryId']} order by weight";
		$libraryBrowseCategoryRS = $aspen_db->query($libraryBrowseCategorySQL, PDO::FETCH_ASSOC);
		$libraryBrowseCategoryRow = $libraryBrowseCategoryRS->fetch();
		while ($libraryBrowseCategoryRow != null) {
			$libraryBrowseCategories[] = $libraryBrowseCategoryRow['browse_category_id'];
			$libraryBrowseCategoryRow = $libraryBrowseCategoryRS->fetch();
		}

		$createNewGroup = true;
		$browseCategoryGroup = new BrowseCategoryGroup();
		$browseCategoryGroup->defaultBrowseMode = $defaultBrowseMode;
		$browseCategoryGroup->browseCategoryRatingsMode = $browseCategoryRatingsMode;
		$browseCategoryGroup->find();
		while ($browseCategoryGroup->fetch()) {
			//Verify that the browse categories are correct
			$browseCategories = $browseCategoryGroup->getBrowseCategories();
			if (count($libraryBrowseCategories) == count($browseCategories)) {
				$index = 0;
				$allMatch = true;
				foreach ($browseCategories as $id => $browseCategory) {
					if ($libraryBrowseCategories[$index] != $id) {
						$allMatch = false;
						break;
					}
					$index++;
				}
				if ($allMatch) {
					$createNewGroup = false;
					$library = new Library();
					$library->libraryId = $libraryRow['libraryId'];
					$library->find(true);
					$library->browseCategoryGroupId = $browseCategoryGroup->id;
					$library->update();
					break;
				}
			}
		}

		if ($createNewGroup) {
			//Create the group
			$browseCategoryGroup = new BrowseCategoryGroup();
			$browseCategoryGroup->name = $libraryRow['displayName'];
			$browseCategoryGroup->defaultBrowseMode = $defaultBrowseMode;
			$browseCategoryGroup->browseCategoryRatingsMode = $browseCategoryRatingsMode;
			$browseCategoryGroup->insert();

			//Add the browse categories
			foreach ($libraryBrowseCategories as $index => $id) {
				$browseCategoryGroupEntry = new BrowseCategoryGroupEntry();
				$browseCategoryGroupEntry->browseCategoryGroupId = $browseCategoryGroup->id;
				$browseCategoryGroupEntry->browseCategoryId = $id;
				$browseCategoryGroupEntry->weight = $index;
				$browseCategoryGroupEntry->insert();
			}

			//Link the group to the library
			$library = new Library();
			$library->libraryId = $libraryRow['libraryId'];
			$library->find(true);
			$library->browseCategoryGroupId = $browseCategoryGroup->id;
			$library->update();
		}
		$libraryRow = $librariesRS->fetch();
	}

	$locationSQL = "SELECT locationId, displayName, defaultBrowseMode, browseCategoryRatingsMode FROM location";
	$locationsRS = $aspen_db->query($locationSQL, PDO::FETCH_ASSOC);
	$locationRow = $locationsRS->fetch();

	require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
	while ($locationRow != null) {
		$defaultBrowseMode = $locationRow['defaultBrowseMode'];
		if ($defaultBrowseMode == 'covers' || empty($defaultBrowseMode)) {
			$defaultBrowseMode = 0;
		} else {
			$defaultBrowseMode = 1;
		}
		$browseCategoryRatingsMode = $locationRow['browseCategoryRatingsMode'];
		if ($browseCategoryRatingsMode == 'popup') {
			$browseCategoryRatingsMode = 1;
		} elseif ($browseCategoryRatingsMode == 'stars') {
			$browseCategoryRatingsMode = 2;
		} else {
			$browseCategoryRatingsMode = 0;
		}

		$locationBrowseCategories = [];
		$locationBrowseCategorySQL = "SELECT browse_category.id as browse_category_id from browse_category_location inner join browse_category on browseCategoryTextId = textId WHERE locationId = {$locationRow['locationId']} order by weight";
		$locationBrowseCategoryRS = $aspen_db->query($locationBrowseCategorySQL, PDO::FETCH_ASSOC);
		$locationBrowseCategoryRow = $locationBrowseCategoryRS->fetch();
		while ($locationBrowseCategoryRow != null) {
			$locationBrowseCategories[] = $locationBrowseCategoryRow['browse_category_id'];
			$locationBrowseCategoryRow = $locationBrowseCategoryRS->fetch();
		}

		if (count($locationBrowseCategories) == 0) {
			$location = new location();
			$location->locationId = $locationRow['locationId'];
			$location->find(true);
			if ($location->getParentLibrary()->browseCategoryGroupId == $browseCategoryGroup->id) {
				$location->browseCategoryGroupId = -1;
			} else {
				$location->browseCategoryGroupId = $browseCategoryGroup->id;
			}
			$location->update();
		} else {
			$createNewGroup = true;
			$browseCategoryGroup = new BrowseCategoryGroup();
			$browseCategoryGroup->defaultBrowseMode = $defaultBrowseMode;
			$browseCategoryGroup->browseCategoryRatingsMode = $browseCategoryRatingsMode;
			$browseCategoryGroup->find();
			while ($browseCategoryGroup->fetch()) {
				//Verify that the browse categories are correct
				$browseCategories = $browseCategoryGroup->getBrowseCategories();
				if (count($locationBrowseCategories) == count($browseCategories)) {
					$index = 0;
					$allMatch = true;
					foreach ($browseCategories as $id => $browseCategory) {
						if ($locationBrowseCategories[$index] != $id) {
							$allMatch = false;
							break;
						}
						$index++;
					}
					if ($allMatch) {
						$createNewGroup = false;
						$location = new location();
						$location->locationId = $locationRow['locationId'];
						$location->find(true);
						if ($location->getParentLibrary()->browseCategoryGroupId == $browseCategoryGroup->id) {
							$location->browseCategoryGroupId = -1;
						} else {
							$location->browseCategoryGroupId = $browseCategoryGroup->id;
						}
						$location->update();
						break;
					}
				}
			}

			if ($createNewGroup) {
				//Create the group
				$browseCategoryGroup = new BrowseCategoryGroup();
				$browseCategoryGroup->name = $locationRow['displayName'];
				$browseCategoryGroup->defaultBrowseMode = $defaultBrowseMode;
				$browseCategoryGroup->browseCategoryRatingsMode = $browseCategoryRatingsMode;
				$browseCategoryGroup->insert();

				//Add the browse categories
				foreach ($locationBrowseCategories as $index => $id) {
					$browseCategoryGroupEntry = new BrowseCategoryGroupEntry();
					$browseCategoryGroupEntry->browseCategoryGroupId = $browseCategoryGroup->id;
					$browseCategoryGroupEntry->browseCategoryId = $id;
					$browseCategoryGroupEntry->weight = $index;
					$browseCategoryGroupEntry->insert();
				}

				//Link the group to the location
				$location = new location();
				$location->locationId = $locationRow['locationId'];
				$location->find(true);
				$location->browseCategoryGroupId = $browseCategoryGroup->id;
				$location->update();
			}
		}

		$locationRow = $locationsRS->fetch();
	}
}

/** @noinspection PhpUnused */
function updateBrowseCategorySources() {
	require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
	$validSources = BaseBrowsable::getBrowseSources();
	$browseCategories = new BrowseCategory();
	$browseCategories->find();
	$allBrowseCategories = [];
	while ($browseCategories->fetch()) {
		$allBrowseCategories[] = clone $browseCategories;
	}
	foreach ($allBrowseCategories as $index => $browseCategory) {
		if (empty($browseCategory->source)) {
			if (!array_key_exists($browseCategory->source, $validSources)) {
				if (!empty($browseCategory->sourceListId) && $browseCategory->sourceListId != -1) {
					$browseCategory->source = 'List';
				} else {
					$browseCategory->source = 'GroupedWork';
				}
				$browseCategory->update();
			}
		}
	}
}

/** @noinspection PhpUnused */
function updateBrowseCategoryLibraries() {
	require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
	$browseCategory = new BrowseCategory();
	$browseCategory->find();
	$allBrowseCategories = [];
	$users = [];
	while ($browseCategory->fetch()) {
		if (!array_key_exists($browseCategory->userId, $users)) {
			$user = new User();
			$user->id = $browseCategory->userId;
			if ($user->find(true)) {
				$users[$user->id] = $user;
			} else {
				continue;
			}
		}
		$userLibrary = $user->getHomeLibrary();
		if ($userLibrary != null) {
			$browseCategory->libraryId = $userLibrary->libraryId;
		}
		$browseCategory->update();
	}
}