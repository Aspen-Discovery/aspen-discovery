<?php

class NotInterested extends DataObject{
	public $id;
	public $userId;
	public $groupedRecordPermanentId;
	public $dateMarked;

	public $__table = 'user_not_interested';
}