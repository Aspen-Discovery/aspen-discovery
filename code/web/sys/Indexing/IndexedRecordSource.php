<?php

namespace sys\Indexing;
use DataObject;

class IndexedRecordSource extends DataObject
{
	public $__table = 'indexed_record_source';
	public $id;
	public $source;
	public $subSource;
}