<?php
require_once ROOT_DIR . '/sys/User/CircEntry.php';

class Hold extends CircEntry {
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
	public $isIll;
	public $pendingCancellation;

	//For Palace Project
	public $cancellationUrl;

	//Try to get rid of
	public $_freezeError;

	public function getNumericColumnNames(): array {
		return [
			'userId',
			'available',
			'cancelable',
			'locationUpdateable',
			'position',
			'holdQueueLength',
			'createDate',
			'availableDate',
			'expirationDate',
			'automaticCancellationDate',
			'frozen',
			'canFreeze',
			'reactivateDate',
			'isIll',
			'pendingCancellation',
		];
	}

	/** @noinspection PhpUnused */
	public function getPreviewActions() {
		$recordDriver = $this->getRecordDriver();
		if ($recordDriver != false) {
			return $recordDriver->getPreviewActions();
		} else {
			return null;
		}
	}

	public function getArrayForAPIs() {
		$hold = $this->toArray();
		if ($hold['type'] == 'ils') {
			$hold['holdSource'] = 'ILS';
		} elseif ($hold['type'] == 'cloud_library') {
			$hold['holdSource'] = 'CloudLibrary';
		} elseif ($hold['type'] == 'axis360') {
			$hold['holdSource'] = 'Axis360';
		} elseif ($hold['type'] == 'palace_project') {
			$hold['holdSource'] = 'Palace Project';
		} elseif ($hold['type'] == 'overdrive') {
			global $configArray;
			$hold['holdSource'] = 'OverDrive';
			$hold['overDriveId'] = $hold['sourceId'];
			$hold['holdQueuePosition'] = (int)$hold['position'];
			$hold['recordUrl'] = $configArray['Site']['url'] . $this->getLinkUrl();
			if ($this->getRecordDriver()) {
				$hold['previewActions'] = $this->getRecordDriver()->getPreviewActions();
			}
		}
		$hold['id'] = $hold['sourceId'];
		$hold['available'] = $hold['available'] == 1;
		$hold['ratingData'] = $this->getRatingData();
		$hold['coverUrl'] = $this->getCoverUrl();
		$hold['link'] = $this->getLinkUrl();
		$hold['linkUrl'] = $this->getLinkUrl();
		$hold['transactionId'] = $hold['sourceId'];
		$hold['sortTitle'] = $this->getSortTitle();
		$hold['user'] = $this->getUserName();
		$hold['create'] = (int)$hold['createDate'];
		$hold['expire'] = $hold['expirationDate'];
		$hold['frozen'] = (boolean)$hold['frozen'];
		$hold['cancelable'] = (boolean)$hold['cancelable'];
		if ($hold['automaticCancellationDate'] == 0 || empty($hold['automaticCancellationDate'])) {
			$hold['automaticCancellation'] = null;
			$hold['automaticCancellationDate'] = null;
		} else {
			$hold['automaticCancellation'] = $hold['automaticCancellationDate'];
		}
		if ($this->type == 'ils' || $this->type == 'overdrive') {
			$hold['format'] = $this->getFormats();
		}
		$hold['allowFreezeHolds'] = $this->canFreeze ? "1" : "0";
		$hold['freezable'] = (boolean)$this->canFreeze;
		$hold['canFreeze'] = (boolean)$this->canFreeze;
		if ($this->pickupLocationId != null) {
			$hold['pickupLocationId'] = (string)$this->pickupLocationId;
			$hold['currentPickupId'] = (string)$this->pickupLocationId;
			$hold['currentPickupName'] = $this->pickupLocationName;
			$location = new Location();
			$location->locationId = $this->pickupLocationId;
			if ($location->find(true)) {
				$hold['currentPickupId'] = (string)$location->code;
				$hold['location'] = $location->code;
			}
		}
		$hold['status'] = translate(['text' => $hold['status'], 'isPublicFacing' => true]);
		$recordDriver = $this->getRecordDriver();
		if ($recordDriver && $recordDriver->isValid()) {
			$hold['isbn'] = $recordDriver->getCleanISBN();
			$hold['upc'] = $recordDriver->getUPC();
			$hold['format_category'] = $recordDriver->getFormatCategory();
		}
		$hold['isIll'] = (boolean)$this->isIll;
		$hold['position'] = (int)$hold['position'];
		$hold['pendingCancellation'] = $this->pendingCancellation;
		return $hold;
	}

	public function getFormats() {
		if ($this->format == null) {
			$recordDriver = $this->getRecordDriver();
			if ($recordDriver != false) {
				return $recordDriver->getFormats();
			} else {
				return [];
			}
		} else {
			return [$this->format];
		}
	}

	private function performPreSaveChecks() {
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		if (strlen($this->title) > 500) {
			$this->title = StringUtils::trimStringToLengthAtWordBoundary($this->title, 500, true);
		}
		if (strlen($this->title2) > 500) {
			$this->title2 = StringUtils::trimStringToLengthAtWordBoundary($this->title2, 500, true);
		}
		if (strlen($this->author) > 500) {
			$this->author = StringUtils::trimStringToLengthAtWordBoundary($this->author, 500, true);
		}
	}

	public function insert($context = '') {
		$this->performPreSaveChecks();
		return parent::insert();
	}

	public function update($context = '') {
		$this->performPreSaveChecks();
		return parent::update();
	}
}