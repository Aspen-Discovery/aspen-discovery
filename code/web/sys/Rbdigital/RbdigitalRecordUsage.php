<?php


class RbdigitalRecordUsage extends DataObject
{
    public $__table = 'rbdigital_record_usage';
    public $id;
    public $rbdigitalId;
    public $year;
    public $month;
    public $timesHeld;
    public $timesCheckedOut;
}