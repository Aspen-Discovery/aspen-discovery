<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

class EventInstance extends DataObject {
	public $__table = 'event_instance';
	public $id;
	public $eventId;
	public $date;
	public $time;
	public $length;
	public $status;
	public $note;

	public $dateUpdated;
	public $deleted;

	public static function getObjectStructure($context = ''): array {
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'eventId' => [
				'property' => 'eventId',
				'type' => 'text',
				'label' => 'Event Name',
				'description' => 'A name for the field',
				'hiddenByDefault' => true,
				'hideInLists' => true,
			],
			'date' => [
				'property' => 'date',
				'type' => 'date',
				'label' => 'Event Date',
				'description' => 'The event date',
			],
			'time' => [
				'property' => 'time',
				'type' => 'time',
				'label' => 'Event Time',
				'description' => 'The event Time',
			],
			'length' => [
				'property' => 'length',
				'type' => 'integer',
				'label' => 'Length (Hours)',
				'description' => 'The event length in hours',
			],
			'note' => [
				'property' => 'note',
				'type' => 'text',
				'label' => 'Note',
				'description' => 'A note for this specific instance',
			],
			'status' => [
				'property' => 'status',
				'type' => 'checkbox',
				'label' => 'Active',
				'default' => 1,
				'description' => 'Whether the event is active or cancelled',
			],
			'dateUpdated' => [
				'property' => 'dateUpdated',
				'label' => 'Date last updated',
				'type' => 'hidden',
				'hideInLists' => true,
			]
		];
		return $structure;
	}

	public function getNumericColumnNames(): array {
		return [
			'length',
			'dateUpdated',
		];
	}

	public function update($context = '') {
		$this->dateUpdated = time();
		if (count($this->_changedFields) > 0) {
			$this->_changedFields[] = 'dateUpdated';
		}
		return parent::update();
	}

	public function insert($context = '') {
		$this->dateUpdated = time();
		return parent::insert();
	}

	function delete($useWhere = false) : int {
		if (!$useWhere) {
			$this->deleted = 1;
			$this->dateUpdated = time();
			return parent::update();
		} else {
			return parent::delete($useWhere);
		}
	}

}