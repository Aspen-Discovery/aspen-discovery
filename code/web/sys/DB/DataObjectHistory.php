<?php /** @noinspection PhpMissingFieldTypeInspection */


class DataObjectHistory extends DataObject {
	public $__table = 'object_history';
	public $id;
	public $objectType;
	public $objectId;
	public $actionType; //1 = create, 2 == update, 3 = delete
	public $propertyName;
	public $oldValue;
	public $newValue;
	public $changedBy;
	public $changeDate;

	private static $_userNames = [];

	public function getNumericColumnNames(): array {
		return ['id', 'objectId', 'actionType', 'changedBy', 'changeDate'];
	}

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
			'objectType' => [
				'property' => 'objectType',
				'type' => 'label',
				'label' => 'Object Type',
				'description' => 'The Type of object',
			],
			'objectId' => [
				'property' => 'objectId',
				'type' => 'label',
				'label' => 'Object ID',
				'description' => 'The ID of the object being changed, updated, or deleted',
			],
			'actionType' => [
				'property' => 'actionType',
				'type' => 'enum',
				'values' => [1 => 'Create', 2 => 'Update', 3 => 'Delete'],
				'label' => 'Action Taken',
				'description' => 'The action taken',
				'readOnly' => true,
			],
			'propertyName' => [
				'property' => 'propertyName',
				'type' => 'label',
				'label' => 'Property Name',
				'description' => 'The Name of the property for change actions',
				'hideInLists' => false
			],
			'oldValue' => [
				'property' => 'oldValue',
				'type' => 'label',
				'label' => 'Old Value',
				'description' => 'The Old Value of the property',
				'hideInLists' => true
			],
			'newValue' => [
				'property' => 'newValue',
				'type' => 'label',
				'label' => 'New Value',
				'description' => 'The New Value for the property',
				'hideInLists' => true
			],
			'changedByName' => [
				'property' => 'changedByName',
				'type' => 'label',
				'label' => 'Change By',
				'description' => 'Who made the change',
				'hideInLists' => false
			],
			'changeDate' => [
				'property' => 'changeDate',
				'type' => 'timestamp',
				'label' => 'Change Date',
				'description' => 'When the change was made',
				'readOnly' => true
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "changedByName") {
			return $this->getChangedByName();
		} else {
			return parent::__get($name);
		}
	}

	public function getChangedByName() {
		if (!array_key_exists($this->changedBy, DataObjectHistory::$_userNames)) {
			$user = new User();
			$user->id = $this->changedBy;
			if ($user->find(true)) {
				if (!empty($user->displayName)) {
					DataObjectHistory::$_userNames[$this->changedBy] = $user->displayName;
				} else {
					DataObjectHistory::$_userNames[$this->changedBy] = $user->firstname . ' ' . $user->lastname;
				}

			} else {
				DataObjectHistory::$_userNames[$this->changedBy] = 'Unknown';
			}
		}
		return DataObjectHistory::$_userNames[$this->changedBy];
	}

	public function insert(string $context = ''): int|bool {
		//Make sure that we aren't recording history of things with minor changes
		//i.e. changing 0 to false or "0" or changing null to ""
		if (is_null($this->oldValue)) {
			$okToInsert = !empty($this->newValue);
		}elseif (is_null($this->newValue)){
			$okToInsert = !empty($this->oldValue);
		}else{
			$okToInsert = ($this->oldValue != $this->newValue);
		}
		if ($okToInsert) {
			return parent::insert($context);
		} else {
			return false;
		}
	}
}