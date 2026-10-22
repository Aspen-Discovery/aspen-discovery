<?php /** @noinspection PhpMissingFieldTypeInspection */


class DonationReceipt extends DataObject {
	public $__table = 'donation_receipt';   // table name

	public $id;
	public $description;
	public $isDefault;
	/** @noinspection PhpUnused */
	public $sendEmailToPatron;
	public $emailTemplate;
	public $isOpen;
	public $isPatronCancel;
	public $libraryId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the libary within the database',
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'size' => 80,
				'label' => 'Description',
				'description' => 'A unique name for the Status',
			],
			'isDefault' => [
				'property' => 'isDefault',
				'type' => 'checkbox',
				'label' => 'Default Status?',
				'description' => 'Whether or not this status is the default status to apply to new requests',
			],
			'isPatronCancel' => [
				'property' => 'isPatronCancel',
				'type' => 'checkbox',
				'label' => 'Set When Patron Cancels?',
				'description' => 'Whether or not this status should be set when the patron cancels their request',
			],
			'isOpen' => [
				'property' => 'isOpen',
				'type' => 'checkbox',
				'label' => 'Open Status?',
				'description' => 'Whether or not this status needs further processing',
			],
			'sendEmailToPatron' => [
				'property' => 'sendEmailToPatron',
				'type' => 'checkbox',
				'label' => 'Send Email To Patron?',
				'description' => 'Whether or not an email should be sent to the patron when this status is set',
			],
			'emailTemplate' => [
				'property' => 'emailTemplate',
				'type' => 'textarea',
				'rows' => 6,
				'cols' => 60,
				'label' => 'Email Template',
				'description' => 'The template to use when sending emails to the user',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

}