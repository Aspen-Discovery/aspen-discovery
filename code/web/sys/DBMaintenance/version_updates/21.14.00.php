<?php
/** @noinspection PhpUnused */
function getUpdates21_14_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'user_list_course_reserves' => [
			'title' => 'User List Course Reserves',
			'description' => 'Add Course Reserve Information to User Lists',
			'sql' => [
				'ALTER TABLE user_list ADD COLUMN isCourseReserve TINYINT(1) DEFAULT 0',
				'ALTER TABLE user_list ADD COLUMN courseInstructor VARCHAR(100)',
				'ALTER TABLE user_list ADD COLUMN courseNumber VARCHAR(50)',
				'ALTER TABLE user_list ADD COLUMN courseTitle VARCHAR(200)',
			]
		], //user_list_course_reserves
		'addLastSeenToOverDriveProducts' => [
			'title' => 'Add Last Seen to OverDrive Products',
			'description' => 'Add Last Seen to OverDrive Availability so we can detect deletions',
			'sql' => [
				'ALTER TABLE overdrive_api_products ADD COLUMN lastSeen INT(11) DEFAULT 0'
			]
		], //addLastSeenToOverDriveProducts
		'loadBadWords' => [
			'title' => 'Load Bad Words',
			'description' => 'Load the Bad Words List',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE bad_words DROP COLUMN replacement',
				'importBadWords',
			]
		], //loadBadWords
		'greenhouseMonitoring' => [
			'title' => 'Greenhouse Monitoring',
			'description' => 'Store Additional information within the Greenhouse for monitoring',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE aspen_sites ADD COLUMN version VARCHAR(25)',
				'ALTER TABLE aspen_sites ADD COLUMN sendErrorNotificationsTo VARCHAR(250)',
				'ALTER TABLE aspen_sites ADD COLUMN slackNotificationChannel VARCHAR(50)',
				'CREATE TABLE aspen_site_checks (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					siteId INT NOT NULL,
					checkName VARCHAR(50),
					currentStatus TINYINT(1),
					currentNote VARCHAR(500),
					lastOkTime INT,
					lastWarningTime INT,
					lastErrorTime INT,
					UNIQUE (siteId, checkName)
				) ENGINE INNODB',
				'CREATE TABLE aspen_site_stats (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					siteId INT NOT NULL,
					year INT(4) NOT NULL,
					month INT(2) NOT NULL,
					day INT(2) NOT NULL,
					minDataDiskSpace FLOAT,
					minUsrDiskSpace FLOAT,
					minAvailableMemory FLOAT,
					maxAvailableMemory FLOAT,
					minLoadPerCPU FLOAT,
					maxLoadPerCPU FLOAT,
					maxWaitTime FLOAT,
					UNIQUE (siteId, year, month, day)
				) ENGINE INNODB'
			]
		], //greenhouseMonitoring
		'greenhouseSlackIntegration' => [
			'title' => 'Greenhouse Slack Integration',
			'description' => 'Greenhouse Slack Integration',
			'sql' => [
				'CREATE TABLE greenhouse_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					greenhouseAlertSlackHook VARCHAR(255)
				) ENGINE INNODB',
			]
		], //greenhouseSlackIntegration
		'greenhouseMonitoring2' => [
			'title' => 'Greenhouse Monitoring 2',
			'description' => 'Store Additional information within the Greenhouse for monitoring',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE aspen_sites DROP COLUMN slackNotificationChannel',
				'ALTER TABLE aspen_sites ADD COLUMN lastNotificationTime INT(11)',
			]
		], //greenhouseMonitoring2
		'displayMaterialsRequestToPublic' => [
			'title' => 'Add displayMaterialsRequestToPublic',
			'description' => 'Add option to have Materials Request enabled but hidden to patrons',
			'sql' => [
				'ALTER TABLE library ADD COLUMN displayMaterialsRequestToPublic TINYINT(1) DEFAULT 1',
			]
		], //displayMaterialsRequestToPublic
		'showTopOfPageButton' => [
			'title' => 'Add showTopOfPageButton',
			'description' => 'Add option to have button that goes to top of page in Layout Settings',
			'sql' => [
				'ALTER TABLE layout_settings ADD COLUMN showTopOfPageButton TINYINT(1) DEFAULT 1',
				'updateAllThemes',
			]
		], //showTopOfPageButton
		'dismissPlacardButtonLocation' => [
			'title' => 'Add dismissPlacardButtonLocation',
			'description' => 'Add option to move button to dismiss placard in top right corner in Layout Settings',
			'sql' => [
				'ALTER TABLE layout_settings ADD COLUMN dismissPlacardButtonLocation TINYINT(1) DEFAULT 0',
			]
		], //dismissPlacardButtonLocation
		'dismissPlacardButtonIcon' => [
			'title' => 'Add dismissPlacardButtonIcon',
			'description' => 'Add option to change dismiss placard button to X icon instead of text in Layout Settings',
			'sql' => [
				'ALTER TABLE layout_settings ADD COLUMN dismissPlacardButtonIcon TINYINT(1) DEFAULT 0',
			]
		], //dismissPlacardButtonIcon
	];
}

function importBadWords(){
	$fhnd = fopen(ROOT_DIR . "/sys/DBMaintenance/badwords.txt", 'r');
	while ($word = fgets($fhnd)) {
		require_once ROOT_DIR . '/sys/LocalEnrichment/BadWord.php';
		$badWord = new BadWord();
		$badWord->word = trim($word);
		if (strlen($badWord->word) > 0) {
			$badWord->insert();
		}
	}
	fclose($fhnd);
	/** @var $memCache Memcache */
	global $memCache;
	$memCache->delete('bad_words_list');
}