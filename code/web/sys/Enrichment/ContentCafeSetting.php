<?php


class ContentCafeSetting extends DataObject {
	public $__table = 'contentcafe_settings';    // table name
	public $id;
	public $contentCafeId;
	public $pwd;
	public $enabled;
	public $hasSummary;
	public $hasToc;
	public $hasExcerpt;
	public $hasAuthorNotes;

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'contentCafeId' => [
				'property' => 'contentCafeId',
				'type' => 'text',
				'label' => 'Content Cafe Id',
				'description' => 'The ID of the Content Cafe subscription',
			],
			'pwd' => [
				'property' => 'pwd',
				'type' => 'storedPassword',
				'label' => 'Profile Password',
				'description' => 'The password for the Profile',
				'hideInLists' => true,
			],
			'enabled' => [
				'property' => 'enabled',
				'type' => 'checkbox',
				'label' => 'Integration Enabled',
				'description' => 'Whether or not content cafe integration is disabled',
				'default' => 1,
			],
			'hasSummary' => [
				'property' => 'hasSummary',
				'type' => 'checkbox',
				'label' => 'Has Summary',
				'description' => 'Whether or not the summary is available in the subscription',
				'default' => 1,
			],
			'hasToc' => [
				'property' => 'hasToc',
				'type' => 'checkbox',
				'label' => 'Has Table of Contents',
				'description' => 'Whether or not the table of contents is available in the subscription',
			],
			'hasExcerpt' => [
				'property' => 'hasExcerpt',
				'type' => 'checkbox',
				'label' => 'Has Excerpt',
				'description' => 'Whether or not the excerpt is available in the subscription',
			],
			'hasAuthorNotes' => [
				'property' => 'hasAuthorNotes',
				'type' => 'checkbox',
				'label' => 'Has Author Notes',
				'description' => 'Whether or not author notes are available in the subscription',
			],
		];
	}
}