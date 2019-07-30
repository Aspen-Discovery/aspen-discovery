<?php


class UserRBdigitalUsage extends DataObject
{
    public $__table = 'user_rbdigital_usage';
    public $id;
    public $userId;
    public $recordId;
    public $year;
    public $month;
    public $usageCount; //Number of holds/clicks
}