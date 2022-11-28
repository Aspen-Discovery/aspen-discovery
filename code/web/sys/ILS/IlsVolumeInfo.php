<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class IlsVolumeInfo extends DataObject {
	public $__table = 'ils_volume_info';    // table name
	public $id;
	public $recordId;
	public $displayLabel;
	public $relatedItems;
	public $volumeId;
	public $displayOrder;

	public $_hasLocalItems;

	public function setHasLocalItems(bool $hasLocalItems) {
		$this->_hasLocalItems = $hasLocalItems;
	}

	public function hasLocalItems(): bool {
		return $this->_hasLocalItems;
	}
}