<?php


class OverDriveRecordUsage extends DataObject
{
    public $__table = 'overdrive_record_usage';
    public $id;
    public $overdriveId;
    public $year;
    public $month;
    public $timesViewedInSearch;
    public $timesHeld;
    public $timesCheckedOut;
}