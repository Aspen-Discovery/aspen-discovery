<?php


class HoldRequestConfirmation extends DataObject
{
	public $__table = 'hold_request_confirmation';
	public $id;
	public $userId;
	public $requestId;
	public $additionalParams;
}