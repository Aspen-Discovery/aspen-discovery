<?php

class ComponentTicketLink extends DataObject {
	public $__table = 'component_ticket_link';
	public $id;
	public $ticketId;
	public $componentId;

	static function getObjectStructure($context = ''): array {
		$componentList = [];
		require_once ROOT_DIR . '/sys/Support/TicketComponentFeed.php';
		$component = new TicketComponentFeed();
		$component->orderBy('name');
		$component->find();
		while ($component->fetch()) {
			$componentList[$component->id] = $component->name;
		}

		$ticketList = [];
		require_once ROOT_DIR . '/sys/Support/Ticket.php';
		$ticket = new Ticket();
		$ticket->whereAdd('status <> "Closed"');
		$ticket->whereAdd("queue IN ('Development', 'Bugs')", 'AND');

		$ticket->orderBy('ticketId + 0 DESC');
		$ticket->find();
		while ($ticket->fetch()) {
			$ticketList[$ticket->id] = $ticket->ticketId . ': ' . $ticket->title;
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'ticketId' => [
				'property' => 'ticketId',
				'type' => 'enum',
				'values' => $ticketList,
				'label' => 'Ticket',
				'description' => 'The ticket related to the component',
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
		if ($context == 'relatedTickets') {
			return '/Greenhouse/Tickets?objectAction=edit&id=' . $this->ticketId;
		} else {
			return '/Support/TicketComponentFeed?objectAction=edit&id=' . $this->componentId;
		}
	}
}