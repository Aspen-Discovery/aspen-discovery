<?php
require_once(ROOT_DIR . '/sys/File/MARC.php');

/**
 * Class MarcLoader
 *
 * Loads a Marc record from the database or file system as appropriate.
 */
class MarcLoader {
	/**
	 * @param ?array $record An array of record data from Solr
	 * @return ?File_MARC_Record
	 */
	public static function loadMarcRecordFromRecord(?array $record) : ?File_MARC_Record {
		if (is_null($record)){
			return null;
		} else {
			if ($record['recordtype'] == 'marc') {
				return MarcLoader::loadMarcRecordByILSId($record['id'], $record['recordtype']);
			} else {
				return null;
			}
		}
	}

	/**
	 * @var $loadedMarcRecords File_MARC_Record[]
	 */
	private static array $loadedMarcRecords = [];

	/**
	 * @param string $id The id of the record within the ils
	 * @param string $recordType The type of the record in the system
	 * @return ?File_MARC_Record
	 */
	public static function loadMarcRecordByILSId(string $id, string $recordType = 'marc') : ?File_MARC_Record {
		if (str_contains($id, ':')) {
			$recordInfo = explode(':', $id);
			$recordType = $recordInfo[0];
			$ilsId = $recordInfo[1];
		} else {
			$ilsId = $id;
		}

		if (array_key_exists($ilsId, MarcLoader::$loadedMarcRecords)) {
			return MarcLoader::$loadedMarcRecords[$ilsId];
		}

		require_once ROOT_DIR . '/sys/Indexing/IlsRecord.php';
		$ilsRecord = IlsRecord::getIlsRecordForId($recordType, $ilsId);
		$marcRecord = null;
		if ($ilsRecord != null) {
			if (!empty($ilsRecord->sourceData)) {
				$marcRecord = new File_MARC_Record();
				if (!$marcRecord->jsonDecode($ilsRecord->sourceData)) {
					AspenError::raiseError(new AspenError('Could not load marc record for record ' . $ilsId));
				}
			}
		}
		//Make sure not to use too much memory
		global $memoryWatcher;
		if (count(MarcLoader::$loadedMarcRecords) > 50) {
			array_shift(MarcLoader::$loadedMarcRecords);
			$memoryWatcher->logMemory("Removed Cached MARC");
		}
		$memoryWatcher->logMemory("Loaded MARC for $id");
		MarcLoader::$loadedMarcRecords[$id] = $marcRecord;
		global $timer;
		$timer->logTime("Loaded MARC record by ILS ID");
		return $marcRecord;
	}

	/**
	 * @param string $id Passed as <type>:<id>
	 * @return int|false
	 */
	public static function lastModificationTimeForIlsId(string $id) : int|false {
		if (str_contains($id, ':')) {
			$recordInfo = explode(':', $id);
			$recordType = $recordInfo[0];
			$ilsId = $recordInfo[1];
		} else {
			//Try to infer the indexing profile from the module
			global $activeRecordProfile;
			if ($activeRecordProfile) {
				$recordType = $activeRecordProfile->name;
			} else {
				$recordType = 'ils';
			}
			$ilsId = $id;
		}

		require_once ROOT_DIR . '/sys/Indexing/IlsRecord.php';
		$ilsRecord = IlsRecord::getIlsRecordForId($recordType, $ilsId);
		if ($ilsRecord != null) {
			return $ilsRecord->lastModified;
		}else{
			return false;
		}
	}

	/**
	 * @param string $id Passed as <type>:<id>
	 * @return boolean
	 */
	public static function marcExistsForILSId(string $id) : bool {
		if (str_contains($id, ':')) {
			$recordInfo = explode(':', $id, 2);
			$recordType = $recordInfo[0];
			$ilsId = $recordInfo[1];
			if ($recordType == 'external_econtent') {
				$recordInfo = explode(':', $ilsId);
				$recordType = $recordInfo[0];
				$ilsId = $recordInfo[1];
			}
		} else {
			//Try to infer the indexing profile from the module
			global $activeRecordProfile;
			if ($activeRecordProfile) {
				$recordType = $activeRecordProfile->name;
			} else {
				$recordType = 'ils';
			}
			$ilsId = $id;
		}

		require_once ROOT_DIR . '/sys/Indexing/IlsRecord.php';
		$ilsRecord = IlsRecord::getIlsRecordForId($recordType, $ilsId);

		if ($ilsRecord != null) {
			$hasMarc = !empty($ilsRecord->sourceData);
		} else {
			$hasMarc = false;
		}
		return $hasMarc;
	}
}