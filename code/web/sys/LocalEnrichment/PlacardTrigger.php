<?php


class PlacardTrigger extends DataObject {
	public $__table = 'placard_trigger';
	public $id;
	public $placardId;
	public $triggerWord;
	public $exactMatch;

	static function getObjectStructure(): array {
		return [
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
	}

	public function getUniquenessFields(): array {
		return [
			'placardId',
			'triggerWord',
		];
	}
}