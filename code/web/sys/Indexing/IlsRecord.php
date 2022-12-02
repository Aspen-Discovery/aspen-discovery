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
	public $suppressed;
	public $suppressionNotes;

	public function getNumericColumnNames(): array {
		return [
			'suppressed',
			'deleted',
			'dateFirstDetected',
			'dateDeleted',
			'suppressedNoMarcAvailable',
		];
	}

	public function getCompressedColumnNames(): array {
		return ['sourceData'];
	}
}