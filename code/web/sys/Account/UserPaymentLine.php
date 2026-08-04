<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserPaymentLine extends DataObject {
	public $__table = 'user_payment_lines';
	public $id;
	public $paymentId;
	public $description;
	public $amountPaid;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'paymentId' => [
				'property' => 'paymentId',
				'type' => 'text',
				'label' => 'Payment Id',
				'description' => 'The payment this line belongs to',
				'readOnly' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'label' => 'Description',
				'description' => 'The description of the paid item',
				'readOnly' => true,
			],
			'amountPaid' => [
				'property' => 'amountPaid',
				'type' => 'currency',
				'label' => 'Amount Paid',
				'description' => 'The amount paid for this line item',
				'displayFormat' => '%0.2f',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}


}