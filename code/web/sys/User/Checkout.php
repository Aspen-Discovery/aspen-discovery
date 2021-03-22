<?php
require_once ROOT_DIR . '/sys/User/CircEntry.php';

class Checkout extends CircEntry
{
	public $__table = 'user_checkout';
	public $shortId;
	public $itemId;
	public $itemIndex;
	public $renewalId;
	public $barcode;
	public $title2;
	public $callNumber;
	public $volume;
	public $checkoutDate;
	public $dueDate;
	public $renewCount;
	public $renewIndicator;
	public $renewalDate;
	public $canRenew;
	public $autoRenew;
	public $autoRenewError;
	public $maxRenewals;
	public $fine;
	public $returnClaim;
	public $holdQueueLength;

	//For OverDrive
	public $allowDownload;
	public $overdriveRead;
	public $overdriveReadUrl;
	public $overdriveListen;
	public $overdriveListenUrl;
	public $overdriveVideo;
	public $overdriveVideoUrl;
	public $overdriveMagazine;
	public $formatSelected;
	public $selectedFormatName;
	public $selectedFormatValue;
	public $canReturnEarly;
	public $supplementalMaterials; //This gets minified when saved and loaded
	public $formats; //This gets minified when saved and loaded

	//For RBdigital
	public $downloadUrl;

	//For Axis360
	public $accessOnlineUrl;
	public $transactionId;

	//For OverDrive magazine support
	public $coverUrl;
	public $format;

	//Calculate in realtime
	public $_overdue = null;
	public $_daysUntilDue = null;

	public function getNumericColumnNames()
	{
		return ['userId', 'checkoutDate', 'dueDate', 'renewCount', 'canRenew', 'autoRenew', 'maxRenewals', 'fine', 'holdQueueLength'];
	}

	public function getDaysUntilDue(){
		if ($this->_daysUntilDue == null) {
			if ($this->dueDate) {
				// use the same time of day to calculate days until due, in order to avoid errors with rounding
				$dueDate = strtotime('midnight', $this->dueDate);
				$today = strtotime('midnight');
				$daysUntilDue = ceil(($dueDate - $today) / (24 * 60 * 60));
				$overdue = $daysUntilDue < 0;
				$this->_overdue = $overdue;
				$this->_daysUntilDue = $daysUntilDue;
			} else {
				$this->_overdue = false;
				$this->_daysUntilDue = '';
			}
		}
		return $this->_daysUntilDue;
	}

	/** @noinspection PhpUnused */
	public function isOverdue(){
		if ($this->_overdue == null){
			$this->getDaysUntilDue();
		}
		return $this->_overdue;
	}

	public function getFormattedRenewalDate(){
		if (!emtpy($this->renewalDate)){
			$dateDue = new DateTime($this->renewalDate);
			$dateDue->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $dateDue->format('D M jS');
		}else{
			return '';
		}
	}
}