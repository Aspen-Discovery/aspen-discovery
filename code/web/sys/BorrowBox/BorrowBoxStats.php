<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/AbstractUsage.php';

class BorrowBoxStats extends AbstractUsage {
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
