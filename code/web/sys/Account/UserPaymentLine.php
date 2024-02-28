<?php

class UserPaymentLine extends DataObject {
	public $__table = 'user_payment_lines';
	public $id;
	public $paymentId;
	public $description;
	public $amountPaid;
}