<?php /** @noinspection PhpMissingFieldTypeInspection */

class BackgroundProcess extends DataObject {
	public $__table = 'background_process';
	public $id;
	public $owningUserId;
	public $name;
	public $notes;
	public $startTime;
	public $endTime;
	public $isRunning;

	public function getNumericColumnNames() : array {
		return ['owningUserId', 'startTime', 'endTime', 'isRunning'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'owningUser' => [
				'property' => 'owningUser',
				'type' => 'text',
				'label' => 'User',
				'description' => 'The user who started the background process',
				'readOnly' => true,
			],
			'name' => [
				'property' => 'name',
				'type' => 'label',
				'label' => 'Name',
				'description' => 'The name of the background process',
				'readOnly' => true,
			],
			'startTime' => [
				'property' => 'startTime',
				'type' => 'timestamp',
				'label' => 'Start Time',
				'description' => 'The start time of the background process',
				'readOnly' => true,
			],
			'endTime' => [
				'property' => 'endTime',
				'type' => 'timestamp',
				'label' => 'End Time',
				'description' => 'The end time of the background process',
				'readOnly' => true,
			],
			'isRunning' => [
				'property' => 'isRunning',
				'type' => 'checkbox',
				'label' => 'Running?',
				'description' => 'Whether the background process is running',
				'readOnly' => true,
			],
			'notes' => [
				'property' => 'notes',
				'type' => 'textarea',
				'label' => 'Notes',
				'description' => 'Notes for the background process',
				'readOnly' => true,
				'hideInLists' => true,
				'rows' => '25',
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	private static $usersById = [];

	function __get($name) {
		if ($name == 'owningUser') {
			if (empty($this->owningUserId)) {
				return translate([
					'text' => 'Unknown',
					'isPublicFacing' => true,
				]);
			}
			if (empty($this->_data['owningUser'])) {
				if (!array_key_exists($this->owningUserId, BackgroundProcess::$usersById)) {
					$user = new User();
					$user->id = $this->owningUserId;
					if ($user->find(true)) {
						BackgroundProcess::$usersById[$this->owningUserId] = $user;
					}
				}
				if (array_key_exists($this->owningUserId, BackgroundProcess::$usersById)) {
					$user = BackgroundProcess::$usersById[$this->owningUserId];
					if (!empty($user->displayName)) {
						if (empty($user->getBarcode())) {
							$this->_data['owningUser'] = $user->displayName;
						}else{
							$this->_data['owningUser'] = $user->displayName . ' (' . $user->getBarcode() . ')';
						}
					} else {
						if (empty($user->getBarcode())) {
							$this->_data['owningUser'] = $user->firstname . ' ' . $user->lastname;
						}else{
							$this->_data['owningUser'] = $user->firstname . ' ' . $user->lastname . ' (' . $user->getBarcode() . ')';
						}
					}
				} else {
					$this->_data['owningUser'] = translate([
						'text' => 'Unknown',
						'isPublicFacing' => true,
					]);
				}

			}
		}
		return $this->_data[$name] ?? null;
	}

	public function addNote(string $note) : void {
		$this->notes .= $note . "\n";
		$this->update();
	}

	public function update(string $context = '') : int|bool {
		if ($this->isRunning == 0) {
			//Create a user message
			require_once ROOT_DIR . '/sys/Account/UserMessage.php';
			$userMessage = new UserMessage();
			$userMessage->userId = $this->owningUserId;
			$userMessage->messageType = 'backgroundProcessCompletion';
			$userMessage->relatedObjectId = $this->id;
			$userMessage->message = translate(['text' => 'Background process %1% finished.', 1 => $this->id, 'isAdminFacing'=> true]);
			$userMessage->action1Title = translate(['text' => 'View Details', 'isAdminFacing'=> true]);
			$userMessage->action1 = "window.location.href = '/Admin/BackgroundProcesses?objectAction=edit&id=$this->id'";
			$userMessage->insert();
		}
		return parent::update($context);
	}

	public function endProcess(?string $message) : void {
		if (!empty($message)) {
			$this->notes .= $message . "\n";
		}
		$this->isRunning = 0;
		$this->endTime = time();
		$this->update();
	}
}
