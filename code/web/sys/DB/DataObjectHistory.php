<?php


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

	public function getObjectStructure($context = ''): array {
		return [
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
	}

	public function __get($name) {
		if ($name == "changedByName") {
			return $this->getChangedByName();
		} else {
			return $this->_data[$name] ?? null;
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


}