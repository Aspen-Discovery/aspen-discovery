<?php /** @noinspection PhpMissingFieldTypeInspection */

class SelfCheckCompletionMessage extends DataObject {
	public $__table = 'self_check_completion_message';
	public $id;
	public $formats;
	public $owningLocations;
	public $checkoutLocations;
	public $_completionMessage;

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
			'formats' => [
				'property' => 'formats',
				'type' => 'regularExpression',
				'label' => 'Formats to show message for (Regex)',
				'description' => 'A regular expression for the formats to show this message for, leave blank or use .* to include everything',
				'maxLength' => '500',
				'required' => false,
				'default' => '.*',
			],
			'owningLocations' => [
				'property' => 'owningLocations',
				'type' => 'regularExpression',
				'label' => 'Owning Locations to show message for (Regex)',
				'description' => 'A regular expression for the owning locations to show this message for, leave blank or use .* to include everything',
				'maxLength' => '500',
				'required' => false,
				'default' => '.*',
			],
			'checkoutLocations' => [
				'property' => 'checkoutLocations',
				'type' => 'regularExpression',
				'label' => 'Checkout Locations to show message for (Regex)',
				'description' => 'A regular expression for the checkout locations to show this message for, leave blank or use .* to include everything',
				'maxLength' => '500',
				'required' => false,
				'default' => '.*',
			],
			'completionMessage' => [
				'property' => 'completionMessage',
				'type' => 'translatablePlainTextBlock',
				'label' => 'Completion Message',
				'description' => 'The message to show after the checkout is completed',
				'readOnly' => false,
				'required' => true,
				'maxLength' => 500,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveTextBlockTranslations('completionMessage');
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveTextBlockTranslations('completionMessage');
		}
		return $ret;
	}
}