<?php /** @noinspection PhpMissingFieldTypeInspection */

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
	public $_needsIllRequest;
	public $_allItems = [];

	public function setHasLocalItems(bool $hasLocalItems) : void {
		$this->_hasLocalItems = $hasLocalItems;
	}

	public function hasLocalItems(): bool {
		return (bool)$this->_hasLocalItems;
	}

	public function setNeedsIllRequest(bool $needsIllRequest) : void {
		$this->_needsIllRequest = $needsIllRequest;
	}

	public function needsIllRequest(): bool {
		return (bool)$this->_needsIllRequest;
	}

	public function addItem($item) : void {
		$this->_allItems[] = $item;
	}

	public function getItems() {
		return $this->_allItems;
	}
}