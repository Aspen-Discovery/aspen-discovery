<?php

class DevelopmentEpic extends DataObject {
	public $__table = 'development_epic';
	public $id;
	public $name; //public
	public $description; //public
	public $linkToDesign;
	public $linkToRequirements;
	public $internalComments;

	public $dueDate;
	public $dueDateComment;

	//create public status from private status
	//public $publicStatus; //Under Consideration, Researching, Ready for Development, In Development, Pending Release, Done!!, Won't Do
	public $privateStatus; //Under Consideration, Planned, Researching, Writing Requirements, In Design / User Testing, Ready for Development, In Development, Blocked, Needs Review, Pending Release, Done!!, Won't Do

	public $_requestingPartners;
	public $_relatedTasks;
	public $_relatedComponents;
	public $_totalStoryPoints;

	public static function getObjectStructure(): array {
		$privateStatuses = [
			0 => 'Under Consideration',
			1 => 'Planned, Researching',
			2 => 'Writing Requirements',
			3 => 'In Design / User Testing',
			4 => 'Ready for Development',
			5 => 'In Development',
			6 => 'Blocked',
			7 => 'Needs Review',
			8 => 'Pending Release',
			9 => 'Done!!',
			10 => "Won't Do",
		];

		require_once ROOT_DIR . '/sys/Development/EpicPartnerLink.php';
		$epicPartnerLink = EpicPartnerLink::getObjectStructure();
		unset($epicPartnerLink['epicId']);

		require_once ROOT_DIR . '/sys/Development/TaskEpicLink.php';
		$taskEpicLinkStructure = TaskEpicLink::getObjectStructure();
		unset($taskEpicLinkStructure['epicId']);

		require_once ROOT_DIR . '/sys/Development/ComponentEpicLink.php';
		$componentEpicLink = ComponentEpicLink::getObjectStructure();
		unset($componentEpicLink['epicId']);

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the epic',
				'maxLength' => 255,
				'required' => true,
				'canBatchUpdate' => false,
			],
			'description' => [
				'property' => 'description',
				'type' => 'html',
				'label' => 'Description',
				'description' => 'A Description for the Task',
				'canBatchUpdate' => false,
				'hideInLists' => true,
			],
			'linkToDesign' => [
				'property' => 'linkToDesign',
				'type' => 'url',
				'label' => 'Link To Design',
				'description' => 'An optional link to where the designs are located',
				'maxLength' => 255,
			],
			'linkToRequirements' => [
				'property' => 'linkToRequirements',
				'type' => 'url',
				'label' => 'Link To Requirements',
				'description' => 'An optional link to where the requirements are located',
				'maxLength' => 255,
			],
			'internalComments' => [
				'property' => 'internalComments',
				'type' => 'html',
				'label' => 'Internal Comments',
				'description' => 'Internal Comments on the Epic',
				'canBatchUpdate' => false,
				'hideInLists' => true,
			],
			'dueDate' => [
				'property' => 'dueDate',
				'type' => 'date',
				'label' => 'Due Date',
				'description' => 'A date we need to deliver the epic by',
			],
			'dueDateComment' => [
				'property' => 'dueDateComment',
				'type' => 'textarea',
				'label' => 'Due Date Comment',
				'description' => 'Comments about the due date',
				'canBatchUpdate' => false,
				'hideInLists' => true,
			],
			'privateStatus' => [
				'property' => 'privateStatus',
				'type' => 'enum',
				'values' => $privateStatuses,
				'label' => 'Status',
				'description' => 'The current status of the epic',
				'default' => 0,
			],
			'relatedComponents' => [
				'property' => 'relatedComponents',
				'type' => 'oneToMany',
				'label' => 'Related Components',
				'description' => 'A list of components related to this epic',
				'keyThis' => 'id',
				'keyOther' => 'epicId',
				'subObjectType' => 'ComponentEpicLink',
				'structure' => $componentEpicLink,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [],
				'hideInLists' => true,
			],
			'requestingPartners' => [
				'property' => 'requestingPartners',
				'type' => 'oneToMany',
				'label' => 'Requesting Partners',
				'description' => 'A list of partners who would like to see this epic',
				'keyThis' => 'id',
				'keyOther' => 'epicId',
				'subObjectType' => 'EpicPartnerLink',
				'structure' => $epicPartnerLink,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'additionalOneToManyActions' => [],
				'hideInLists' => true,
			],
			'relatedTasks' => [
				'property' => 'relatedTasks',
				'type' => 'oneToMany',
				'label' => 'Related Tasks',
				'description' => 'A list of all tasks assigned to this release',
				'keyThis' => 'id',
				'keyOther' => 'releaseId',
				'subObjectType' => 'TaskEpicLink',
				'structure' => $taskEpicLinkStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'additionalOneToManyActions' => [],
				'hideInLists' => true,
			],
			'totalStoryPoints' => [
				'property' => 'totalStoryPoints',
				'type' => 'label',
				'label' => 'Total Story Points',
				'description' => 'The total number of story points assigned to the release',
			],
		];
	}

	public function __get($name) {
		if ($name == 'totalStoryPoints') {
			if (!isset($this->_totalStoryPoints) && $this->id) {
				$this->_totalStoryPoints = 0;
				$relatedTasks = $this->getRelatedTasks();
				foreach ($relatedTasks as $task) {
					$this->_totalStoryPoints += $task->getTask()->storyPoints;
				}
			}
			return $this->_totalStoryPoints;
		} elseif ($name == 'relatedTasks') {
			return $this->getRelatedTasks();
		} elseif ($name == 'relatedComponents') {
			return $this->getRelatedComponents();
		} elseif ($name == 'requestingPartners') {
			return $this->getRequestingPartners();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value) {
		if ($name == "relatedTasks") {
			$this->_relatedTasks = $value;
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
			$this->saveRelatedTasks();
			$this->saveRelatedComponents();
			$this->saveRequestingPartners();
		}
		return $ret;
	}

	public function insert() {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveRelatedTasks();
			$this->saveRelatedComponents();
			$this->saveRequestingPartners();
		}
		return $ret;
	}

	public function saveRelatedTasks() {
		if (isset ($this->_relatedTasks) && is_array($this->_relatedTasks)) {
			$this->saveOneToManyOptions($this->_relatedTasks, 'epicId');
			unset($this->_relatedTasks);
		}
	}

	public function saveRelatedComponents() {
		if (isset ($this->_relatedComponents) && is_array($this->_relatedComponents)) {
			$this->saveOneToManyOptions($this->_relatedComponents, 'epicId');
			unset($this->_relatedComponents);
		}
	}

	public function saveRequestingPartners() {
		if (isset ($this->_requestingPartners) && is_array($this->_requestingPartners)) {
			$this->saveOneToManyOptions($this->_requestingPartners, 'epicId');
			unset($this->_requestingPartners);
		}
	}

	/**
	 * @return TaskEpicLink[]
	 */
	private function getRelatedTasks(): ?array {
		if (!isset($this->_relatedTasks) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/TaskEpicLink.php';
			$this->_relatedTasks = [];
			$task = new TaskEpicLink();
			$task->epicId = $this->id;
			$task->orderBy('weight asc');
			$task->find();
			while ($task->fetch()) {
				$this->_relatedTasks[$task->id] = clone($task);
			}
		}
		return $this->_relatedTasks;
	}

	/**
	 * @return ComponentEpicLink[]
	 */
	private function getRelatedComponents(): ?array {
		if (!isset($this->_relatedComponents) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/ComponentEpicLink.php';
			$this->_relatedComponents = [];
			$component = new ComponentEpicLink();
			$component->epicId = $this->id;
			$component->find();
			while ($component->fetch()) {
				$this->_relatedComponents[$component->id] = clone($component);
			}
		}
		return $this->_relatedComponents;
	}

	/**
	 * @return EpicPartnerLink[]
	 */
	private function getRequestingPartners(): ?array {
		if (!isset($this->_requestingPartners) && $this->id) {
			require_once ROOT_DIR . '/sys/Development/EpicPartnerLink.php';
			$this->_requestingPartners = [];
			$partnerLink = new EpicPartnerLink();
			$partnerLink->epicId = $this->id;
			$partnerLink->find();
			while ($partnerLink->fetch()) {
				$this->_requestingPartners[$partnerLink->id] = clone($partnerLink);
			}
		}
		return $this->_requestingPartners;
	}
}