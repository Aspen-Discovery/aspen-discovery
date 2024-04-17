<?php
require_once(ROOT_DIR . '/sys/File/MARC.php');

/**
 * Class MarcLoader
 *
 * Loads a Marc record from the database or file system as appropriate.
 */
class MarcLoader {
	/**
	 * @param array $record An array of record data from Solr
	 * @return File_MARC_Record
	 */
	public static function loadMarcRecordFromRecord($record) {
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
	 * @param string $ilsId The id of the record within the ils
	 * @param string $recordType The type of the record in the system
	 * @return File_MARC_Record
	 */
	private static $loadedMarcRecords = [];

	public static function loadMarcRecordByILSId($id, $recordType = 'marc') {
		global $indexingProfiles;
		global $sideLoadSettings;
		if (strpos($id, ':') !== false) {
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
		$ilsRecord = new IlsRecord();
		$ilsRecord->source = $recordType;
		$ilsRecord->ilsId = $ilsId;
		if ($ilsRecord->find(true)) {
			if (!empty($ilsRecord->sourceData)) {
				$marcRecord = new File_MARC_Record();
				if (!$marcRecord->jsonDecode($ilsRecord->sourceData)) {
					AspenError::raiseError(new AspenError('Could not load marc record for record ' . $ilsId));
				}
				$checkFileSystem = false;
			}
		}
		//Make sure not to use to much memory
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
	 * @return int
	 */
	public static function lastModificationTimeForIlsId($id) {
		global $indexingProfiles;
		global $sideLoadSettings;
		if (strpos($id, ':') !== false) {
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
		$ilsRecord = new IlsRecord();
		$ilsRecord->selectAdd();
		$ilsRecord->selectAdd('lastModified');
		$ilsRecord->source = $recordType;
		$ilsRecord->ilsId = $ilsId;
		if ($ilsRecord->find(true)) {
			return $ilsRecord->lastModified;
		}else{
			return false;
		}
	}

	/**
	 * @param string $id Passed as <type>:<id>
	 * @return boolean
	 */
	public static function marcExistsForILSId($id) {
		global $indexingProfiles;
		global $sideLoadSettings;
		if (strpos($id, ':') !== false) {
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
		$ilsRecord = new IlsRecord();
		$ilsRecord->selectAdd();
		$ilsRecord->selectAdd('id, UNCOMPRESSED_LENGTH(sourceData) as hasMarc');
		$ilsRecord->ilsId = $ilsId;
		$ilsRecord->source = $recordType;

		if ($ilsRecord->find(true)) {
			/** @noinspection PhpUndefinedFieldInspection */
			$hasMarc = $ilsRecord->hasMarc > 0;
		} else {
			$hasMarc = false;
		}
		return $hasMarc;
	}
}