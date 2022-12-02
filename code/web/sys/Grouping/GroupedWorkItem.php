<?php


class GroupedWorkItem extends DataObject {
	public $__table = 'grouped_work_record_items';
	public $groupedWorkRecordId;
	public $groupedWorkVariationId;
	public $itemId;
	public $shelfLocationId;
	public $callNumberId;
	public $sortableCallNumberId;
	public $numCopies;
	public $isOrderItem;
	public $statusId;
	public $dateAdded;
	public $locationCodeId;
	public $subLocationCodeId;
	public $lastCheckInDate;
	public $groupedStatusId;
	public $available;
	public $holdable;
	public $inLibraryUseOnly;
	public $locationOwnedScopes;
	public $libraryOwnedScopes;
	public $recordIncludedScopes;
}