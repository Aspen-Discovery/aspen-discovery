<?php /** @noinspection PhpMissingFieldTypeInspection */

class BorrowBoxAPIProductAvailability extends DataObject {
	public $__table = 'borrowbox_api_product_availability';   // table name

	public $id;
	public $productId;
	public $borrowboxId;
	public $siteId;
	public $availabilityStatus;
	public $nextAvailableDate;
	public $lastChange;
	public $settingId;

	private $_settingName;

	/** @noinspection PhpUnused */
	function getSettingName() : string {
		if (empty($this->_settingName)) {
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';
			$setting = new BorrowBoxSetting();
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
			require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';
			$setting = new BorrowBoxSetting();
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
	 * Preloads availability for an array of BorrowBox IDs.
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
		$availability = new BorrowBoxAPIProductAvailability();
		$availability->whereAddIn('borrowboxId', $identifiers, true);
		$allAvailability = $availability->fetchAll();
		foreach ($allAvailability as $avail) {
			self::$_preloadedAvailability[$avail->borrowboxId][] = $avail;
		}
	}

	/**
	 * @param string $borrowboxId The BorrowBox product ID
	 * @return array
	 */
	static function getBorrowBoxAvailabilityForId(string $borrowboxId) : array {
		if (!isset(self::$_preloadedAvailability[$borrowboxId])) {
			self::$_preloadedAvailability[$borrowboxId] = [];
			$availability = new BorrowBoxAPIProductAvailability();
			$availability->borrowboxId = $borrowboxId;
			$availability->find();
			while ($availability->fetch()) {
				self::$_preloadedAvailability[$borrowboxId][] = clone $availability;
			}
		}
		return self::$_preloadedAvailability[$borrowboxId];
	}
}
