<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/CatalogFactory.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';

class Admin_StaffRegisterPatron extends Admin_Admin {
	private $_catalogDriver;

	public function launch(): void {
		global $library;

		if ($library == null || empty($library->enablePatronIlsRegistrationByStaff)) {
			$this->displayError('Staff patron registration is not enabled for this library.');
			return;
		}

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if ($catalog == null || $catalog->driver == null || !$catalog->driver->hasIlsRegistrationModeSupport(AbstractIlsDriver::ILS_REG_MODE_STAFF)) {
			$this->displayError('Staff patron registration is not supported by the active ILS.');
			return;
		}

		$this->_catalogDriver = $catalog->driver;

		$this->renderForm();
	}

	private function renderForm(?string $error = null, array $previousInput = []): void {
		global $interface;
		$structure = $this->_catalogDriver->getILSRegistrationFormStructure(AbstractIlsDriver::ILS_REG_MODE_STAFF);
		$interface->assign('formStructure', $structure);
		$interface->assign('previousInput', $previousInput);
		if ($error !== null) {
			$interface->assign('error', translate(['text' => $error, 'isAdminFacing' => true]));
		}
		$this->display('staffRegisterPatron.tpl', 'Register Patron');
	}


	private function sanitiseInput(array $raw, array $structure): array {
		$allowedKeys = $this->extractFieldKeys($structure);
		return array_intersect_key($raw, array_flip($allowedKeys));
	}

	private function extractFieldKeys(array $structure): array {
		$keys = [];
		foreach ($structure as $entryKey => $entry) {
			if (isset($entry['type']) && $entry['type'] === 'section') {
				foreach (array_keys($entry['properties'] ?? []) as $key) {
					$keys[] = $key;
				}
				continue;
			}
			$keys[] = $entryKey;
		}
		return $keys;
	}

	private function canRegisterPatronForBranch(string $branchcode): bool {
		$user = UserAccount::getActiveUserObj();
		if ($user == null) {
			return false;
		}
		if ($user->hasPermission('Register New ILS Patrons for any home library')) {
			return true;
		}

		$location = new Location();
		$location->code = $branchcode;
		if (!$location->find(true)) {
			return false;
		}

		if ($user->hasPermission('Register New ILS Patrons for patrons with same home location')
			&& $location->locationId == $user->homeLocationId) {
			return true;
		}

		if ($user->hasPermission('Register New ILS Patrons for patrons with same home library')) {
			$homeLibrary = $user->getHomeLibrary();
			return $homeLibrary != null && $location->libraryId == $homeLibrary->libraryId;
		}

		return false;
	}

	private function displayError(string $error): void {
		global $interface;
		$interface->assign('error', translate(['text' => $error, 'isAdminFacing' => true]));
		$this->display('../Admin/noPermission.tpl', 'Register Patron');
	}

	public function getActiveAdminSection(): string {
		return 'patron_management';
	}

	public function getBreadcrumbs(): array {
		return [
			new Breadcrumb('/Admin/Home', 'Administration Home'),
			new Breadcrumb('/Admin/Home#patron_management', 'Patron Management'),
			new Breadcrumb('', 'Register Patron'),
		];
	}
	
	public function canView(): bool {
		return UserAccount::userHasPermission([
			'Register New ILS Patrons for any home library',
			'Register New ILS Patrons for patrons with same home library',
			'Register New ILS Patrons for patrons with same home location',
		]);
	}
}
