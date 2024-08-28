<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

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

	/**
	 * @param bool $updateBrowseCategories
	 * @return bool
	 */
	function insert($updateBrowseCategories = true) {
		$result = parent::insert();
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return $result;
	}

	/**
	 * @param bool $updateBrowseCategories
	 * @return bool|int|mixed
	 */
	function update($updateBrowseCategories = true) {
		$result = parent::update();
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return $result;
	}

	/**
	 * @param bool $useWhere
	 * @param bool $updateBrowseCategories
	 * @return bool|int|mixed
	 */
	function delete($useWhere = false, $updateBrowseCategories = true) : int {
		$result = parent::delete($useWhere);
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return $result;
	}

	public function getRecordDriver() {
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
		}elseif ($this->source == 'Events') {
			if (preg_match('`^communico`', $this->sourceId)){
				require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
				return new CommunicoEventRecordDriver($this->sourceId);
			} elseif (preg_match('`^libcal`', $this->sourceId)){
				require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
				return new SpringshareLibCalEventRecordDriver($this->sourceId);
			} elseif (preg_match('`^lc_`', $this->sourceId)){
				require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
				return new LibraryCalendarEventRecordDriver($this->sourceId);
			} elseif (preg_match('`^assabet`', $this->sourceId)){
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
		} else {
			return null;
		}
	}

	public function getNotes() {
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
