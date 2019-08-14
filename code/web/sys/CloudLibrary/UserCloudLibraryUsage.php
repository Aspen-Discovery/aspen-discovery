<?php


class UserCloudLibraryUsage extends DataObject
{
    public $__table = 'user_cloud_library_usage';
    public $id;
    public $userId;
    public $recordId;
    public $year;
    public $month;
    public $usageCount; //Number of holds/clicks
}