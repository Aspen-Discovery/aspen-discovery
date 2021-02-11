<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserListEntry extends DataObject{
	public $__table = 'user_list_entry';     // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $source;
	public $sourceId;          // int(11)  not_null multiple_key
	public $listId;                          // int(11)  multiple_key
	public $notes;                           // blob(65535)  blob
	public $dateAdded;                       // timestamp(19)  not_null unsigned zerofill binary timestamp
	public $weight;                          //Where to position the entry in the overall list

	/**
	 * @param bool $updateBrowseCategories
	 * @return bool
	 */
	function insert($updateBrowseCategories = true)
	{
		$result = parent::insert();
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return $result;
	}

	/**
	 * @param bool $updateBrowseCategories
	 * @return bool|int|mixed
	 */
	function update($updateBrowseCategories = true)
	{
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
	function delete($useWhere = false, $updateBrowseCategories = true)
	{
		$result = parent::delete($useWhere);
		global $memCache;
		$memCache->delete('user_list_data_' . UserAccount::getActiveUserId());
		return $result;
	}

	public function getRecordDriver()
	{
		if ($this->source == 'GroupedWork'){
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$recordDriver = new GroupedWorkDriver($this->sourceId);
			if (!$recordDriver->isValid()){
				return null;
			}
			return $recordDriver;
		}elseif ($this->source == 'OpenArchives'){
			require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
			$recordDriver = new OpenArchivesRecordDriver($this->sourceId);
			return $recordDriver;
		}elseif ($this->source == 'Lists'){
			require_once ROOT_DIR . '/RecordDrivers/ListsRecordDriver.php';
			$recordDriver = new ListsRecordDriver($this->sourceId);
			return $recordDriver;
		}elseif ($this->source == 'Genealogy'){
			require_once ROOT_DIR . '/RecordDrivers/PersonRecord.php';
			$recordDriver = new PersonRecord($this->sourceId);
			return $recordDriver;
		}elseif ($this->source == 'EbscoEds'){
			require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
			$recordDriver = new EbscoRecordDriver($this->sourceId);
			return $recordDriver;
		}else{
			return null;
		}
	}
}
