<?php /** @noinspection PhpMissingFieldTypeInspection */

class GroupedWorkRecord extends DataObject {
	public $__table = 'grouped_work_records';
	public $id;
	public $groupedWorkId;
	public $sourceId;
	public $recordIdentifier;
	public $formatId;
	public $formatCategoryId;
	public $editionId;
	public $audienceId;
	public $publisherId;
	public $publicationDateId;
	public $placeOfPublicationId;
	public $physicalDescriptionId;
	public $durationId;
	public $languageId;
	public $isClosedCaptioned;
}