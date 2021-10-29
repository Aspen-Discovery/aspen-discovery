<?php

/**
 * RecordDriverFactory Class
 *
 * This is a factory class to build record drivers for accessing metadata.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class RecordDriverFactory {
	/**
	 * initSearchObject
	 *
	 * This constructs a search object for the specified engine.
	 *
	 * @access  public
	 * @param   array|AbstractFedoraObject   $record     The fields retrieved from the Solr index.
	 * @return  RecordInterface     The record driver for handling the record.
	 */
	static function initRecordDriver($record)
	{
		global $configArray;
		global $timer;

		$path = '';

		$timer->logTime("Starting to load record driver");

		// Determine driver path based on record type:
		$driver = ucwords($record['recordtype']) . 'Record';
		$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
		// If we can't load the driver, fall back to the default, index-based one:
		if (!is_readable($path)) {
			//Try without appending Record
			$recordType = $record['recordtype'];
			$driverNameParts = explode('_', $recordType);
			$recordType = '';
			foreach ($driverNameParts as $driverPart){
				$recordType .= (ucfirst($driverPart));
			}

			$driver = $recordType . 'Driver' ;
			$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";

			// If we can't load the driver, fall back to the default, index-based one:
			if (!is_readable($path)) {

				$driver = 'IndexRecordDriver';
				$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
			}
		}

		return self::initAndReturnDriver($record, $driver, $path);
	}

	static $recordDrivers = array();
	/**
	 * @param $id
	 * @param  GroupedWork $groupedWork;
	 * @return ExternalEContentDriver|MarcRecordDriver|null|OverDriveRecordDriver
	 */
	static function initRecordDriverById($id, $groupedWork = null){
		global $configArray;
		if (isset(RecordDriverFactory::$recordDrivers[$id])){
			return RecordDriverFactory::$recordDrivers[$id];
		}
		if (strpos($id, ':') !== false){
			$recordInfo = explode(':', $id, 2);
			$recordType = $recordInfo[0];
			$recordId = $recordInfo[1];
		}else{
			$recordType = 'ils';
			$recordId = $id;
		}

		disableErrorHandler();
		if ($recordType == 'overdrive'){
			require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
			$recordDriver = new OverDriveRecordDriver($recordId, $groupedWork);
		} elseif ($recordType == 'axis360') {
			require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
			$recordDriver = new Axis360RecordDriver($recordId, $groupedWork);
		}elseif ($recordType == 'cloud_library'){
			require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
			$recordDriver = new CloudLibraryRecordDriver($recordId, $groupedWork);
		}elseif ($recordType == 'external_econtent'){
			require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';
			$recordDriver = new ExternalEContentDriver($recordId, $groupedWork);
		}elseif ($recordType == 'hoopla'){
			require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
			$recordDriver = new HooplaRecordDriver($recordId, $groupedWork);
			if (!$recordDriver->isValid()){
				global $logger;
				$logger->log("Unable to load record driver for hoopla record $recordId", Logger::LOG_WARNING);
				$recordDriver = null;
			}
		}elseif ($recordType == 'open_archives'){
			require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
			$recordDriver = new OpenArchivesRecordDriver($recordId);
		}else{
			global $indexingProfiles;
			global $sideLoadSettings;

			if (array_key_exists($recordType, $indexingProfiles)) {
				$indexingProfile = $indexingProfiles[$recordType];
				$driverName = $indexingProfile->recordDriver;
				$driverPath = ROOT_DIR . "/RecordDrivers/{$driverName}.php";
				require_once $driverPath;
				$recordDriver = new $driverName($id, $groupedWork);
			}else if (array_key_exists($recordType, $sideLoadSettings)){
				$indexingProfile = $sideLoadSettings[$recordType];
				$driverName = $indexingProfile->recordDriver;
				$driverPath = ROOT_DIR . "/RecordDrivers/{$driverName}.php";
				require_once $driverPath;
				$recordDriver = new $driverName($id, $groupedWork);
			}else{
				//Check to see if this is an object from the archive
				$driverNameParts = explode('_', $recordType);
				$normalizedRecordType = '';
				foreach ($driverNameParts as $driverPart){
					$normalizedRecordType .= (ucfirst($driverPart));
				}
				$driver = $normalizedRecordType . 'Driver' ;
				$path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";

				// If we can't load the driver, fall back to the default, index-based one:
				if (!is_readable($path)) {
					global $logger;
					$logger->log("Unknown record type " . $recordType, Logger::LOG_ERROR);
					$recordDriver = null;
				}else{
					require_once $path;
					if (class_exists($driver)) {
						disableErrorHandler();
						$obj = new $driver($id);
						if (($obj instanceof AspenError)){
							global $logger;
							$logger->log("Error loading record driver", Logger::LOG_DEBUG);
						}
						enableErrorHandler();
						return $obj;
					}
				}


			}
		}
		enableErrorHandler();
		RecordDriverFactory::$recordDrivers[$id] = $recordDriver;
		return $recordDriver;
	}

	/**
	 * @param $record
	 * @param $path
	 * @param $driver
	 * @return AspenError|RecordInterface
	 */
	public static function initAndReturnDriver($record, $driver, $path)
	{
		global $timer;
		global $logger;
		global $memoryWatcher;

		// Build the object:
		if ($path) {
			require_once $path;
			if (class_exists($driver)) {
				$timer->logTime("Error loading record driver");
				disableErrorHandler();
				/** @var RecordInterface $obj */
				$obj = new $driver($record);
				$timer->logTime("Initialized Driver");
				if (($obj instanceof AspenError)) {
					$logger->log("Error loading record driver", Logger::LOG_DEBUG);
				}
				enableErrorHandler();
				$timer->logTime('Loaded record driver for ' . $obj->getUniqueID());

				$memoryWatcher->logMemory("Created record driver for {$obj->getUniqueID()}");
				return $obj;
			}
		}

		// If we got here, something went very wrong:
		return new AspenError("Problem loading record driver: {$driver}");
	}


}