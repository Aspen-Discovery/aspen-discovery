<?php
require_once ROOT_DIR . '/sys/User/CircEntry.php';

class Hold extends CircEntry
{
	public $__table = 'user_hold';
	public $shortId;
	public $itemId;
	public $title2;
	public $volume;
	public $callNumber;
	public $available;
	public $cancelable;
	public $cancelId;
	public $locationUpdateable;
	public $pickupLocationId;
	public $pickupLocationName;
	public $status;
	public $position;
	public $holdQueueLength;
	public $createDate;
	public $availableDate;
	public $expirationDate;
	public $automaticCancellationDate;
	public $frozen;
	public $canFreeze;
	public $reactivateDate;
	public $format;

	//Try to get rid of
	public $_freezeError;

	public function getNumericColumnNames()
	{
		return ['userId', 'available', 'cancelable', 'locationUpdateable', 'position', 'holdQueueLength', 'createDate', 'availableDate', 'expirationDate', 'automaticCancellationDate', 'frozen', 'canFreeze', 'reactivateDate'];
	}

	/** @noinspection PhpUnused */
	public function getPreviewActions(){
		$recordDriver = $this->getRecordDriver();
		if ($recordDriver != false){
			return $recordDriver->getPreviewActions();
		}else{
			return null;
		}
	}

	public function getArrayForAPIs(){
		$hold = $this->toArray();
		if ($hold['type'] == 'ils') {
			$hold['holdSource'] = 'ILS';
		}elseif ($hold['type'] == 'cloud_library') {
			$hold['holdSource'] = 'CloudLibrary';
		}elseif ($hold['type'] == 'axis360') {
			$hold['holdSource'] = 'Axis360';
		}
		$hold['id'] = $hold['sourceId'];
		$hold['ratingData'] = $this->getRatingData();
		$hold['coverUrl'] = $this->getCoverUrl();
		$hold['link'] = $this->getLinkUrl();
		$hold['linkUrl'] = $this->getLinkUrl();
		$hold['transactionId'] = $hold['sourceId'];
		$hold['sortTitle'] = $this->getSortTitle();
		$hold['user'] = $this->getUserName();
		$hold['create'] = $hold['createDate'];
		$hold['expire'] = $hold['expirationDate'];
		$hold['automaticCancellation'] = $hold['automaticCancellationDate'];
		if ($this->type == 'ils') {
			$hold['format'] = $this->getFormats();
		}
		$hold['allowFreezeHolds'] = $this->canFreeze ? "1" : "0";
		$hold['freezable'] = $this->canFreeze;
		if ($this->pickupLocationId != null) {
			$hold['currentPickupId'] = $this->pickupLocationId;
			$hold['currentPickupName'] = $this->pickupLocationName;
			$location = new Location();
			$location->locationId = $this->pickupLocationId;
			if ($location->find(true)){
				$hold['currentPickupId'] = $location->code;
				$hold['location'] = $location->code;
			}
		}
		$recordDriver = $this->getRecordDriver();
		if ($recordDriver && $recordDriver->isValid()){
			$hold['isbn'] = $recordDriver->getCleanISBN();
			$hold['upc'] = $recordDriver->getUPC();
			$hold['format_category'] = $recordDriver->getFormatCategory();
		}
		return $hold;
	}
}