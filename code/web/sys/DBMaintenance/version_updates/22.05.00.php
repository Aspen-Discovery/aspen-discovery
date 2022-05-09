<?php
/** @noinspection PhpUnused */
function getUpdates22_05_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'footerText' => [
			'title' => 'Add Footer Text to Library',
			'description' => 'Add Footer Text to Library',
			'sql' => [
				'ALTER TABLE library ADD COLUMN footerText MEDIUMTEXT',
			]
		], //footerText
		'force_website_reindex_22_05' => [
			'title' => 'Force Website Reindex 22.05',
			'description' => 'Force all website content to be reindexed',
			'sql' => [
				'UPDATE website_indexing_settings set lastIndexed = 0',
			]
		], //force_website_reindex_22_05
		'website_crawlDelay' => [
			'title' => 'Website Crawl Delay',
			'description' => 'Add a crawl delay to slow down indexing pages',
			'sql' => [
				'ALTER TABLE website_indexing_settings ADD COLUMN crawlDelay INT DEFAULT 10',
			]
		], //website_crawlDelay
		'paypal_error_email' => [
			'title' => 'PayPal Error Email',
			'description' => 'Add Error Email to PayPal settings',
			'sql' => [
				"ALTER TABLE paypal_settings ADD COLUMN errorEmail VARCHAR(128) DEFAULT ''",
			]
		], //paypal_error_email
		'open_archives_deleted_collections' => [
			'title' => 'Open Archives Deleted Collections',
			'description' => 'Add a flag to collections when they are deleted',
			'sql' => [
				"ALTER TABLE open_archives_collection ADD COLUMN deleted TINYINT(1) DEFAULT 0",
			]
		], //open_archives_deleted_collections
		'open_archives_reindex_all_collections_22_05' => [
			'title' => 'Open Archives Reindex all Collections',
			'description' => 'Reindex all open archives collections',
			'sql' => [
				"UPDATE open_archives_collection SET lastFetched = 0",
			]
		], //open_archives_deleted_collections
	];
}
