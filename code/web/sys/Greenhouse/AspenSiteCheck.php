<?php


class AspenSiteCheck extends DataObject
{
	public $__table = 'aspen_site_checks';
	public $id;
	public $siteId;
	public $checkName;
	public $currentStatus;
	public $currentNote;
	public $lastOkTime;
	public $lastWarningTime;
	public $lastErrorTime;

	public function getNumericColumnNames(): array
	{
		return ['currentStatus', 'lastOkTime', 'lastWarningTime', 'lastErrorTime'];
	}
}