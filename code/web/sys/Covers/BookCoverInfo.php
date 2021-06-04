<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class BookCoverInfo extends DataObject
{
	public $__table = 'bookcover_info';    // table name
	public $id;
	public $recordType;
	public $recordId;
	public $firstLoaded;
	public $lastUsed;
	public $imageSource;
	public $sourceWidth;
	public $sourceHeight;
	public $thumbnailLoaded;
	public $mediumLoaded;
	public $largeLoaded;
	public $uploadedImage;

	public function getNumericColumnNames() : array
	{
		return ['sourceWidth', 'sourceHeight', 'thumbnailLoaded', 'mediumLoaded', 'largeLoaded', 'uploadedImage'];
	}

	public function reloadAllDefaultCovers()
	{
		$this->query("UPDATE " . $this->__table . " SET thumbnailLoaded = 0, mediumLoaded = 0, largeLoaded = 0 where imageSource = 'default'");
	}
}