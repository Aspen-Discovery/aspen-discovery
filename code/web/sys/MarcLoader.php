<?php
require_once ('File/MARC.php');
/**
 * Class MarcLoader
 *
 * Loads a Marc record from the database or file system as appropriate.
 */
class MarcLoader{
	/**
	 * @param array $record An array of record data from Solr
	 * @return File_MARC_Record
	 */
	public static function loadMarcRecordFromRecord($record){
		if ($record['recordtype'] == 'marc'){
			return MarcLoader::loadMarcRecordByILSId($record['id'], $record['recordtype']);
		}else{
			return null;
		}

	}

	/**
	 * @param string $ilsId       The id of the record within the ils
	 * @param string $recordType  The type of the record in the system
	 * @return File_MARC_Record
	 */
	private static $loadedMarcRecords = array();
	public static function loadMarcRecordByILSId($id, $recordType = 'marc'){
		global $indexingProfiles;
		$recordInfo = explode(':', $id);
		$recordType = $recordInfo[0];
		$ilsId = $recordInfo[1];

		if (array_key_exists($ilsId, MarcLoader::$loadedMarcRecords)){
			return MarcLoader::$loadedMarcRecords[$ilsId];
		}

		/** @var $indexingProfiles IndexingProfile[] */
		if (array_key_exists($recordType, $indexingProfiles)){
			$indexingProfile = $indexingProfiles[$recordType];
		}else{
			//Try to infer the indexing profile from the module
			global $activeRecordProfile;
			if ($activeRecordProfile){
				$indexingProfile = $activeRecordProfile;
			}else{
				$indexingProfile = $indexingProfiles['ils'];
			}
		}

		$shortId = str_replace('.', '', $ilsId);
		if (strlen($shortId) < 9){
			$shortId = str_pad($shortId, 9, "0", STR_PAD_LEFT);
		}
		if ($indexingProfile->createFolderFromLeadingCharacters){
			$firstChars = substr($shortId, 0, $indexingProfile->numCharsToCreateFolderFrom);
		}else{
			$firstChars = substr($shortId, 0, strlen($shortId) - $indexingProfile->numCharsToCreateFolderFrom);
		}

		$individualName = $indexingProfile->individualMarcPath . "/{$firstChars}/{$shortId}.mrc";
		$marcRecord = false;
		if (isset($indexingProfile->individualMarcPath)){
			if (file_exists($individualName)){
				$rawMarc = file_get_contents($individualName);
				$marc = new File_MARC($rawMarc, File_MARC::SOURCE_STRING);
				if (!($marcRecord = $marc->next())) {
					PEAR_Singleton::raiseError(new PEAR_Error('Could not load marc record for record ' . $shortId));
				}
			}
		}
		//Make sure not to use to much memory
		global $memoryWatcher;
		if (count(MarcLoader::$loadedMarcRecords) > 50){
			array_shift(MarcLoader::$loadedMarcRecords);
			$memoryWatcher->logMemory("Removed Cached MARC");
		}
		$memoryWatcher->logMemory("Loaded MARC for $id");
		MarcLoader::$loadedMarcRecords[$id] = $marcRecord;
		return $marcRecord;
	}

	/**
	 * @param string $id       Passed as <type>:<id>
	 * @return int
	 */
	public static function lastModificationTimeForIlsId($id){
		global $indexingProfiles;
		if (strpos($id, ':') !== false){
			$recordInfo = explode(':', $id);
			$recordType = $recordInfo[0];
			$ilsId = $recordInfo[1];
		}else{
			//Try to infer the indexing profile from the module
			/** @var IndexingProfile $activeRecordProfile */
			global $activeRecordProfile;
			if ($activeRecordProfile){
				$recordType = $activeRecordProfile->name;
			}else{
				$recordType = 'ils';
			}
			$ilsId = $id;
		}

		/** @var $indexingProfiles IndexingProfile[] */
		if (array_key_exists($recordType, $indexingProfiles)){
			$indexingProfile = $indexingProfiles[$recordType];
		}else{
			$indexingProfile = $indexingProfiles['ils'];
		}
		$shortId = str_replace('.', '', $ilsId);
		if (strlen($shortId) < 9){
			$shortId = str_pad($shortId, 9, "0", STR_PAD_LEFT);
		}
		if ($indexingProfile->createFolderFromLeadingCharacters){
			$firstChars = substr($shortId, 0, $indexingProfile->numCharsToCreateFolderFrom);
		}else{
			$firstChars = substr($shortId, 0, strlen($shortId) - $indexingProfile->numCharsToCreateFolderFrom);
		}
		$individualName = $indexingProfile->individualMarcPath . "/{$firstChars}/{$shortId}.mrc";
		if (isset($indexingProfile->individualMarcPath)){
			return filemtime($individualName);
		}else{
			return false;
		}
	}

