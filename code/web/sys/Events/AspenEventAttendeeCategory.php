<?php /** @noinspection PhpMissingFieldTypeInspection */

class AspenEventAttendeeCategory extends DataObject {
	public $__table = 'aspen_event_attendee_category';
	public $id;
	public $name;
	public $staffDescription;
	public $publicDescription;

	static function getObjectStructure(string $context = ''): array {
		return [
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
				'description' => 'The name of the attendee category',
				'maxLength' => 50,
				'required' => true,
			],
			'staffDescription' => [
				'property' => 'staffDescription',
				'type' => 'text',
				'label' => 'Description to Staff',
				'description' => 'The description of the attendee category as displayed to staff',
				'maxLength' => 50,
				'required' => true,
			],
			'publicDescription' => [
				'property' => 'publicDescription',
				'type' => 'text',
				'label' => 'Description to the Public',
				'description' => 'The description of the attendee category as displayed to public',
				'maxLength' => 50,
				'required' => true,
			],
		];
	}
}
