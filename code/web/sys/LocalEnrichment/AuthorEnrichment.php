<?php
/** @noinspection PhpMissingFieldTypeInspection */

class AuthorEnrichment extends DataObject {
	public $__table = 'author_enrichment';
	public $id;
	public $authorName;
	public $hideWikipedia;
	public $wikipediaUrl;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$structure = [
			[
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of this setting.',
				'storeDb' => true,
			],
			[
				'property' => 'authorName',
				'type' => 'text',
				'size' => '255',
				'maxLength' => 255,
				'label' => 'Author Name',
				'description' => 'The exact author name used for Wikipedia lookup.',
				'note' => 'This must match the author\'s hyperlinked name on the grouped work. When debug mode is enabled for your <a href="/Admin/IPAddresses" target="_blank">IP Address</a> and the Wikipedia lookup has failed, you can view the exact search term used on the Author page via the displayed debug message.',
				'storeDb' => true,
				'required' => true,
			],
			[
				'property' => 'hideWikipedia',
				'type' => 'checkbox',
				'label' => 'Hide Wikipedia Information',
				'description' => 'Whether to hide Wikipedia data for this author.',
				'storeDb' => true,
				'required' => false,
			],
			[
				'property' => 'wikipediaUrl',
				'type' => 'text',
				'size' => '255',
				'maxLength' => 255,
				'label' => 'Wikipedia URL',
				'description' => 'The URL from which to load Wikipedia data.',
				'storeDb' => true,
				'required' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}