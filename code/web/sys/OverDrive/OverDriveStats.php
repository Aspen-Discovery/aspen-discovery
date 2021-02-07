<?php

class OverDriveStats extends DataObject
{
	public $__table = 'overdrive_stats';
	public $id;
	public $instance;
	public $year;
	public $month;

	public $numCheckouts;
	public $numFailedCheckouts;
	public $numRenewals;
	public $numEarlyReturns;
	public $numHoldsPlaced;
	public $numFailedHolds;
	public $numHoldsCancelled;
	public $numHoldsFrozen;
	public $numHoldsThawed;
	public $numDownloads;
	public $numPreviews;
	public $numOptionsUpdates;
	public $numApiErrors;
	public $numConnectionFailures;
}