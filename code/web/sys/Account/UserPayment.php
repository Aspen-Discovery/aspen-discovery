<?php


class UserPayment extends DataObject
{
	public $__table = 'user_payments';

	public $id;
	public $userId;
	public $paymentType;
	public $orderId;
	public $completed;
	public $cancelled;
	public $error;
	public $message;
	public $finesPaid;
	public $totalPaid;
	public $transactionDate;
}