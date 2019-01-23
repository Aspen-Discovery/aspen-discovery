<?php
/**
 * Stores information about a product that has been loaded from the OverDrive APIs
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/8/13
 * Time: 9:28 AM
 */

class OverDriveAPIProduct extends DB_DataObject{
	public $__table = 'overdrive_api_products';   // table name

	public $id;
	public $overdriveId;
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
	public $rawData;
	public $needsUpdate;
}