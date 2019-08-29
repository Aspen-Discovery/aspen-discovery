<?php


class SideLoadedRecordUsage extends DataObject
{
    public $__table = 'sideload_record_usage';
    public $id;
    public $sideloadId;
	public $recordId;
    public $year;
    public $month;
    public $timesUsed;
}