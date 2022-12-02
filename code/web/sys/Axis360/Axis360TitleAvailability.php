<?php

class Axis360TitleAvailability extends DataObject {
	public $__table = 'axis360_title_availability';   // table name

	public $id;
	public $settingId;
	public $titleId;
	public $libraryPrefix;
	public $available;
	public $ownedQty;
	public $totalHolds;
} 