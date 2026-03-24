<?php /** @noinspection PhpMissingFieldTypeInspection */


class UserListEntry extends DataObject {
	public $__table = 'user_list_entry';     // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $source;
	public $sourceId;          // int(11)  not_null multiple_key
	public $listId;                          // int(11)  multiple_key
	public $notes;                           // blob(65535)  blob
	public $dateAdded;                       // timestamp(19)  not_null unsigned zerofill binary timestamp
	public $weight;                          //Where to position the entry in the overall list
	public $importedFrom;
	public $title;

	public function getUniquenessFields(): array {
		return [
			'listId',
			'source',
			'sourceId',
		];
	}

	public function insert(string $context = '') : int|bool {
		if($this->source == "GroupedWork") {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $this->sourceId;
			if ($groupedWork->find(true)) {
				if ($this->title == null) {
					$this->title = mb_substr($groupedWork->full_title, 0, 50);
				}
				//Force reindex so facets will update
				$groupedWork->forceReindex();
			}
		}
		$result = parent::insert();
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());

		$this->updateParentListDateUpdated();

		return $result;
	}

	/**
	 * @param string $context
	 * @return bool|int
	 */
	public function update(string $context = '') : bool|int {
		$result = parent::update();
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());

		//Force reindex so facets will update
		if ($this->source == "GroupedWork") {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $this->sourceId;
			if ($groupedWork->find(true)) {
				$groupedWork->forceReindex();
			}
		}

		$this->updateParentListDateUpdated();

		return $result;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		//Force reindex so facets will update
		if ($this->source == "GroupedWork") {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $this->sourceId;
			if ($groupedWork->find(true)) {
				$groupedWork->forceReindex();
			}
		}

		$result = parent::delete($useWhere, $hardDelete);
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());

		$this->updateParentListDateUpdated();

		return $result;
	}

	public function updateParentListDateUpdated() : void {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		if (!empty($this->listId)) {
			$parentList = new UserList();
			$parentList->id = $this->listId;
			if ($parentList->find(true)) {
				$parentList->dateUpdated = time();
				$parentList->update();
			}
		}
	}

	public function getRecordDriver() : ?RecordInterface {
		if ($this->source == 'GroupedWork') {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$recordDriver = new GroupedWorkDriver($this->sourceId);
			if (!$recordDriver->isValid()) {
				return null;
			}
			return $recordDriver;
		} elseif ($this->source == 'OpenArchives') {
			require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
			return new OpenArchivesRecordDriver($this->sourceId);
		} elseif ($this->source == 'Events') {
			if (str_starts_with($this->sourceId, 'communico')) {
				require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
				return new CommunicoEventRecordDriver($this->sourceId);
			} elseif (str_starts_with($this->sourceId, 'libcal')) {
				require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
				return new SpringshareLibCalEventRecordDriver($this->sourceId);
			} elseif (str_starts_with($this->sourceId, 'lc_')) {
				require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
				return new LibraryCalendarEventRecordDriver($this->sourceId);
			} elseif (str_starts_with($this->sourceId, 'assabet')) {
				require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
				return new AssabetEventRecordDriver($this->sourceId);
			}
		} elseif ($this->source == 'Lists') {
			require_once ROOT_DIR . '/RecordDrivers/ListsRecordDriver.php';
			$recordDriver = new ListsRecordDriver($this->sourceId);
			if ($recordDriver->isValid()) {
				return $recordDriver;
			} else {
				return null;
			}
		} elseif ($this->source == 'Genealogy') {
			require_once ROOT_DIR . '/RecordDrivers/PersonRecord.php';
			return new PersonRecord($this->sourceId);
		} elseif ($this->source == 'EbscoEds') {
			require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
			return new EbscoRecordDriver($this->sourceId);
		} elseif ($this->source == 'Ebscohost') {
			require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
			return new EbscohostRecordDriver($this->sourceId);
		} elseif ($this->source == 'Summon') {
			require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
			return new SummonRecordDriver($this->sourceId);
		} elseif ($this->source == 'CloudSource') {
			require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
			return new CloudSourceRecordDriver($this->sourceId);
		} elseif ($this->source == 'Gale') {
			require_once ROOT_DIR . '/RecordDrivers/GaleRecordDriver.php';
			return new GaleRecordDriver($this->sourceId);
		} elseif ($this->source == 'Series') {
			require_once ROOT_DIR . '/RecordDrivers/SeriesRecordDriver.php';
			return new SeriesRecordDriver($this->sourceId);
		}
		return null;
	}

	public function getNotes() : ?string {
		/** @var Library $library */
		global $library;
		require_once ROOT_DIR . '/sys/LocalEnrichment/BadWord.php';
		$badWords = new BadWord();

		//Determine if we should censor bad words or hide the comment completely.
		$censorWords = $library->getGroupedWorkDisplaySettings()->hideCommentsWithBadWords == 0;
		if ($censorWords) {
			return $badWords->censorBadWords($this->notes);
		} else {
			if ($badWords->hasBadWords($this->notes)) {
				return '';
			} else {
				return $this->notes;
			}
		}
	}
}
