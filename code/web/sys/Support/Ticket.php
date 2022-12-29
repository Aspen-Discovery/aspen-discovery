<?php

class Ticket extends DataObject {
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
	public $partnerPriority;
	public $partnerPriorityChangeDate;
	public $dateClosed;

	public $_relatedTasks;
	public $_relatedComponents;

	public function getNumericColumnNames(): array {
		return [
			'requestingPartner',
			'dateCreated',
			'partnerPriority',
			'partnerPriorityChangeDate',
			'dateClosed',
			'developmentTaskId',
		];
	}

	public static function getObjectStructure($context = ''): array {
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

		$partnerPriorities = [
			0 => 'None',
			1 => 'Priority 1',
			2 => 'Priority 2',
			3 => 'Priority 3',
		];

		require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
		$aspenSite = new AspenSite();
		$aspenSite->orderBy('name');
		$aspenSites = $aspenSite->fetchAll('id', 'name');
		$aspenSites[null] = 'None';

		require_once ROOT_DIR . '/sys/Development/TaskTicketLink.php';
		$taskTicketLinkStructure = TaskTicketLink::getObjectStructure($context);
		unset($taskTicketLinkStructure['ticketId']);

		require_once ROOT_DIR . '/sys/Development/ComponentTicketLink.php';
		$componentTicketLink = ComponentTicketLink::getObjectStructure($context);
		unset($componentTicketLink['ticketId']);

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'ticketId' => [
				'property' => 'ticketId',
				'type' => 'text',
				'label' => 'Ticket ID',
				'description' => 'The name of the Severity',
				'maxLength' => 20,
				'required' => true,
				'readOnly' => true,
			],
			'displayUrl' => [
				'property' => 'displayUrl',
				'type' => 'url',
				'label' => 'Display URL',
				'description' => 'The URL where the ticket can be found',
				'hideInLists' => true,
				'required' => true,
				'readOnly' => true,
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title for the ticket',
				'maxLength' => 512,
				'required' => true,
				'readOnly' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'description' => 'The description for the ticket',
				'hideInLists' => true,
				'required' => true,
				'readOnly' => true,
			],
			'dateCreated' => [
				'property' => 'dateCreated',
				'type' => 'timestamp',
				'label' => 'Date Created',
				'description' => 'When the ticket was created',
				'required' => true,
				'readOnly' => true,
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'values' => $ticketStatuses,
				'label' => 'Status',
				'description' => 'Status of the ticket',
				'required' => true,
				'readOnly' => true,
			],
			'queue' => [
				'property' => 'queue',
				'type' => 'enum',
				'values' => $ticketQueues,
				'label' => 'Queue',
				'description' => 'Queue of the ticket',
				'required' => true,
				'readOnly' => true,
			],
			'severity' => [
				'property' => 'severity',
				'type' => 'enum',
				'values' => $ticketSeverities,
				'label' => 'Severity',
				'description' => 'Severity of a bug',
				'required' => true,
				'readOnly' => true,
			],
			'relatedComponents' => [
				'property' => 'relatedComponents',
				'type' => 'oneToMany',
				'label' => 'Related Components',
				'description' => 'A list of components related to this ticket',
				'keyThis' => 'id',
				'keyOther' => 'epicId',
				'subObjectType' => 'ComponentTicketLink',
				'structure' => $componentTicketLink,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [],
				'hideInLists' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'requestingPartner' => [
				'property' => 'requestingPartner',
				'type' => 'enum',
				'values' => $aspenSites,
				'label' => 'Requesting Partner',
				'description' => 'The partner who entered the ticket',
				'required' => true,
				'readOnly' => true,
			],
			'partnerPriority' => [
				'property' => 'partnerPriority',
				'type' => 'enum',
				'values' => $partnerPriorities,
				'label' => 'Partner Priority',
				'description' => 'Priority for the partner',
				'required' => true,
				'readOnly' => true,
			],
			'partnerPriorityChangeDate' => [
				'property' => 'partnerPriorityChangeDate',
				'type' => 'timestamp',
				'label' => 'Partner Priority Last Changed',
				'description' => 'When the partner last changed the priority',
				'readOnly' => true,
			],
			'dateClosed' => [
				'property' => 'dateClosed',
				'type' => 'timestamp',
				'label' => 'Date Closed',
				'description' => 'When the ticket was closed',
				'required' => false,
				'readOnly' => true,
			],
			'relatedTasks' => [
				'property' => 'relatedTasks',
				'type' => 'oneToMany',
				'label' => 'Related Tasks',
				'description' => 'A list of all tasks assigned to this ticket',
				'keyThis' => 'id',
				'keyOther' => 'ticketId',
				'subObjectType' => 'TaskTicketLink',
				'structure' => $taskTicketLinkStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'additionalOneToManyActions' => [],
				'hideInLists' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
		];
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];

		if ($existingObject instanceof Ticket) {
			require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
			$rtConnection = new RequestTrackerConnection();
			if ($rtConnection->find(true)) {

				$objectActions[] = [
					'text' => 'Open in RT',
					'url' => $rtConnection->baseUrl . '/Ticket/Display.html?id=' . $existingObject->ticketId,
					'target' => '_blank',
				];
			}

			//Check to see if this is already part of a task.  If not, add a button to create a task from it.
			if (count($existingObject->getRelatedTasks()) == 0) {
				$objectActions[] = [
					'text' => 'Create Task',
					'url' => '/Development/Tasks?objectAction=createTaskFromTicket&ticketId=' . $existingObject->ticketId,
				];
			}

		}
		return $objectActions;
	}

