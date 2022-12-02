<?php

class CloudLibraryProduct extends DataObject {
	public $__table = 'cloud_library_title';

	public $id;
	public $cloudLibraryId;
	public $title;
	public $subTitle;
	public $author;
	public $format;
	public $rawChecksum;
	public $rawResponse;
	public $lastChange;
	public $dateFirstDetected;
	public $deleted;
}