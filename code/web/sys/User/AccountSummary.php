<?php


class AccountSummary extends DataObject
{
	public $__table = 'user_account_summary';
	public $source;
	public $userId;
	public $numCheckedOut;
	public $numOverdue;
	public $numAvailableHolds;
	public $numUnavailableHolds;
	public $totalFines;
	public $expirationDate;
	public $lastLoaded;
}