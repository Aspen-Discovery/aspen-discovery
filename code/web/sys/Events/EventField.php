<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/EventFieldSetField.php';

class EventField extends DataObject {
	public $__table = 'event_field';
	public $id;
	public $fieldUse;
	public $name;
	public $description;
	public $type;
	public $allowableValues; // For select lists
	public $defaultValue;
	public $facetName;

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
				'description' => 'The unique id',
			],
			'fieldUse' => [
				'property' => 'fieldUse',
				'type' => 'enum',
				'label' => 'Field Use',
				'description' => 'Defines what the field is to be used for (describing an event, taking in registration information, etc)',
				'values' => [
					'0' => 'Please select...',
					'1' => 'Event description section (for staff use, viewable by the public)',
					'2' => 'Event registration form (for public use)',
				],
				'default' => '0',
				'required' => true,
			], 
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the field',
				'maxLength' => 50,
				'required' => true,
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$fieldSet = new EventFieldSetField();
		$fieldSet->eventFieldId = $this->id;
		if ($fieldSet->count() == 0) {
			return parent::delete($useWhere, $hardDelete);
		}
		return 0;
	}

	public static function getEventInformationFieldList(bool $forCalendarOptions = false): array {
		$fieldList = EventField::getEventFieldList(1);
		if ($forCalendarOptions) {
			/*			$fieldList[-3] = "Title - The title of the event";
						$fieldList[-2] = "Time - The time of the event";
						$fieldList[-1] = "Cover - The image for the event";*/
			$fieldList[-3] = "Branch - The Branch where the event is held";
			$fieldList[-4] = "Room - The Room where the event is held";
			$fieldList[-2] = "Description - The description for the event";
		}
		return $fieldList;
	}

	public static function getEventRegistrationFieldList(): array {
		return EventField::getEventFieldList(2);
	}

	public static function getEventFieldList($fieldUse = null): array {
		$fieldList = [];
		$object = new EventField();
		if (!is_null($fieldUse)) {
			$object->fieldUse = $fieldUse;
		}
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

	public function	getFieldObjectStructure(): array {
		if (!$this->find(true)) {
			return [];
		}
		$type = match ($this->type) {
			0 => 'text',
			1 => 'textarea',
			2 => 'checkbox',
			3 => 'enum',
			4 => 'email',
			5 => 'url',
			default => '',
		};
		$structure = [
			'fieldId' => $this->id,
			'property' => $this->id,
			'type' => $type,
			'label' => $this->name,
			'description' => $this->description,
			'default' => $this->defaultValue,
			'facetName' => $this->facetName,
		];
		if ($type == 'enum') {
			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$allowableValues = array_map('trim', explode("\n", $this->allowableValues));
			$keys = array_map([StringUtils::class, 'toCamelCase'], $allowableValues);
			$structure['values'] = array_combine($keys, $allowableValues);
		} else if ($type == 'checkbox') {
			$structure['returnValueForUnchecked'] = true;
		}
		return $structure;
	}
}

