<?php

class OverDriveAPIProduct extends DataObject{
	public $__table = 'overdrive_api_products';   // table name

	public $id;
	public $overdriveId;
	public $crossRefId;
	public $mediaType;
	public $title;
	public $subtitle;
	public $series;
	public $primaryCreatorRole;
	public $primaryCreatorName;
	public $cover;
	public $dateAdded;
	public $dateUpdated;
	public $lastMetadataCheck;
	public $lastMetadataChange;
	public $lastAvailabilityCheck;
	public $lastAvailabilityChange;
	public $deleted;
	public $dateDeleted;
}