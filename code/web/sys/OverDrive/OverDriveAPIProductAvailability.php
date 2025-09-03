<?php /** @noinspection PhpMissingFieldTypeInspection */

class OverDriveAPIProductAvailability extends DataObject {
	public $__table = 'overdrive_api_product_availability';   // table name

	public $id;
	public $productId;
	public $libraryId;
	public $settingId;
	public $available;
	public $copiesOwned;
	public $copiesAvailable;
	public $numberOfHolds;
	public $shared;

	private $_libraryName;
	private $_settingName;

	/** @noinspection PhpUnused */
	function getLibraryName() : string {
		if ($this->libraryId == -1) {
			return 'Shared Digital Collection';
		} else {
			if (empty($this->_libraryName)) {
				$library = new Library();
				$library->libraryId = $this->libraryId;
				$library->find(true);
				$this->_libraryName = $library->displayName;
			}
			return $this->_libraryName;
		}
	}

	/** @noinspection PhpUnused */
	function getSettingName() : string {
		if (empty($this->_settingName)) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
			$setting = new OverDriveSetting();
			$setting->id = $this->settingId;
			if ($setting->find(true)) {
				$this->_settingName = $setting->name;
			} else {
				$this->_settingName = 'Unknown';
			}
		}
		return $this->_settingName;
	}

	/** @noinspection PhpUnused */
	function getSettingDescription() : string {
		if (empty($this->_settingName)) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
			$setting = new OverDriveSetting();
			$setting->id = $this->settingId;
			if ($setting->find(true)) {
				$this->_settingName = $setting->id . ': '  . $setting->__toString();
			} else {
				$this->_settingName = 'Unknown';
			}
		}
		return $this->_settingName;
	}

	private static $_preloadedAvailability = [];
	/**
	 * Preloads availability for an array of overdrive ids.
	 *
	 * @param array $identifiers
	 * @return void
	 */
	static function preloadAvailability(array $identifiers) : void {
		foreach ($identifiers as $identifier) {
			if (!isset(self::$_preloadedAvailability[$identifier])) {
				self::$_preloadedAvailability[$identifier] = [];
			}
		}
		global $library;
		$overDriveScopes = $library->getOverdriveScopeObjects();
		$libraryScopingId = self::getLibraryScopingId();

		if (!empty($overDriveScopes)) {
			foreach ($overDriveScopes as $overDriveScope) {
				$availability = new OverDriveAPIProductAvailability();
				$overDriveProduct = new OverDriveAPIProduct();
				$overDriveProduct->whereAddIn('overdriveId', $identifiers, true);
				$availability->joinAdd($overDriveProduct, 'INNER', 'product', 'productId', 'id');
				$availability->selectAdd();
				$availability->selectAdd('overdrive_api_product_availability.*');
				$availability->selectAdd('overdriveId');

				$availability->settingId = $overDriveScope->settingId;
				//OverDrive availability now returns correct counts for the library including shared items for each library.
				// Get the correct availability for with either the library (if available) or the shared collection
				$availability->whereAdd("libraryId = $libraryScopingId OR libraryId = -1");
				$availability->orderBy("libraryId DESC");
				$availability->find();
				while ($availability->fetch()) {
					/** @noinspection PhpUndefinedFieldInspection */
					self::$_preloadedAvailability[$availability->overdriveId][] = clone $availability;
				}
			}
		}
	}

	/**
	 * @param string $identifier The OverDrive ID (GUID)
	 * @return array
	 */
	static function getOverDriveAvailabilityForId(string $identifier) : array {
		if (!isset(self::$_preloadedAvailability[$identifier])) {
			self::$_preloadedAvailability[$identifier] = [];
			global $library;
			$overDriveScopes = $library->getOverdriveScopeObjects();
			$libraryScopingId = self::getLibraryScopingId();

			if (!empty($overDriveScopes)) {
				foreach ($overDriveScopes as $overDriveScope) {
					$availability = new OverDriveAPIProductAvailability();
					$overDriveProduct = new OverDriveAPIProduct();
					$overDriveProduct->overdriveId = $identifier;
					$availability->joinAdd($overDriveProduct, 'INNER', 'product', 'productId', 'id');
					$availability->selectAdd();
					$availability->selectAdd('overdrive_api_products.*');
					$availability->selectAdd('overdriveId');

					$availability->settingId = $overDriveScope->settingId;
					//OverDrive availability now returns correct counts for the library including shared items for each library.
					// Get the correct availability for with either the library (if available) or the shared collection
					$availability->whereAdd("libraryId = $libraryScopingId OR libraryId = -1");
					$availability->orderBy("libraryId DESC");
					if ($availability->find(true)) {
						self::$_preloadedAvailability[$identifier][] = clone $availability;
					}
				}
			}
		}
		return self::$_preloadedAvailability[$identifier];
	}

	private static function getLibraryScopingId() : int {
		//For econtent, we need to be more specific when restricting copies
		//since patrons can't use copies that are only available to other libraries.
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$activeLibrary = Library::getActiveLibrary();
		global $locationSingleton;
		$activeLocation = $locationSingleton->getActiveLocation();
		$homeLibrary = Library::getPatronHomeLibrary();

		//Load the holding label for the branch where the user is physically.
		if (!is_null($homeLibrary)) {
			return $homeLibrary->libraryId;
		} elseif (!is_null($activeLocation)) {
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->libraryId;
		} elseif (isset($activeLibrary)) {
			return $activeLibrary->libraryId;
		} elseif (!is_null($searchLocation)) {
			return $searchLocation->libraryId;
		} elseif (isset($searchLibrary)) {
			return $searchLibrary->libraryId;
		} else {
			return -1;
		}
	}
} 