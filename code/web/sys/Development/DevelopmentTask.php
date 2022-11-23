<?php


class DevelopmentTask extends DataObject {
	public $__table = 'development_task';
	public $id;
	public $taskType; //Not Set, Bug, Feature, Code Maintenance, Server Maintenance, Support
	public $name;
	public $dueDate;
	public $dueDateComment;
	public $description;
	public $releaseId;
	public $_sprintId;
	public $status; //To do, Working on it, Needs Info, QA/Documentation, Blocked, Needs Review, Pending Deploy, QA Changes needed, Verify after Deploy, Done!!, Won't Do
	public $storyPoints;
	public $devTestingNotes;
	public $qaFeedback;
	public $releaseNoteText;
	public $newSettingsAdded;

	public $suggestedForCommunityDev; //True/False

	public $_epicId;
	public $_relatedTickets;

	public $_assignedDeveloper; //Can be multiple
	public $_assignedQA; //Can be multiple

	public $_requestingPartners; //Can be multiple
	public $_relatedComponents; //Can be multiple

	public static function getObjectStructure(): array {
		$taskTypes = [
			0 => 'Not Set',
			1 => 'Bug',
			2 => 'Feature',
			3 => 'Code Maintenance',
			4 => 'Server Maintenance',
			5 => 'Support'
		];
		$availableReleases = [
			0 => 'None'
		];
		require_once ROOT_DIR . '/sys/Development/AspenRelease.php';
		$aspenReleases = new AspenRelease();
		$aspenReleases->orderBy('releaseDate DESC');
		$aspenReleases->find();
		while ($aspenReleases->fetch()) {
			$availableReleases[$aspenReleases->id] = $aspenReleases->name;
		}

		$availableSprints = [
			0 => 'None'
		];
		require_once ROOT_DIR . '/sys/Development/DevelopmentSprint.php';
		$sprints = new DevelopmentSprint();
		$sprints->orderBy('endDate DESC');
		$sprints->find();
		while ($sprints->fetch()) {
			$availableSprints[$sprints->id] = $sprints->name;
		}

		$availableEpics = [
			0 => 'None'
		];
		require_once ROOT_DIR . '/sys/Development/DevelopmentEpic.php';
		$epic = new DevelopmentEpic();
		$epic->whereAdd('privateStatus NOT IN (9, 10)');
		$epic->orderBy('name ASC');
		$epic->find();
		while ($epic->fetch()) {
			$availableEpics[$epic->id] = $epic->name;
		}

		$statuses = [
			0 => 'To do',
			1 => 'Working on it',
			2 => 'Needs Info',
			3 => 'QA/Documentation',
			4 => 'Blocked',
			5 => 'Needs Review',
			6 => 'Pending Deploy',
			7 => 'QA Changes needed',
			8 => 'Verify after Deploy',
			9 => 'Done!!',
			10 => "Won't Do"
		];
		$storyPoints = [
			0 => 0,
			0.25 => 0.25,
			0.5 => 0.5,
			1 => 1,
			2 => 2,
			3 => 3,
			5 => 5,
			8 => 8,
			13 => 13,
			21 => 21
		];

		require_once ROOT_DIR . '/sys/Development/TaskTicketLink.php';
		$taskTicketLink = TaskTicketLink::getObjectStructure();
		unset($taskTicketLink['taskId']);

		require_once ROOT_DIR . '/sys/Development/TaskPartnerLink.php';
		$taskPartnerLink = TaskPartnerLink::getObjectStructure();
		unset($taskPartnerLink['taskId']);

		require_once ROOT_DIR . '/sys/Development/ComponentTaskLink.php';
		$componentTaskLink = ComponentTaskLink::getObjectStructure();
		unset($componentTaskLink['taskId']);

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id'
			],
			'taskType' => [
				'property' => 'taskType',
				'type' => 'enum',
				'values' => $taskTypes,
				'label' => 'Task Types',
				'description' => 'The type of the task',
				'default' => 0
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the task',
				'maxLength' => 255,
				'required' => true,
				'canBatchUpdate' => false
			],
			'epicId' => [
				'property' => 'epicId',
				'type' => 'enum',
				'label' => 'Related Epic',
				'description' => 'The epic related to the task (if any)',
				'values' => $availableEpics,
				'required' => true,
				'canBatchUpdate' => true
			],
			'dueDate' => [
				'property' => 'dueDate',
				'type' => 'date',
				'values' => $taskTypes,
				'label' => 'Due Date',
				'description' => 'When the task needs to be completed by'
			],
			'dueDateComment' => [
				'property' => 'dueDateComment',
				'type' => 'text',
				'label' => 'Due Date Comment',
				'description' => 'More information about the due date',
				'maxLength' => 255,
				'required' => false,
				'hideInLists' => true
			],
			'description' => [
				'property' => 'description',
				'type' => 'html',
				'label' => 'Description',
				'description' => 'A Description for the Task',
				'canBatchUpdate' => false,
				'hideInLists' => true
			],
			'relatedComponents' => [
				'property' => 'relatedComponents',
				'type' => 'oneToMany',
				'label' => 'Related Components',
				'description' => 'A list of components related to this task',
				'keyThis' => 'id',
				'keyOther' => 'taskId',
				'subObjectType' => 'ComponentTaskLink',
				'structure' => $componentTaskLink,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [],
				'hideInLists' => true
			],
			'sprintId' => [
				'property' => 'sprintId',
				'type' => 'enum',
				'label' => 'Assigned Sprint',
				'description' => 'The sprint related to the task (if any)',
				'values' => $availableSprints,
				'canBatchUpdate' => true,
				'default' => 0
			],
			'releaseId' => [
				'property' => 'releaseId',
				'type' => 'enum',
				'values' => $availableReleases,
				'label' => 'Release in',
				'description' => 'The planned release for the task',
				'default' => 0,
				'canBatchUpdate' => true
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'values' => $statuses,
				'label' => 'Status',
				'description' => 'The current status of the task',
				'default' => 0,
				'canBatchUpdate' => true
			],
			'storyPoints' => [
				'property' => 'storyPoints',
				'type' => 'enum',
				'values' => $storyPoints,
				'label' => 'Story Points',
				'description' => 'The number of story points assigned to the task',
				'default' => 0
			],
			'devTestingNotes' => [
				'property' => 'devTestingNotes',
				'type' => 'html',
				'label' => 'Testing Notes (from Development)',
				'description' => 'Testing notes to aid in testing the fix',
				'canBatchUpdate' => false,
				'hideInLists' => true
			],
			'qaFeedback' => [
				'property' => 'qaFeedback',
				'type' => 'html',
				'label' => 'QA Feedback (from testing)',
				'description' => 'Feedback from testing the fix',
				'canBatchUpdate' => false,
				'hideInLists' => true
			],
			'releaseNoteText' => [
				'property' => 'releaseNoteText',
				'type' => 'html',
				'label' => 'Descriptive Text to add to Release Notes',
				'description' => 'The text to be added to release notes',
				'canBatchUpdate' => false,
				'hideInLists' => true
			],
			'newSettingsAdded' => [
				'property' => 'newSettingsAdded',
				'type' => 'html',
				'label' => 'New Settings Added (for release notes)',
				'description' => 'New settings that were added as part of development',
				'canBatchUpdate' => false,
				'hideInLists' => true
			],
			'suggestedForCommunityDev' => [
				'property' => 'suggestedForCommunityDev',
				'type' => 'checkbox',
				'label' => 'Suggested for Community Development',
				'description' => 'If this is a good development for anyone in the community',
				'hideInLists' => true,
				'default' => 0
			],
			'relatedTickets' => [
				'property' => 'relatedTickets',
				'type' => 'oneToMany',
				'label' => 'Related Tickets',
				'description' => 'A list of all tickets related to this task',
				'keyThis' => 'id',
				'keyOther' => 'releaseId',
				'subObjectType' => 'TaskTicketLink',
				'structure' => $taskTicketLink,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'additionalOneToManyActions' => [],
				'hideInLists' => true
			],
			'requestingPartners' => [
				'property' => 'requestingPartners',
				'type' => 'oneToMany',
				'label' => 'Requesting Partners',
				'description' => 'A list of partners who would like to see this task',
				'keyThis' => 'id',
				'keyOther' => 'releaseId',
				'subObjectType' => 'TaskPartnerLink',
				'structure' => $taskPartnerLink,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'additionalOneToManyActions' => [],
				'hideInLists' => true
			],
		];
	}

	function getEditLink($context) : string{
		return '/Development/Tasks?objectAction=edit&id=' . $this->id;
	}

	public function __get($name) {
		if ($name == 'epicId') {
			if ($this->_epicId == null && $this->id) {
				require_once ROOT_DIR . '/sys/Development/TaskEpicLink.php';
				$epicTaskLink = new TaskEpicLink();
				$epicTaskLink->taskId = $this->id;
				if ($epicTaskLink->find(true)) {
					$this->_epicId = $epicTaskLink->epicId;
				} else {
					$this->_epicId = 0;
				}
			}
			return $this->_epicId;
		} elseif ($name == 'sprintId') {
			if ($this->_sprintId == null && $this->id) {
				require_once ROOT_DIR . '/sys/Development/TaskSprintLink.php';
				$sprintTaskLink = new TaskSprintLink();
				$sprintTaskLink->taskId = $this->id;
				if ($sprintTaskLink->find(true)) {
					$this->sprintId = $sprintTaskLink->sprintId;
				} else {
					$this->_sprintId = 0;
				}
			}
			return $this->_sprintId;
		} elseif ($name == 'relatedTickets') {
			return $this->getRelatedTickets();
		} elseif ($name == 'relatedComponents') {
			return $this->getRelatedComponents();
		} elseif ($name == 'requestingPartners') {
			return $this->getRequestingPartners();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "epicId") {
			$this->_epicId = $value;
		} else if ($name == "sprintId") {
			$this->_sprintId = $value;
		} else if ($name == "relatedTickets") {
			$this->_relatedTickets = $value;
		} elseif ($name == "relatedComponents") {
			$this->_relatedComponents = $value;
		} elseif ($name == "requestingPartners") {
			$this->_requestingPartners = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * @return int|bool
	 */
	public function update() {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveRelatedEpic();
			$this->saveRelatedSprint();
			$this->saveRelatedTickets();
			$this->saveRelatedComponents();
			$this->saveRequestingPartners();
		}
		return $ret;
	}

	public function insert() {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveRelatedEpic();
			$this->saveRelatedSprint();
			$this->saveRelatedTickets();
			$this->saveRelatedComponents();
			$this->saveRequestingPartners();
		}
		return $ret;
	}

	private function saveRelatedEpic(){
		if ($this->id){
			require_once ROOT_DIR . '/sys/Development/TaskEpicLink.php';
			if ($this->_epicId === 0){
				$epicTaskLink = new TaskEpicLink();
				$epicTaskLink->taskId = $this->id;
				$epicTaskLink->delete(true);
			}else{
				$epicTaskLink = new TaskEpicLink();
				$epicTaskLink->taskId = $this->id;
				if ($epicTaskLink->find(true)){
					$epicTaskLink->epicId = $this->_epicId;
					$epicTaskLink->update();
				}else{
					$epicTaskLink->epicId = $this->_epicId;
					$epicTaskLink->insert();
				}
			}
		}
	}

	private function saveRelatedSprint(){
		if ($this->id){
			require_once ROOT_DIR . '/sys/Development/TaskSprintLink.php';
			if ($this->_sprintId === 0){
				$taskSprintLink = new TaskSprintLink();
				$taskSprintLink->taskId = $this->id;
				$taskSprintLink->delete(true);
			}else{
				$taskSprintLink = new TaskSprintLink();
				$taskSprintLink->taskId = $this->id;
				if ($taskSprintLink->find(true)){
					$taskSprintLink->sprintId = $this->_sprintId;
					$taskSprintLink->update();
				}else{
					$taskSprintLink->sprintId = $this->_sprintId;
					$taskSprintLink->insert();
				}
			}
		}
	}

	public function saveRelatedTickets() {
		if (isset ($this->_relatedTickets) && is_array($this->_relatedTickets)) {
			$this->saveOneToManyOptions($this->_relatedTickets, 'taskId');
			unset($this->_relatedTickets);
		}
	}

	public function saveRelatedComponents() {
		if (isset ($this->_relatedComponents) && is_array($this->_relatedComponents)) {
			$this->saveOneToManyOptions($this->_relatedComponents, 'taskId');
			unset($this->_relatedComponents);
		}
	}

	public function saveRequestingPartners() {
		if (isset ($this->_requestingPartners) && is_array($this->_requestingPartners)) {
			$this->saveOneToManyOptions($this->_requestingPartners, 'taskId');
			unset($this->_requestingPartners);
		}
	}

	/**
	 * @return TaskTicketLink[]
	 */
	private function getRelatedTickets(): ?array {
		if (!isset($this->_relatedTickets) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/TaskTicketLink.php';
			$this->_relatedTickets = [];
			$task = new TaskTicketLink();
			$task->taskId = $this->id;
			$task->find();
			while ($task->fetch()) {
				$this->_relatedTickets[$task->id] = clone($task);
			}
		}
		return $this->_relatedTickets;
	}

	/**
	 * @return ComponentTaskLink[]
	 */
	private function getRelatedComponents(): ?array {
		if (!isset($this->_relatedComponents) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/ComponentTaskLink.php';
			$this->_relatedComponents = [];
			$component = new ComponentTaskLink();
			$component->taskId = $this->id;
			$component->find();
			while ($component->fetch()) {
				$this->_relatedComponents[$component->id] = clone($component);
			}
		}
		return $this->_relatedComponents;
	}

	/**
	 * @return TaskPartnerLink[]
	 */
	private function getRequestingPartners(): ?array {
		if (!isset($this->_requestingPartners) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/TaskPartnerLink.php';
			$this->_requestingPartners = [];
			$partnerLink = new TaskPartnerLink();
			$partnerLink->taskId = $this->id;
			$partnerLink->find();
			while ($partnerLink->fetch()) {
				$this->_requestingPartners[$partnerLink->id] = clone($partnerLink);
			}
		}
		return $this->_requestingPartners;
	}
}