<?php

class ComponentTaskLink extends DataObject {
	public $__table = 'component_development_task_link';
	public $id;
	public $taskId;
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

		$taskList = [];
		require_once ROOT_DIR . '/sys/Development/DevelopmentTask.php';
		$task = new DevelopmentTask();
		$task->find();
		while ($task->fetch()) {
			$taskList[$task->id] = "$task->name ($task->storyPoints)";
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'taskId' => [
				'property' => 'taskId',
				'type' => 'enum',
				'values' => $taskList,
				'label' => 'Task',
				'description' => 'The task related to the component',
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
		if ($context == 'relatedTasks') {
			return '/Development/Tasks?objectAction=edit&id=' . $this->ticketId;
		} else {
			return '/Support/TicketComponentFeed?objectAction=edit&id=' . $this->componentId;
		}
	}
}