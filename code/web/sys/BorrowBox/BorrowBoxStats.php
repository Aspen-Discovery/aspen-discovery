<?php /** @noinspection PhpMissingFieldTypeInspection */

class BorrowBoxStats extends DataObject {
	public $__table = 'borrowbox_stats';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $day;

	public $numCheckouts;
	public $numFailedCheckouts;
	public $numRenewals;
	public $numEarlyReturns;
	public $numHoldsPlaced;
	public $numFailedHolds;
	public $numHoldsCancelled;
	public $numApiErrors;
	public $numConnectionFailures;
}
