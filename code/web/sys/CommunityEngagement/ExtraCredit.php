<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
class ExtraCredit extends DataObject {
	public $__table = 'ce_extra_credit';
	public $id;
	public $name;
	public $description;
	/** @noinspection PhpUnused */
	public $allowPatronProgressInput;


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
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for the extra credit activity',
				'required' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'translatableTextBlock',
				'label' => 'Description',
				'maxLength' => 255,
				'description' => 'A description of the extra credit actuvuty',
				'defaultTextFile' => 'Extra_credit_description.MD',
				'hideInLists'=> true,
			],
			'allowPatronProgressInput' => [
				'property' => 'allowPatronProgressInput',
				'type' => 'checkbox',
				'label' => 'Allow Patrons to Update Progress',
				'description' => 'Allow patrons to update their own progress for this extra credit activity.',
				'default' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @return array
	*/
	public static function getExtraCreditList(): array {
		$extraCredit = new ExtraCredit();
		$extraCreditList = [];
		 
		if ($extraCredit->find()) {
			while ($extraCredit->fetch()) {
				$extraCreditList[$extraCredit->id] = $extraCredit->name;
			}
		}
		return $extraCreditList;
	}
}