<?php

class IlsRecord extends DataObject {
	public $__table = 'ils_records';    // table name
	public $id;
	public $ilsId;
	public $checksum;
	public $dateFirstDetected;
	public $deleted;
	public $dateDeleted;
	public $suppressedNoMarcAvailable;
	public $source;
	public $sourceData;
	public $lastModified;

	public function getCompressedColumnNames(): array
	{
		return ['sourceData'];
	}
}