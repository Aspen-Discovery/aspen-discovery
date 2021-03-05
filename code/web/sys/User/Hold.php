<?php
require_once ROOT_DIR . '/sys/User/CircEntry.php';

class Hold extends CircEntry
{
	public $__table = 'user_hold';
	public $shortId;
	public $itemId;
	public $title;
	public $title2;
	public $author;
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

}