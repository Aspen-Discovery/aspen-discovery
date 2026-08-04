<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Events/AspenEventAttendeeCategory.php';

class EventTypeAttendeeCategory extends DataObject {
	public $__table = 'event_type_attendee_category';
	public $id;
	public $eventTypeId;
	public $attendeeCategoryId;
	public $maxAttendees;

	private $_category;

	public function getCategory(): ?AspenEventAttendeeCategory {
		if (!isset($this->_category) && $this->attendeeCategoryId) {
			$this->_category = new AspenEventAttendeeCategory();
			$this->_category->id = $this->attendeeCategoryId;
			if (!$this->_category->find(true)) {
				$this->_category = null;
			}
		}
		return $this->_category;
	}

	function getEditLink(): string {
		return '/Events/EventTypes?objectAction=edit&id=' . $this->eventTypeId;
	}

	public function getUniquenessFields(): array {
		return ['eventTypeId', 'attendeeCategoryId'];
	}

	static function getObjectStructure(string $context = ''): array {
		$categories = [];
		$categoryObj = new AspenEventAttendeeCategory();
		$categoryObj->orderBy('name');
		$categoryObj->find();
		while ($categoryObj->fetch()) {
			$categories[$categoryObj->id] = $categoryObj->name;
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'attendeeCategoryId' => [
				'property' => 'attendeeCategoryId',
				'type' => 'enum',
				'label' => 'Attendee Category',
				'description' => 'The attendee category',
				'values' => $categories,
				'required' => true,
			],
			'maxAttendees' => [
				'property' => 'maxAttendees',
				'type' => 'integer',
				'label' => 'Max Attendees',
				'description' => 'Maximum number of attendees allowed for this category',
				'default' => 1,
				'required' => true,
			],
		];
	}
}
