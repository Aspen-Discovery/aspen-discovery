<?php

class IlsMarcChecksum extends DataObject {
	public $__table = 'ils_marc_checksums';    // table name
	public $id;
	public $ilsId;
	public $checksum;
	public $dateFirstDetected;
	public $source;
}