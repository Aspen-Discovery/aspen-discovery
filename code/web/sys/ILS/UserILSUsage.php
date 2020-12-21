<?php


class UserILSUsage extends DataObject
{
	public $__table = 'user_ils_usage';
	public $id;
	public $instance;
	public $userId;
	public $indexingProfileId;
	public $year;
	public $month;
	public $usageCount; //Number of holds/clicks to online for sideloads
	public $selfRegistrationCount;
	public $pdfDownloadCount;
	public $supplementalFileDownloadCount;
	public $pdfViewCount;
}