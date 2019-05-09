<?php

class SyndeticsData extends DataObject{
	public $id;
	public $groupedRecordPermanentId;
	public $lastUpdate;
	public $hasSyndeticsData;
	public $primaryIsbn;
	public $primaryUpc;
	public $description;
	public $tableOfContents;
	public $excerpt;

	public $__table = 'syndetics_data';
} 