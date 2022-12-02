<?php


class RBdigitalMagazineUsage extends DataObject {
	public $__table = 'rbdigital_magazine_usage';
	public $id;
	public $instance;
	public $magazineId;
	public $year;
	public $month;
	public $timesCheckedOut;
}