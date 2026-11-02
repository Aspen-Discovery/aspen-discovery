<?php

/**
 * BorrowBox API Driver for Aspen Discovery.
 *
 * Handles all API communication with BorrowBox for patron operations
 * including checkouts, holds/reserves, returns, and renewals.
 *
 * BorrowBox uses a single JWT Bearer token (client_credentials grant) for all
 * API calls. Patrons are identified by barcode in the URL path — there is no
 * separate patron OAuth flow.
 *
 * @category Aspen Discovery
 * @package  Drivers
 */

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';

class BorrowBoxDriver extends AbstractEContentDriver {

	/** @var null|BorrowBoxSetting|false Current BorrowBox setting, null = not loaded, false = not found */
	protected null|BorrowBoxSetting|false $settings = null;

	/** @var CurlWrapper|null Reusable cURL wrapper for API requests */
	private ?CurlWrapper $apiCurlWrapper = null;

	/** @var string|null Cached JWT access token */
	private ?string $accessToken = null;

	/** @var int|null Epoch timestamp when the access token expires */
	private ?int $tokenExpiry = null;

	/**
	 * Set the active BorrowBox setting.
	 */
	public function setSettings(BorrowBoxSetting $setting): void {
		$this->settings = $setting;
		$this->accessToken = null;
		$this->tokenExpiry = null;
	}

	private function restoreSettings(null|BorrowBoxSetting|false $originalSettings): void {
		$this->settings = $originalSettings;
		$this->accessToken = null;
		$this->tokenExpiry = null;
	}

	/**
	 * Get the active BorrowBox setting, or load the first available one.
	 *
	 * @return BorrowBoxSetting|false
	 */
	public function getSettings(): BorrowBoxSetting|false {
		if ($this->settings !== null) {
			return $this->settings !== false ? $this->settings : false;
		}

		$availableSettings = $this->getAvailableSettings();
		if (empty($availableSettings)) {
			$this->settings = false;
			return false;
		}

		$this->settings = reset($availableSettings);
		return $this->settings;
	}

	/** @var BorrowBoxSetting[]|null */
	private ?array $_availableSettings = null;

	/**
	 * Get all BorrowBox settings available for the current library context.
	 *
	 * @return BorrowBoxSetting[]
	 */
	public function getAvailableSettings(): array {
		if ($this->_availableSettings !== null) {
			return $this->_availableSettings;
		}

		$this->_availableSettings = [];
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';
		require_once ROOT_DIR . '/sys/BorrowBox/LibraryBorrowBoxSettings.php';

		$activeLibrary = $this->getActiveLibrary();

		if ($activeLibrary === null) {
			return $this->_availableSettings;
		}

		$libraryBorrowBoxSettings = new LibraryBorrowBoxSettings();
		$libraryBorrowBoxSettings->libraryId = $activeLibrary->libraryId;
		if (!$libraryBorrowBoxSettings->find()) {
			return $this->_availableSettings;
		}

		while ($libraryBorrowBoxSettings->fetch()) {
			$borrowBoxSetting = new BorrowBoxSetting();
			$borrowBoxSetting->id = $libraryBorrowBoxSettings->settingId;
			if (!$borrowBoxSetting->find(true)) {
				continue;
			}
			$this->_availableSettings[$borrowBoxSetting->id] = clone $borrowBoxSetting;
		}

		return $this->_availableSettings;
	}

	private function getActiveLibrary(): ?Library {
		global $library;
		$activeLocationId = UserAccount::isLoggedIn() ? UserAccount::getUserHomeLocationId() : 0;
		if ($activeLocationId <= 0) {
			return $library;
		}
		$location = new Location();
		$location->locationId = $activeLocationId;
		if (!$location->find(true)) {
			return $library;
		}
		$homeLibrary = new Library();
		$homeLibrary->libraryId = $location->libraryId;
		if (!$homeLibrary->find(true)) {
			return $library;
		}
		return $homeLibrary;
	}

	private function setSettingsForProduct(string $productId): void {
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProduct.php';
		require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxAPIProductAvailability.php';
		$product = new BorrowBoxAPIProduct();
		$product->borrowboxId = $productId;
		if (!$product->find(true)) {
			return;
		}
		$availableSettings = $this->getAvailableSettings();
		$availability = new BorrowBoxAPIProductAvailability();
		$availability->productId = $product->id;
		$availability->find();
		while ($availability->fetch()) {
			if (isset($availableSettings[$availability->settingId])) {
				$this->setSettings($availableSettings[$availability->settingId]);
				return;
			}
		}
	}
}
