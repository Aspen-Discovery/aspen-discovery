<?php


class Axis360Stats extends DataObject {
	public $__table = 'axis360_stats';
	public $id;
	public $instance;
	public $year;
	public $month;

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