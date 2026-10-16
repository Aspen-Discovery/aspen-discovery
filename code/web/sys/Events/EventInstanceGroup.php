<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/Event.php';
require_once ROOT_DIR . '/sys/Events/EventInstance.php';

class EventInstanceGroup extends DataObject {
	public $__table = 'event';
	public $id;
	public $title;
	public $startDate;
	public $_instances;
	public $deleted;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$instanceStructure = EventInstance::getObjectStructure($context);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The name of the event',
				'readOnly' => true,
			],
			'startDate' => [
				'property' => 'startDate',
				'type' => 'date',
				'label' => 'Date',
				'description' => 'Event start date',
				'readOnly' => true,
			],
			'instances' => [
				'property' => 'instances',
				'type' => 'oneToMany',
				'label' => 'Instances',
				'description' => 'A list of instances for this event',
				'keyThis' => 'id',
				'keyOther' => 'id',
				'subObjectType' => 'EventInstance',
				'structure' => $instanceStructure,
				'additionalOneToManyActions' => [
					'showPastEvents' => [
						'text' => 'Show Past Events',
						'url' => '/Events/EventInstances?id=$id&amp;objectAction=edit&amp;pastEvents=true',
					],
				],
				'storeDb' => true,
				'allowEdit' => true,
				'canDelete' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveInstances();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveInstances();
		}
		return $ret;
	}

	public function saveInstances() : void {
		if (isset ($this->_instances) && is_array($this->_instances)) {
			$this->saveOneToManyOptions($this->_instances, 'eventId');
			unset($this->_instances);
		}
	}

	public function __get($name) {
		if ($name == 'instances') {
			if (isset($_GET['pastEvents']) && $_GET['pastEvents'] == 'true') {
				return $this->getAllInstances();
			}
			return $this->getFutureInstances();
//		} if ($name == "libraries") {
//			return $this->getLibraries();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'instances') {
			$this->setInstances($value);
//		}if ($name == "libraries") {
//			$this->_libraries = $value;
		}  else {
			parent::__set($name, $value);
		}
	}

	/** @return ?EventInstance[] */
	public function getFutureInstances(): ?array {
		if (!isset($this->_instances) && $this->id) {
			$this->_instances = [];
			$instance = new EventInstance();
			$instance->eventId = $this->id;
			$instance->deleted = 0;
			$todayDate = date('Y-m-d');
			$todayTime = date('H:i:s');
			$instance->whereAdd("date > '$todayDate' OR (date = '$todayDate' and time > '$todayTime')");
			$instance->find();
			while ($instance->fetch()) {
				$this->_instances[$instance->id] = clone($instance);
			}
		}
		return $this->_instances;
	}

	/** @return ?EventInstance[] */
	public function getAllInstances(): ?array {
		if (!isset($this->_instances) && $this->id) {
			$this->_instances = [];
			$instance = new EventInstance();
			$instance->eventId = $this->id;
			$instance->deleted = 0;
			$instance->find();
			while ($instance->fetch()) {
				$this->_instances[$instance->id] = clone($instance);
			}
		}
		return $this->_instances;
	}

	public function setInstances($value) : void {
		$this->_instances = $value;
	}
}