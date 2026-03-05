<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/EventField.php';
require_once ROOT_DIR . '/sys/Events/EventFieldSetField.php';
require_once ROOT_DIR . '/sys/Events/EventType.php';

class EventFieldSet extends DataObject {
	public $__table = 'event_field_set';
	public $id;
	public $name;
	private $_eventFields;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$eventFields = EventField::getEventFieldList();
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
				'required' => true,
			],
			'eventFields' => [
				'property' => 'eventFields',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Event Fields',
				'description' => 'The event fields that make up the set',
				'values' => $eventFields,
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveFields();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveFields();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$type = new EventType();
		$type->eventFieldSetId = $this->id;
		if ($type->count() == 0) {
			return parent::delete($useWhere, $hardDelete);
		}
		return 0;
	}

	public function __set($name, $value) {
		if ($name == 'eventFields') {
			$this->setEventFields($value);
		} else {
			parent::__set($name, $value);
		}
	}

	public function __get($name) {
		if ($name == 'eventFields') {
			return $this->getEventFields();
		} else {
			return parent::__get($name);
		}
	}

	public function setEventFields($value) : void {
		$this->_eventFields = $value;
	}

	public function getEventFields() : ?array {
		if (!isset($this->_eventFields) && $this->id) {
			$this->_eventFields = [];
			$field = new EventFieldSetField();
			$field->eventFieldSetId = $this->id;
			$field->find();
			while ($field->fetch()) {
				$this->_eventFields[$field->eventFieldId] = clone($field);
			}
		}
		return $this->_eventFields;
	}

	public function saveFields() : void {
		if (isset($this->_eventFields) && is_array($this->_eventFields)) {
			$this->clearFields();

			foreach ($this->_eventFields as $eventField) {
				$fieldSetFields = new EventFieldSetField();
				$fieldSetFields->eventFieldId = $eventField;
				$fieldSetFields->eventFieldSetId = $this->id;
				$fieldSetFields->update();
			}
			unset($this->_eventFields);
		}
	}

	private function clearFields() : void {
		//Delete existing field/field set associations
		$fieldSetFields = new EventFieldSetField();
		$fieldSetFields->eventFieldSetId = $this->id;
		$fieldSetFields->find();
		while ($fieldSetFields->fetch()){
			$fieldSetFields->delete(true);
		}
	}

	public static function getEventFieldSetList(): array {
		$setList = [];
		$object = new EventFieldSet();
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()) {
			$label = $object->name;
			$setList[$object->id] = $label;
		}
		return $setList;
	}

	public function getFieldObjectStructure() : array {
		$structure = [];
		foreach ($this->getEventFields() as $fieldId) {
			$field = new EventField();
			$field->id = $fieldId->eventFieldId;
			if ($field->find(true)) {
				$type = match ($field->type) {
					0 => 'text',
					1 => 'textarea',
					2 => 'checkbox',
					3 => 'enum',
					4 => 'email',
					5 => 'url',
					default => '',
				};
				$structure[$field->id] = [
					'fieldId' => $field->id,
					'property' => $field->id,
					'type' => $type,
					'label' => $field->name,
					'description' => $field->description,
					'default' => $field->defaultValue,
					'facetName' => $field->facetName,
				];
				if ($type == 'enum') {
					$structure[$field->id]['values'] = explode("\n", $field->allowableValues);
				}
			}
		}
		return $structure;
	}
}
