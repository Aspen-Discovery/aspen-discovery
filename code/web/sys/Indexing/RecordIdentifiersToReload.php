<?php

class RecordIdentifiersToReload extends DataObject {
	public $__table = 'record_identifiers_to_reload';
	public $id;
	public $type;
	public $identifier;
	public $processed;
}