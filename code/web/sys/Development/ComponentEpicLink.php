<?php

class ComponentEpicLink extends DataObject {
	public $__table = 'component_development_epic_link';
	public $id;
	public $epicId;
	public $componentId;

	static function getObjectStructure(): array {
		$componentList = [];
		require_once ROOT_DIR . '/sys/Support/TicketComponentFeed.php';
		$component = new TicketComponentFeed();
		$component->orderBy('name');
		$component->find();
		while ($component->fetch()) {
			$componentList[$component->id] = $component->name;
		}

		$epicList = [];
		require_once ROOT_DIR . '/sys/Development/DevelopmentEpic.php';
		$epic = new DevelopmentEpic();
		$epic->whereAdd('privateStatus NOT IN (9, 10)');
		$epic->orderBy('name ASC');
		$epic->find();
		while ($epic->fetch()) {
			$epicList[$epic->id] = $epic->name;
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'epicId' => [
				'property' => 'epicId',
				'type' => 'enum',
				'values' => $epicList,
				'label' => 'Epic',
				'description' => 'The epic related to the component',
				'required' => true,
			],
			'componentId' => [
				'property' => 'componentId',
				'type' => 'enum',
				'values' => $componentList,
				'label' => 'Task',
				'description' => 'The component related to the ticket',
				'required' => true,
			],
		];
	}

	function getEditLink($context): string {
		if ($context == 'relatedEpics') {
			return '/Development/Epics?objectAction=edit&id=' . $this->epicId;
		} else {
			return '/Support/TicketComponentFeed?objectAction=edit&id=' . $this->componentId;
		}
	}
}