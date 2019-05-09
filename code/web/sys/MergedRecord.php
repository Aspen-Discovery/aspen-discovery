<?php

class MergedRecord extends DataObject{
	public $__table = 'merged_records';   // table name
    public $id;
	public $original_record;
	public $new_record;

}