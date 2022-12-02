<?php


class RBdigitalMagazine extends DataObject {
	public $__table = 'rbdigital_magazine';

	public $id;
	public $magazineId;
	public $issueId;
	public $title;
	public $publisher;
	public $mediaType;
	public $language;
	public $rawChecksum;
	public $rawResponse;
	public $lastChange;
	public $dateFirstDetected;
	public $deleted;
}