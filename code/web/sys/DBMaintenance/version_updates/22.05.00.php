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
	];
}
