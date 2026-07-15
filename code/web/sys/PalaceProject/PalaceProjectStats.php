<?php /** @noinspection PhpMissingFieldTypeInspection */


class PalaceProjectStats extends DataObject {
	public $__table = 'palace_project_stats';
	public $id;
	public $instance;
	public $year;
	public $month;
	public $day;

	public $numCheckouts;
	public $numRenewals;
	/** @noinspection PhpUnused */
	public $numEarlyReturns;
	/** @noinspection PhpUnused */
	public $numHoldsPlaced;
	/** @noinspection PhpUnused */
	public $numHoldsCancelled;
	/** @noinspection PhpUnused */
	public $numHoldsFrozen;
	/** @noinspection PhpUnused */
	public $numHoldsThawed;
	/** @noinspection PhpUnused */
	public $numApiErrors;
	/** @noinspection PhpUnused */
	public $numConnectionFailures;
}