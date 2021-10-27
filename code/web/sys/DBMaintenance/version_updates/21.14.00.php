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