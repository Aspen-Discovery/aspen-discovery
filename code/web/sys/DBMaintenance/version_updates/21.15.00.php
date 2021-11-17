<?php
/** @noinspection PhpUnused */
function getUpdates21_15_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'omdb_disableCoversWithNoDates' => [
			'title' => 'OMDB - Disable Covers With No Dates',
			'description' => 'Allow loading covers with no dates to be disabled',
			'sql' => [
				'ALTER TABLE omdb_settings ADD COLUMN fetchCoversWithoutDates TINYINT(1) DEFAULT 1',
			]
		], //omdb_disableCoversWithNoDates
		'checkoutFormatLength' => [
			'title' => 'Increase Format Length for Checkout',
			'description' => 'Increase Format Length for Checkouts',
			'sql' => [
				'alter table user_checkout change column format format VARCHAR(75) DEFAULT NULL;'
			]
		], //checkoutFormatLength
		'overdrive_useFulfillmentInterface' => [
			'title' => 'OverDrive - Enable updated checkout fulfillment interface',
			'description' => 'Enable updated checkout fulfillment interface',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN useFulfillmentInterface TINYINT(1) DEFAULT 0',
			]
		], //overdrive_useFulfillmentInterface
		'account_profile_increaseDatabaseNameLength' => [
			'title' => 'Account Profile - Increase Database Name Length',
			'description' => 'Increase datbase name length for Account Profiles',
			'sql' => [
				"ALTER TABLE account_profiles CHANGE COLUMN databaseName databaseName VARCHAR(75)",
			]
		], //account_profile_increaseDatabaseNameLength
		'browseCategoryDismissal' => [
			'title' => 'Add browse_category_dismissal table',
			'description' => 'Enables the ability to hide browse categories by the user',
			'sql' => [
				'CREATE TABLE browse_category_dismissal (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							browseCategoryId INT,
							userId INT,
							UNIQUE INDEX userBrowseCategory(userId, browseCategoryId)
						) ENGINE = INNODB;',
			]
		], //browseCategoryDismissal
		'overdrive_showLibbyPromo' => [
			'title' => 'OverDrive - Enable show/hide Libby promo',
			'description' => 'Enable show/hide option for Libby promo in OverDrive fulfillment interface',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN showLibbyPromo TINYINT(1) DEFAULT 1',
			]
		], //overdrive_showLibbyPromo
		'search_increaseTitleLength' => [
			'title' => 'Saved Search - Increase Title Length',
			'description' => 'Increase title length for Saved Searches',
			'sql' => [
				"ALTER TABLE search CHANGE COLUMN title title VARCHAR(225)",
			]
		], //search_increaseTitleLength
	];

}