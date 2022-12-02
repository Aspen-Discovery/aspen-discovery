<?php

class Axis360Title extends DataObject {
	public $__table = 'axis360_title';

	public $id;
	public $axis360Id;
	public $isbn;
	public $title;
	public $subtitle;
	public $primaryAuthor;
	public $formatType;
	public $rawChecksum;
	public $rawResponse;
	public $lastChange;
	public $dateFirstDetected;
	public $deleted;
}