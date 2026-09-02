<?php
/** @noinspection PhpMissingFieldTypeInspection */

class RecordGroupingOverride extends DataObject {
	public $__table = 'record_grouping_overrides';
	public $id;
	public $source;
	public $record_id;
	public $grouped_work_permanent_id;
	public $added_by;
	public $date_added;

	static array $_objectStructure = [];

	public function getUniquenessFields(): array {
		return ['source', 'record_id'];
	}

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		global $indexingProfiles;
		global $sideLoadSettings;
		$availableSources = [];
		foreach ($indexingProfiles as $profile) {
			$availableSources[$profile->name] = $profile->name;
		}
		foreach ($sideLoadSettings as $profile) {
			$availableSources[$profile->name] = $profile->name;
		}
		$availableSources['axis360'] = 'Boundless';
		$availableSources['cloud_library'] = 'Cloud Library';
		$availableSources['hoopla'] = 'Hoopla';
		$availableSources['overdrive'] = 'Overdrive';
		$availableSources['palace_project'] = 'Palace Project';

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The identifier of this override.',
			],
			'addedByName' => [
				'property' => 'addedByName',
				'type' => 'label',
				'label' => 'Added By',
				'description' => 'The user who created this override.',
			],
			'date_added' => [
				'property' => 'date_added',
				'type' => 'timestamp',
				'label' => 'Date Added',
				'description' => 'When this override was created.',
				'readOnly' => true,
			],
			'source' => [
				'property' => 'source',
				'type' => 'enum',
				'values' => $availableSources,
				'label' => 'Source',
				'description' => 'The source of the record.',
				'required' => true,
			],
			'record_id' => [
				'property' => 'record_id',
				'type' => 'text',
				'label' => 'Record ID',
				'description' => 'The identifier of the record within its source.',
				'maxLength' => 50,
				'required' => true,
			],
			'grouped_work_permanent_id' => [
				'property' => 'grouped_work_permanent_id',
				'type' => 'text',
				'label' => 'Grouped Work Permanent ID',
				'description' => 'The permanent ID of the grouped work to which this record should belong.',
				'maxLength' => 40,
				'required' => true,
			],
			'grouped_work_display' => [
				'property' => 'grouped_work_display',
				'type' => 'label',
				'label' => 'Grouped Work',
				'description' => 'The grouped work to which this record is assigned.',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	private static array $usersById = [];

	public function __get($name) {
		if ($name == 'addedByName') {
			if (empty($this->_data['addedByName'])) {
				if (!empty($this->added_by)) {
					if (array_key_exists($this->added_by, RecordGroupingOverride::$usersById)) {
						$this->_data['addedByName'] = RecordGroupingOverride::$usersById[$this->added_by];
					} else {
						require_once ROOT_DIR . '/sys/Account/User.php';
						$user = new User();
						$user->id = $this->added_by;
						if ($user->find(true)) {
							$displayName = $user->getDisplayName();
							$barcode = $user->getBarcode();
							if (!empty($barcode)) {
								$this->_data['addedByName'] = trim($barcode . ' - ' . $displayName, ' -');
							} elseif (!empty($user->username)) {
								$this->_data['addedByName'] = trim($user->username . ' - ' . $displayName, ' -');
							} else {
								$this->_data['addedByName'] = $displayName;
							}
							RecordGroupingOverride::$usersById[$this->added_by] = $this->_data['addedByName'];
						} else {
							$this->_data['addedByName'] = 'User ID: ' . $this->added_by;
						}
					}
				} else {
					$this->_data['addedByName'] = 'Unknown';
				}
			}
		} elseif ($name == 'grouped_work_display') {
			if (empty($this->_data['grouped_work_display']) && !empty($this->grouped_work_permanent_id)) {
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $this->grouped_work_permanent_id;
				if ($groupedWork->find(true)) {
					$this->_data['grouped_work_display'] = $groupedWork->full_title . ' by ' . $groupedWork->author;
				} else {
					$this->_data['grouped_work_display'] = 'Unknown (Permanent ID: ' . $this->grouped_work_permanent_id . ')';
				}
			}
		}
		return $this->_data[$name] ?? null;
	}

	public function insert(string $context = ''): bool|int {
		require_once ROOT_DIR . '/sys/Grouping/ManuallyGroupedWorkRecord.php';
		$manuallyGroupedRecord = new ManuallyGroupedWorkRecord();
		$manuallyGroupedRecord->selectAdd();
		$manuallyGroupedRecord->selectAdd('manually_grouped_work_id');
		$manuallyGroupedRecord->type = $this->source;
		$manuallyGroupedRecord->identifier = $this->record_id;
		if ($manuallyGroupedRecord->find(true)) {
			require_once ROOT_DIR . '/sys/Grouping/ManualGroupedWork.php';
			$manualGroupedWork = new ManualGroupedWork();
			$manualGroupedWork->selectAdd();
			$manualGroupedWork->selectAdd('id, title');
			$manualGroupedWork->id = $manuallyGroupedRecord->manually_grouped_work_id;
			if ($manualGroupedWork->find(true)) {
				$this->setLastError("Cannot create a record grouping override for source '$this->source' and record_id '$this->record_id' because it is already part of manually grouped work '$manualGroupedWork->title' (ID: $manualGroupedWork->id). Remove it from the manual group first.");
				return false;
			}
		}

		$existingOverride = new RecordGroupingOverride();
		$existingOverride->selectAdd();
		$existingOverride->selectAdd('id');
		$existingOverride->source = $this->source;
		$existingOverride->record_id = $this->record_id;
		if ($existingOverride->find(true)) {
			$this->setLastError("An override already exists for source '$this->source' and record_id '$this->record_id' (ID: $existingOverride->id).");
			return false;
		}

		if (empty($this->date_added)) {
			$this->date_added = time();
		}
		if (empty($this->added_by)) {
			$this->added_by = UserAccount::getActiveUserId();
		}
		$ret = parent::insert();
		if ($ret) {
			$this->triggerReindex();
		}
		return $ret;
	}

	public function update(string $context = ''): bool|int {
		require_once ROOT_DIR . '/sys/Grouping/ManuallyGroupedWorkRecord.php';
		$manuallyGroupedRecord = new ManuallyGroupedWorkRecord();
		$manuallyGroupedRecord->selectAdd();
		$manuallyGroupedRecord->selectAdd('manually_grouped_work_id');
		$manuallyGroupedRecord->type = $this->source;
		$manuallyGroupedRecord->identifier = $this->record_id;
		if ($manuallyGroupedRecord->find(true)) {
			require_once ROOT_DIR . '/sys/Grouping/ManualGroupedWork.php';
			$manualGroupedWork = new ManualGroupedWork();
			$manualGroupedWork->selectAdd();
			$manualGroupedWork->selectAdd('id, title');
			$manualGroupedWork->id = $manuallyGroupedRecord->manually_grouped_work_id;
			if ($manualGroupedWork->find(true)) {
				$this->setLastError("Cannot update record grouping override for source '$this->source' and record_id '$this->record_id' because it is already part of manually grouped work '$manualGroupedWork->title' (ID: $manualGroupedWork->id). Remove it from the manual group first.");
				return false;
			}
		}

		$ret = parent::update();
		if ($ret) {
			$this->triggerReindex();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): int {
		$ret = parent::delete($useWhere);
		if ($ret) {
			$this->triggerReindex();
		}
		return $ret;
	}

	private function triggerReindex(): void {
		require_once ROOT_DIR . '/sys/Indexing/RecordIdentifiersToReload.php';
		$recordToReload = new RecordIdentifiersToReload();
		$recordToReload->type = $this->source;
		$recordToReload->identifier = $this->record_id;
		if (!$recordToReload->find(true)) {
			$recordToReload->insert();
		}

		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $this->grouped_work_permanent_id;
		if ($groupedWork->find(true)) {
			$groupedWork->forceReindex(true);
		}
	}

	public function getAdditionalListActions(): array {
		$actions = [];
		if (!empty($this->grouped_work_permanent_id)) {
			$actions[] = [
				'text' => 'View Grouped Work',
				'url' => '/GroupedWork/' . $this->grouped_work_permanent_id,
				'target' => '_blank',
				'icon' => 'fas fa-external-link-alt',
			];
		}
		return $actions;
	}
}
