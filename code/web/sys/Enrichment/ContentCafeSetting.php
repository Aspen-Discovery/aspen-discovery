<?php


class ContentCafeSetting extends DataObject
{
	public $__table = 'contentcafe_settings';    // table name
	public $id;
	public $contentCafeId;
	public $pwd;
	public $hasSummary;
	public $hasToc;
	public $hasExcerpt;
	public $hasAuthorNotes;

	public static function getObjectStructure() : array
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'contentCafeId' => array('property' => 'contentCafeId', 'type' => 'text', 'label' => 'Content Cafe Id', 'description' => 'The ID of the Content Cafe subscription'),
			'pwd' => array('property' => 'pwd', 'type' => 'storedPassword', 'label' => 'Profile Password', 'description' => 'The password for the Profile', 'hideInLists' => true),
			'hasSummary' => array('property' => 'hasSummary', 'type' => 'checkbox', 'label' => 'Has Summary', 'description' => 'Whether or not the summary is available in the subscription', 'default' => 1),
			'hasToc' => array('property' => 'hasToc', 'type' => 'checkbox', 'label' => 'Has Table of Contents', 'description' => 'Whether or not the table of contents is available in the subscription'),
			'hasExcerpt' => array('property' => 'hasExcerpt', 'type' => 'checkbox', 'label' => 'Has Excerpt', 'description' => 'Whether or not the excerpt is available in the subscription'),
			'hasAuthorNotes' => array('property' => 'hasAuthorNotes', 'type' => 'checkbox', 'label' => 'Has Author Notes', 'description' => 'Whether or not author notes are available in the subscription'),
		);
	}
}