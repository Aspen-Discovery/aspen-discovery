<?php

class Axis360TitleAvailability extends DataObject{
	public $__table = 'axis360_title_availability';   // table name

	public $id;
	public $titleId;
	public $libraryPrefix;
	public $ownedQty;
	public $availableQty;
	public $copiesAvailable;
	public $totalHolds;
	public $totalCheckouts;
} 