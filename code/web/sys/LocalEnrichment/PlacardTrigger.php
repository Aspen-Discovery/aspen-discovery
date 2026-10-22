<?php /** @noinspection PhpMissingFieldTypeInspection */

class PlacardTrigger extends DataObject {
	public $__table = 'placard_trigger';
	public $id;
	public $placardId;
	/** @noinspection PhpUnused */
	public $triggerWord;
	/** @noinspection PhpUnused */
	public $exactMatch;

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
				'description' => 'The unique id of the sub-category row within the database',
			],
			'triggerWord' => [
				'property' => 'triggerWord',
				'type' => 'text',
				'label' => 'Trigger word',
				'description' => 'The trigger used to cause the placard to display',
				'maxLength' => 100,
				'required' => true,
				'noteBullets' => 'When multiple placards match, priority is as follows: exact match > whole word match > partial match. Longer triggers are favored for specificity.',
			],
			'placardId' => [
				'property' => 'placardId',
				'type' => 'label',
				'label' => 'Placard',
				'description' => 'The placard to display',
			],
			'exactMatch' => [
				'property' => 'exactMatch',
				'type' => 'checkbox',
				'label' => 'Exact Match',
				'description',
				'Select if the search term mus be matched exactly (case insensitive)',
				'default' => 0,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getUniquenessFields(): array {
		return [
			'placardId',
			'triggerWord',
		];
	}
}