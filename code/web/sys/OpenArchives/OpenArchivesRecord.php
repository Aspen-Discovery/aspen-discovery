<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class OpenArchivesRecord extends DataObject {
	public $__table = 'open_archives_record';
	public $id;
	public $sourceCollection;
	public $permanentUrl;
}