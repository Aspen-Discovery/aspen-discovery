<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Events/EventFieldSetField.php';

class EventField extends DataObject {
	public $__table = 'event_field';
	public $id;
	public $name;
	public $description;
	public $type;
	public $allowableValues; // For select lists
	public $defaultValue;
	public $facetName;

	public static function getObjectStructure($context = ''): array {
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
				'description' => 'A name for the field',
				'maxLength' => 50,
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'label' => 'Description/Instructions for usage',
				'description' => 'A description or instructions for the field',
				'maxLength' => 100,
			],
			'type' => [
				'property' => 'type',
				'type' => 'enum',
				'label' => 'Field Type',
				'description' => 'The type of field',
				'values' => [
					'0' => 'Text Field',
					'1' => 'Text Area',
					'2' => 'Checkbox',
					'3' => 'Select List',
					'4' => 'Email Address',
					'5' => 'URL',
				],
				'default' => '0',
			],
			'allowableValues' => [
				'property' => 'allowableValues',
				'type' => 'textarea',
				'label' => 'Allowable Values for Select Lists',
				'description' => 'A list of allowable values (only for select lists)',
				'note' => 'Each value should be on a new line'
			],
			'defaultValue' => [
				'property' => 'defaultValue',
				'type' => 'text',
				'label' => 'Default Value',
				'description' => 'The default value for the field',
				'maxLength' => 150,
			],
			'facetName' => [
				'property' => 'facetName',
				'type' => 'enum',
				'label' => 'Facet Name',
				'values' => [
					'0' => 'None',
					'1' => 'Age Group',
					'2' => 'Program Type',
					'3' => 'Category',
					'4' => 'Event Type',
					'5' => 'Custom Facet 1',
					'6' => 'Custom Facet 2',
					'7' => 'Custom Facet 3',
				],
				'default' => '0',
			],
		];
		return $structure;
	}

	function delete($useWhere = false) : int {
		$fieldSet = new EventFieldSetField();
		$fieldSet->eventFieldId = $this->id;
		if ($fieldSet->count() == 0) {
			return parent::delete($useWhere);
		}
		return 0;
	}

	public static function getEventFieldList(): array {
		$fieldList = [];
		$object = new EventField();
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()) {
			$label = $object->name . " - " . $object->description;
			$fieldList[$object->id] = $label;
		}
		return $fieldList;
	}

	public static function getEventFieldsByTypes(array $types): array {
		$fieldList = [];
		$object = new EventField();
		$object->whereAdd();
		$object->whereAddIn('type', $types, false, "OR");
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()) {
			$fieldList[$object->id] = clone($object);
		}
		return $fieldList;
	}
}

