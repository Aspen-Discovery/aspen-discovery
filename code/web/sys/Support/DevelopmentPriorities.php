<?php

class DevelopmentPriorities extends DataObject {
	public $__table = 'development_priorities';
	public $id;
	public $priority1;
	public $priority2;
	public $priority3;

	public static function getObjectStructure($context = '') {
		require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
		$supportConnections = new RequestTrackerConnection();
		$activeTickets = [];
		if ($supportConnections->find(true)) {
			$activeTickets = $supportConnections->getActiveTickets();
		}
		$ticketsToPrioritize = ['-1' => 'None'];
		foreach ($activeTickets as $ticket) {
			$ticketsToPrioritize[$ticket['id']] = '(' . $ticket['id'] . ') ' . $ticket['title'];
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'priority1' => [
				'property' => 'priority1',
				'type' => 'enum',
				'values' => $ticketsToPrioritize,
				'label' => 'Priority 1',
				'description' => '1st Priority for Development',
				'default' => '-1',
			],
			'priority2' => [
				'property' => 'priority2',
				'type' => 'enum',
				'values' => $ticketsToPrioritize,
				'label' => 'Priority 2',
				'description' => '2nd Priority for Development',
				'default' => '-1',
			],
			'priority3' => [
				'property' => 'priority3',
				'type' => 'enum',
				'values' => $ticketsToPrioritize,
				'label' => 'Priority 3',
				'description' => '3rd Priority for Development',
				'default' => '-1',
			],
		];
	}
}