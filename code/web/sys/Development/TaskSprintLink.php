<?php

class TaskSprintLink extends DataObject {
	public $__table = 'development_task_sprint_link';
	public $id;
	public $sprintId;
	public $taskId;
	public $weight;

	static function getObjectStructure(): array {
		$taskList = [];
		require_once ROOT_DIR . '/sys/Development/DevelopmentTask.php';
		$task = new DevelopmentTask();
		$task->find();
		while ($task->fetch()) {
			$taskList[$task->id] = $task->name;
		}

		$sprintList = [];
		require_once ROOT_DIR . '/sys/Development/DevelopmentSprint.php';
		$sprint = new DevelopmentSprint();
		$sprint->active = 1;

		$sprint->orderBy('startDate DESC');
		$sprint->find();
		while ($sprint->fetch()) {
			$sprintList[$sprint->id] = $sprint->name;
		}

		return array(
			'id' => array(
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id'
			),
			'weight' => array(
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true
			),
			'sprintId' => array(
				'property' => 'sprintId',
				'type' => 'enum',
				'values' => $sprintList,
				'label' => 'Sprint',
				'description' => 'The sprint where the task will be worked on',
				'required' => true
			),
			'taskId' => array(
				'property' => 'taskId',
				'type' => 'enum',
				'values' => $taskList,
				'label' => 'Task',
				'description' => 'A task to be completed during the sprint',
				'required' => true
			),
		);
	}

	function getEditLink() : string{
		return '/Development/Tasks?objectAction=edit&id=' . $this->taskId;
	}
}