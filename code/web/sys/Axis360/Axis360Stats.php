<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/AbstractUsage.php';

class Axis360Stats extends AbstractUsage {
	public $__table = 'axis360_stats';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $day;

	public $numCheckouts;
	public $numRenewals;
	public $numEarlyReturns;
	public $numHoldsPlaced;
	public $numHoldsCancelled;
	public $numHoldsFrozen;
	public $numHoldsThawed;
	public $numApiErrors;
	public $numConnectionFailures;
}