<?php


class DataObjectHistory extends DataObject {
	public $__table = 'object_history';
	public $id;
	public $objectType;
	public $objectId;
	public $propertyName;
	public $oldValue;
	public $newValue;
	public $changedBy;
	public $changeDate;

	private static $_userNames = [];

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