<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserEbscoEdsUsage extends DataObject
{
    public $__table = 'user_ebsco_eds_usage';
    public $id;
    public $instance;
    public $userId;
    public $year;
    public $month;
    public $usageCount;
}