	function getAdditionalListActions(): array {
		$objectActions = [];

		require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
		$rtConnection = new RequestTrackerConnection();
		if ($rtConnection->find(true)) {

			$objectActions[] = [
				'text' => 'Open in RT',
				'url' => $rtConnection->baseUrl . '/Ticket/Display.html?id=' . $this->ticketId,
				'target' => '_blank',
			];
			if (count($this->getRelatedTasks()) == 0) {
				$objectActions[] = [
					'text' => 'Create Task',
					'url' => '/Development/Tasks?objectAction=createTaskFromTicket&ticketId=' . $this->id,
				];
			}
		}
		return $objectActions;
	}

	public function __get($name) {
		if ($name == 'relatedTasks') {
			return $this->getRelatedTasks();
		} elseif ($name == 'relatedComponents') {
			return $this->getRelatedComponents();
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	public function __set($name, $value) {
		if ($name == "relatedTasks") {
			$this->_relatedTasks = $value;
		} elseif ($name == "relatedComponents") {
			$this->_relatedComponents = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * @return int|bool
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveRelatedComponents();
			$this->saveRelatedTasks();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveRelatedComponents();
			$this->saveRelatedTasks();
		}
		return $ret;
	}

	public function saveRelatedTasks() {
		if (isset ($this->_relatedTasks) && is_array($this->_relatedTasks)) {
			$this->saveOneToManyOptions($this->_relatedTasks, 'ticketId');
			unset($this->_relatedTasks);
		}
	}

	public function saveRelatedComponents() {
		if (isset ($this->_relatedComponents) && is_array($this->_relatedComponents)) {
			$this->saveOneToManyOptions($this->_relatedComponents, 'ticketId');
			unset($this->_relatedComponents);
		}
	}

	/**
	 * @return TaskTicketLink[]
	 */
	public function getRelatedTasks(): ?array {
		if (!isset($this->_relatedTasks) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/TaskTicketLink.php';
			$this->_relatedTasks = [];
			$task = new TaskTicketLink();
			$task->ticketId = $this->id;
			$task->find();
			while ($task->fetch()) {
				$this->_relatedTasks[$task->id] = clone($task);
			}
		}
		return $this->_relatedTasks;
	}

	/**
	 * @return ComponentTicketLink[]
	 */
	public function getRelatedComponents(): ?array {
		if (!isset($this->_relatedComponents) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/ComponentTicketLink.php';
			$this->_relatedComponents = [];
			$component = new ComponentTicketLink();
			$component->ticketId = $this->id;
			$component->find();
			while ($component->fetch()) {
				$this->_relatedComponents[$component->id] = clone($component);
			}
		}
		return $this->_relatedComponents;
	}

	/**
	 * @param ComponentTicketLink[] $relatedComponents
	 * @return void
	 */
	public function setRelatedComponents(array $relatedComponents) {
		$this->_relatedComponents = $relatedComponents;
	}

}