	/**
	 * @param string $id       Passed as <type>:<id>
	 * @return boolean
	 */
	public static function marcExistsForILSId($id){
		global $indexingProfiles;
		if (strpos($id, ':') !== false){
			$recordInfo = explode(':', $id);
			$recordType = $recordInfo[0];
			$ilsId = $recordInfo[1];
		}else{
			//Try to infer the indexing profile from the module
			/** @var IndexingProfile $activeRecordProfile */
			global $activeRecordProfile;
			if ($activeRecordProfile){
				$recordType = $activeRecordProfile->name;
			}else{
				$recordType = 'ils';
			}
			$ilsId = $id;
		}

		/** @var $indexingProfiles IndexingProfile[] */
		if (array_key_exists($recordType, $indexingProfiles)){
			$indexingProfile = $indexingProfiles[$recordType];
		}else{
			$indexingProfile = $indexingProfiles['ils'];
		}
		$shortId = str_replace('.', '', $ilsId);
		if (strlen($shortId) < 9){
			$shortId = str_pad($shortId, 9, "0", STR_PAD_LEFT);
		}
		if ($indexingProfile->createFolderFromLeadingCharacters){
			$firstChars = substr($shortId, 0, $indexingProfile->numCharsToCreateFolderFrom);
		}else{
			$firstChars = substr($shortId, 0, strlen($shortId) - $indexingProfile->numCharsToCreateFolderFrom);
		}
		$individualName = $indexingProfile->individualMarcPath . "/{$firstChars}/{$shortId}.mrc";
		if (isset($indexingProfile->individualMarcPath)){
			return file_exists($individualName);
		}else{
			return false;
		}
	}

	/**
	 * @param string $hooplaId       The id of the record within Hoopla
	 * @return boolean
	 */
	public static function marcExistsForHooplaId($hooplaId){
		global $configArray;
		global $indexingProfiles;
		$indexingProfile = $indexingProfiles['hoopla'];
		if ($indexingProfile->createFolderFromLeadingCharacters){
			$firstChars = substr($hooplaId, 0, $indexingProfile->numCharsToCreateFolderFrom);
		}else{
			$firstChars = substr($hooplaId, 0, strlen($hooplaId) - $indexingProfile->numCharsToCreateFolderFrom);
		}

		$individualName = $configArray['Hoopla']['individualMarcPath'] . "/{$firstChars}/{$hooplaId}.mrc";
		if (isset($configArray['Hoopla']['individualMarcPath'])){
			return file_exists($individualName);
		}else{
			return false;
		}
	}

	public static function loadMarcRecordByHooplaId($id) {
		global $configArray;
		global $indexingProfiles;
		if (array_key_exists($id, MarcLoader::$loadedMarcRecords)){
			return MarcLoader::$loadedMarcRecords[$id];
		}
		$indexingProfile = $indexingProfiles['hoopla'];
		if ($indexingProfile->createFolderFromLeadingCharacters){
			$firstChars = substr($id, 0, $indexingProfile->numCharsToCreateFolderFrom);
		}else{
			$firstChars = substr($id, 0, strlen($id) - $indexingProfile->numCharsToCreateFolderFrom);
		}
		$individualName = $configArray['Hoopla']['individualMarcPath'] . "/{$firstChars}/{$id}.mrc";
		$marcRecord = false;
		if (isset($configArray['Hoopla']['individualMarcPath'])){
			if (file_exists($individualName)){
				//Load file contents independently to avoid too many files open issue
				$rawMarc = file_get_contents($individualName);
				$marc = new File_MARC($rawMarc, File_MARC::SOURCE_STRING);
				if (!($marcRecord = $marc->next())) {
					PEAR_Singleton::raiseError(new PEAR_Error('Could not load marc record for hoopla record ' . $id));
				}
			}
		}
		//Make sure not to use to much memory
		global $memoryWatcher;
		if (count(MarcLoader::$loadedMarcRecords) > 50){
			array_shift(MarcLoader::$loadedMarcRecords);
			$memoryWatcher->logMemory("Removed cached MARC for Hoopla");
		}
		MarcLoader::$loadedMarcRecords[$id] = $marcRecord;
		$memoryWatcher->logMemory("Loaded MARC for Hoopla record $id");
		return $marcRecord;
	}
}