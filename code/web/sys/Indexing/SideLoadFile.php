<?php


class SideLoadFile extends DataObject {
	public $__table = 'sideload_files';
	public $id;
	public $sideLoadId;
	public $filename;
	public $lastChanged;
	public $deletedTime;
	public $lastIndexed;
}