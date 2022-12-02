<?php

class SyndeticsData extends DataObject {
	public $id;
	public $groupedRecordPermanentId;
	public $primaryIsbn;
	public $primaryUpc;
	public $description;
	public $lastDescriptionUpdate;
	public $tableOfContents;
	public $lastTableOfContentsUpdate;
	public $excerpt;
	public $lastExcerptUpdate;

	public $__table = 'syndetics_data';
} 