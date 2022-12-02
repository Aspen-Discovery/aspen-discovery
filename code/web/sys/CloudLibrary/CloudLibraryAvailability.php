<?php

class CloudLibraryAvailability extends DataObject {
	public $__table = 'cloud_library_availability';

	public $id;
	public $settingId;
	public $cloudLibraryId;
	public $totalCopies;
	public $sharedCopies;
	public $totalLoanCopies;
	public $totalHoldCopies;
	public $sharedLoanCopies;
}