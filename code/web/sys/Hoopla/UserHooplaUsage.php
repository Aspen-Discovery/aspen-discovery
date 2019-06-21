<?php


class UserHooplaUsage extends DataObject
{
    public $__table = 'user_hoopla_usage';
    public $id;
    public $userId;
    public $recordId;
    public $year;
    public $month;
    public $usageCount; //Number of holds/clicks to online for sideloads
}