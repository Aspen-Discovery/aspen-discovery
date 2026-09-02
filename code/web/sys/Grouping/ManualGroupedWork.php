<?php
/** @noinspection PhpMissingFieldTypeInspection */

class ManualGroupedWork extends DataObject {
	public $__table = 'manually_grouped_works';
	public $id;
	public $title;
	public $description;
	public $created_by;
	public $date_created;
	public $last_updated;
	public $grouped_work_permanent_id;

	private $_records;
	// Keep track of grouped work permanent IDs that have been scheduled for reindex this request.
	private static $reindexedPermanentIds = [];

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
				'description' => 'The unique id of the manually created group.',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'maxLength' => 150,
				'label' => 'Title',
				'description' => 'The title of this manually created group.',
				'required' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'description' => 'An internal description of why this manual group exists.',
			],
			'created_by' => [
				'property' => 'created_by',
				'type' => 'hidden',
				'label' => 'Created By',
				'description' => 'The user who created this manual group.',
				'hideInLists' => true,
			],
			'created_by_display' => [
				'property' => 'created_by_display',
				'type' => 'label',
				'label' => 'Created By',
				'description' => 'The display name of the user who created this manual group.',
				'hideInLists' => false,
			],
			'date_created' => [
				'property' => 'date_created',
				'type' => 'timestamp',
				'label' => 'Date Created',
				'description' => 'The date this manual group was created.',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'last_updated' => [
				'property' => 'last_updated',
				'type' => 'timestamp',
				'label' => 'Last Updated',
				'description' => 'The date this manual group was last updated.',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'record_summary' => [
				'property' => 'record_summary',
				'type' => 'hidden',
				'label' => 'Records',
				'description' => 'Summary of records in this manual group.',
				'hideInLists' => false,
			],
			'records' => [
				'property' => 'records',
				'type' => 'oneToMany',
				'label' => 'Records',
				'description' => 'The records to include in this manual group.',
				'noteBullets' => [
					'In the table below, select the source of the record, select the identifier\'s type, and input its identifier value.',
					'The "Resolved Record ID" field cannot be edited and will be automatically populated with the record\'s primary identifier if a record is found.',
				],
				'keyThis' => 'id',
				'keyOther' => 'manually_grouped_work_id',
				'subObjectType' => 'ManuallyGroupedWorkRecord',
				'structure' => ManuallyGroupedWorkRecord::getObjectStructure(),
				'canAddNew' => true,
				'canDelete' => true,
				'sortable' => false,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @return string|null
	 */
	public function getGroupedWorkPermanentId(): ?string {
		return $this->grouped_work_permanent_id;
	}

	public function insert($context = ''): bool|int {
		if (empty($this->created_by)) {
			$this->created_by = UserAccount::getActiveUserId();
		}
		if (empty($this->date_created)) {
			$this->date_created = time();
		}
		$this->last_updated = time();
		$ret = parent::insert();
		if ($ret !== false) {
			// If records were populated via the form, move them into the internal records array.
			if (empty($this->_records) && isset($this->_data['records'])) {
				$this->_records = $this->_data['records'];
			}
			if (!empty($this->_records)) {
				$this->saveRecords();
			}
		}
		return $ret;
	}

	public function update($context = ''): bool|int {
		if (empty($this->date_created) && !empty($this->id)) {
			$original = new ManualGroupedWork();
			$original->id = $this->id;
			if ($original->find(true) && !empty($original->date_created)) {
				$this->date_created = $original->date_created;
			}
		}
		$this->last_updated = time();
		$ret = parent::update();
		if ($ret !== false) {
			if (isset($this->_records) && is_array($this->_records)) {
				$this->saveRecords();
			}
		}
		return $ret;
	}

	public function __get($name) {
		if ($name === 'records') {
			return $this->getRecords();
		} elseif ($name === 'record_summary') {
			$records = $this->getRecords();
			if (!empty($records)) {
				$identifiers = [];
				foreach ($records as $record) {
					if (!empty($record->identifier)) {
						$identifiers[] = $record->identifier;
					}
				}
				if (!empty($identifiers)) {
					return implode(", ", $identifiers);
				}
			}
			return 'No records';
		} elseif ($name === 'created_by_display') {
			if (!empty($this->created_by)) {
				require_once ROOT_DIR . '/sys/Account/User.php';
				$user = new User();
				$user->id = $this->created_by;
				if ($user->find(true)) {
					$displayName = $user->getDisplayName();
					$barcode = $user->getBarcode();
					if (!empty($barcode)) {
						return trim($barcode . ' - ' . $displayName, ' -');
					} elseif (!empty($user->username)) {
						return trim($user->username . ' - ' . $displayName, ' -');
					} else {
						return $displayName;
					}
				} else {
					return 'User ID: ' . $this->created_by;
				}
			}
			return 'Not set';
		}
		return parent::__get($name);
	}

	public function __set($name, $value) {
		if ($name === 'records') {
			$this->_records = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete($useWhere = false, bool $hardDelete = false): int {
		$ret = parent::delete($useWhere);
		if ($ret) {
			$this->reindexRecords();
			$this->clearRecords();
		}
		return $ret;
	}

	/**
	 * Get all records associated with this manually grouped work.
	 *
	 * @return array|null
	 */
	public function getRecords(): ?array {
		if (!isset($this->_records) && $this->id) {
			$this->_records = [];
			$manuallyGroupedWorkRecord = new ManuallyGroupedWorkRecord();
			$manuallyGroupedWorkRecord->manually_grouped_work_id = $this->id;
			$manuallyGroupedWorkRecord->orderBy('id');
			$manuallyGroupedWorkRecord->find();
			while ($manuallyGroupedWorkRecord->fetch()) {
				$this->_records[$manuallyGroupedWorkRecord->id] = clone $manuallyGroupedWorkRecord;
			}
		}
		return $this->_records;
	}

	/**
	 * Save all records associated with this manually grouped work.
	 */
	private function saveRecords(): void {
		if (isset($this->_records) && is_array($this->_records)) {
			$recordsToInsert = [];
			foreach ($this->_records as $record) {
				// Skip records flagged for deletion by DataObjectUtil.
				if (!empty($record->_deleteOnSave) && !empty($record->id)) {
					$record->delete();
					continue;
				}

				if (!empty($record->id)) {
					continue;
				}

				if ($record->identifier_type === 'record_id') {
					// Validate the record ID exists in the system.
					require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
					$primaryCheck = new GroupedWorkPrimaryIdentifier();
					$primaryCheck->type = $record->type;
					$primaryCheck->identifier = $record->user_provided_identifier;
					if (!$primaryCheck->find(true)) {
						$this->displayMessageToUser("Record ID '{$record->user_provided_identifier}' not found for source '{$record->type}'.", true);
						continue;
					}
					$record->identifier = $record->user_provided_identifier;
				} else {
					$resolveResult = $record->resolvePrimaryIdentifier();
					if (is_array($resolveResult) && !$resolveResult['success']) {
						$this->displayMessageToUser($resolveResult['message'], true);
						continue;
					}
				}
				$recordsToInsert[] = $record;
			}

			foreach ($recordsToInsert as $record) {
				// Prepare for insert; unique constraint will skip duplicates.
				$record->manually_grouped_work_id = $this->id;
				if (empty($record->date_added)) {
					$record->date_added = time();
				}
				// Skip if this record is already in any manual group.
				$existing = new ManuallyGroupedWorkRecord();
				$existing->type = $record->type;
				$existing->identifier = $record->identifier;
				if ($existing->find(true)) {
					if ($existing->manually_grouped_work_id != $this->id) {
						$this->displayMessageToUser("Record '{$record->identifier}' is already in another manual group.", true);
					} else {
						$this->displayMessageToUser("Record '{$record->identifier}' is already in this manual group.", true);
					}
					continue;
				}

				try {
					$insertResult = $record->insert();
					if ($insertResult === false) {
						global $logger;
						$logger->log("Failed to insert record to group " . $this->id . ". Data: " . json_encode($record->toArray()), Logger::LOG_ERROR);
						$this->displayMessageToUser("Failed to add record '{$record->identifier}' from source '{$record->type}' to manual group.", true);
					}
				} catch (Exception $e) {
					global $logger;
					$logger->log("Error inserting record to group " . $this->id . ": " . $e->getMessage() . " Data: " . json_encode($record->toArray()), Logger::LOG_ERROR);
					$this->displayMessageToUser("Error inserting record to group {$this->id}: {$e->getMessage()}.", true);
				}
			}

			$this->reindexRecords();
		}
	}

	/**
	 * Clear all records associated with this manually grouped work.
	 */
	private function clearRecords(): void {
		if ($this->id) {
			$record = new ManuallyGroupedWorkRecord();
			$record->manually_grouped_work_id = $this->id;
			$record->delete(true);
		}
	}

	/**
	 * Force reindexing of all records in this manually grouped work.
	 * Adds each record's identifier and type to the record_identifiers_to_reload
	 * table to be picked up by the indexer.
	 *
	 * @return int The number of records to be reindexed.
	 */
	public function reindexRecords(): int {
		if (!isset($this->_records)) {
			$this->getRecords();
		}
		if (empty($this->_records)) {
			return 0;
		}

		$reindexedCount = 0;
		require_once ROOT_DIR . '/sys/Indexing/RecordIdentifiersToReload.php';
		foreach ($this->_records as $recordLink) {
			$identifier = $recordLink->identifier;
			$source = $recordLink->type;

			if (!empty($identifier) && !empty($source)) {
				$reindexKey = $source . '|' . $identifier;
				if (isset(self::$reindexedPermanentIds[$reindexKey])) {
					continue;
				}
				self::$reindexedPermanentIds[$reindexKey] = true;

				try {
					$recordToReload = new RecordIdentifiersToReload();
					$recordToReload->type = $source;
					$recordToReload->identifier = $identifier;
					$recordToReload->insert();
					$reindexedCount++;
				} catch (Exception $e) {
					global $logger;
					$logger->log("Error adding record to reload queue: $identifier (source: $source) for manual group ID $this->id. Error: {$e->getMessage()}.", Logger::LOG_ERROR);
				}
			} else {
				global $logger;
				$logger->log("Skipping reindex for a record in manual group ID $this->id due to missing identifier or source. Record link ID: $recordLink->id.", Logger::LOG_ERROR);
			}
		}
		return $reindexedCount;
	}

	private function displayMessageToUser(string $message, bool $isError): void {
		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$user->updateMessage .= !empty($user->updateMessage) ? "<br>{$message}" : $message;
			$user->updateMessageIsError = $isError;
			$user->update();
		}
	}

	public function getAdditionalListActions(): array {
		$actions = [];
		$permanentId = self::returnGroupedWorkPermanentId($this->grouped_work_permanent_id);
		if (!empty($permanentId)) {
			$actions[] = [
				'text' => 'View Grouped Work',
				'url' => "/GroupedWork/$permanentId",
				'target' => '_blank',
			];
		}

		return $actions;
	}

	/**
	 * Get the permanent ID of the grouped work created from this manual grouping
	 * to ensure the Grouped Work exists.
	 *
	 * @param $permId
	 * @return string|null
	 */
	public static function returnGroupedWorkPermanentId($permId): ?string {
		if (!empty($permId)) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $permId;
			if ($groupedWork->find(true)) {
				return $groupedWork->permanent_id;
			}
		}
		return null;
	}
}
