<?php /** @noinspection PhpMissingFieldTypeInspection */

class AdministrationLocation extends DataObject {
	public $__table = 'user_administration_locations';
	public $id;
	public $userId;
	public $locationId;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'label',
				'label' => 'User Id',
				'description' => 'The id of the user who can has privileges to administer to the location',
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'label',
				'label' => 'Location Id',
				'description' => 'The id of the location that can be administered',
			]
		];
	}
}