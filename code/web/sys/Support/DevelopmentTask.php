<?php


class DevelopmentTask extends DataObject
{
	public $__table = 'development_task';
	public $id;
	public $name;
	public $description;
	public $releaseId;
	public $weight;
	public $status;
	public $assignedTo;
}