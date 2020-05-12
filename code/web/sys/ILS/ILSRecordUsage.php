<?php


class ILSRecordUsage extends DataObject
{
	public $__table = 'ils_record_usage';
	public $id;
	public $indexingProfileId;
	public $recordId;
	public $year;
	public $month;
	public $timesUsed; //This is number of holds
	public $pdfDownloadCount;
	public $supplementalFileDownloadCount;
}