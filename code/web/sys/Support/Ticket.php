<?php

class Ticket extends DataObject
{
	public $__table = 'ticket';
	public $id;
	public $ticketId;
	public $displayUrl;
	public $title;
	public $description;
	public $dateCreated;
	public $requestingPartner;
	public $status;
	public $queue;
	public $severity;
	public $component;
	public $partnerPriority;
	public $partnerPriorityChangeDate;
	public $dateClosed;
	public $developmentTaskId;

	public function getNumericColumnNames(): array
	{
		return ['requestingPartner', 'dateCreated', 'partnerPriority', 'partnerPriorityChangeDate', 'dateClosed', 'developmentTaskId'];
	}

	public static function getObjectStructure() : array {
		//Get a list of statuses
		require_once ROOT_DIR . '/sys/Support/TicketStatusFeed.php';
		$ticketStatusFeed = new TicketStatusFeed();
		$ticketStatuses = $ticketStatusFeed->fetchAll('name');
		$ticketStatuses['Closed'] = 'Closed';
		ksort($ticketStatuses);

		require_once ROOT_DIR . '/sys/Support/TicketQueueFeed.php';
		$ticketQueueFeed = new TicketQueueFeed();
		$ticketQueues = $ticketQueueFeed->fetchAll('name');
		$ticketQueues[''] = 'None';
		ksort($ticketQueues);

		require_once ROOT_DIR . '/sys/Support/TicketSeverityFeed.php';
		$ticketSeverityFeed = new TicketSeverityFeed();
		$ticketSeverities = $ticketSeverityFeed->fetchAll('name');
		$ticketSeverities[''] = 'None';
		ksort($ticketSeverities);

		$partnerPriorities = [0 => 'None', 1=>'Priority 1', 2=>'Priority 2', 3=>'Priority 3'];

		require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
		$aspenSite = new AspenSite();
		$aspenSite->orderBy('name');
		$aspenSites = $aspenSite->fetchAll('id', 'name');
		$aspenSites[0] = 'None';

		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'ticketId' => array('property' => 'ticketId', 'type' => 'text', 'label' => 'Ticket ID', 'description' => 'The name of the Severity', 'maxLength' => 20, 'required' => true, 'readOnly'=>true),
			'displayUrl' => array('property' => 'displayUrl', 'type' => 'url', 'label' => 'Display URL', 'description' => 'The URL where the ticket can be found', 'hideInLists' => true, 'required' => true, 'readOnly'=>true),
			'title' => array('property' => 'title', 'type' => 'text', 'label' => 'Title', 'description' => 'The title for the ticket', 'maxLength' => 512, 'required' => true, 'readOnly'=>true),
			'description' => array('property' => 'description', 'type' => 'textarea', 'label' => 'Description', 'description' => 'The description for the ticket', 'hideInLists'=>true, 'required' => true, 'readOnly'=>true),
			'dateCreated' => array('property' => 'dateCreated', 'type' => 'timestamp', 'label' => 'Date Created', 'description' => 'When the ticket was created', 'required' => true, 'readOnly'=>true),
			'status' => array('property' => 'status', 'type' => 'enum', 'values' => $ticketStatuses, 'label' => 'Status', 'description' => 'Status of the ticket', 'required' => true, 'readOnly'=>true),
			'queue' => array('property' => 'queue', 'type' => 'enum', 'values' => $ticketQueues, 'label' => 'Queue', 'description' => 'Queue of the ticket', 'required' => true, 'readOnly'=>true),
			'severity' => array('property' => 'severity', 'type' => 'enum', 'values' => $ticketSeverities, 'label' => 'Severity', 'description' => 'Severity of a bug', 'required' => true, 'readOnly'=>true),
			'requestingPartner' => array('property' => 'requestingPartner', 'type' => 'enum', 'values' => $aspenSites, 'label' => 'Requesting Partner', 'description' => 'The partner who entered the ticket', 'required' => true, 'readOnly'=>true),
			'partnerPriority' => array('property' => 'partnerPriority', 'type' => 'enum', 'values' => $partnerPriorities, 'label' => 'Partner Priority', 'description' => 'Priority for the partner', 'required' => true, 'readOnly'=>true),
			'partnerPriorityChangeDate' => array('property' => 'partnerPriorityChangeDate', 'type' => 'timestamp', 'label' => 'Partner Priority Last Changed', 'description' => 'When the partner last changed the priority', 'required' => true, 'readOnly'=>true),
			'dateClosed' => array('property' => 'dateClosed', 'type' => 'timestamp', 'label' => 'Date Closed', 'description' => 'When the ticket was closed', 'required' => false, 'readOnly'=>true),
		];
	}
}