<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class IlsVolumeInfo extends DataObject{
	public $__table = 'ils_volume_info';    // table name
	public $id;
	public $recordId;
	public $displayLabel;
	public $relatedItems;
	public $volumeId;
	public $displayOrder;
}