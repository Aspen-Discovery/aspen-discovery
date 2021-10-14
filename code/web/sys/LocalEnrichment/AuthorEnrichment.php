<?php

class AuthorEnrichment extends DataObject
{
	public $__table = 'author_enrichment';    // table name
	public $id;
	public $authorName;
	public $hideWikipedia;
	public $wikipediaUrl;

	static function getObjectStructure() : array
	{
		return [
			[
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the enrichment information',
				'storeDb' => true,
			],
			[
				'property' => 'authorName',
				'type' => 'text',
				'size' => '255',
				'maxLength' => 255,
				'label' => 'Author Name (100ad)',
				'description' => 'The name of the author including any dates',
				'storeDb' => true,
				'required' => true,
			],
			[
				'property' => 'hideWikipedia',
				'type' => 'checkbox',
				'label' => 'Hide Wikipedia Information',
				'description' => 'Check to not show Wikipedia data for this author',
				'storeDb' => true,
				'required' => false,
			],
			[
				'property' => 'wikipediaUrl',
				'type' => 'text',
				'size' => '255',
				'maxLength' => 255,
				'label' => 'Wikipedia URL',
				'description' => 'The URL to load Wikipedia data from.',
				'storeDb' => true,
				'required' => false,
			],
		];
	}
}