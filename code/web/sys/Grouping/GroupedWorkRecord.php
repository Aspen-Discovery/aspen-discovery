<?php


class GroupedWorkRecord extends DataObject {
	public $__table = 'grouped_work_records';
	public $id;
	public $groupedWorkId;
	public $sourceId;
	public $recordIdentifier;
	public $formatId;
	public $formatCategoryId;
	public $editionId;
	public $publisherId;
	public $publicationDateId;
	public $physicalDescriptionId;
	public $languageId;
	public $isClosedCaptioned;
}