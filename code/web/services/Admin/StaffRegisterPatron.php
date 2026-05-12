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

		if (isset($_POST['submit'])) {
			$this->handleSubmit();
			return;
		}

		$this->renderForm();
	}

	private function renderForm(?string $error = null, array $previousInput = []): void {
		global $interface;
		$structure = $this->_catalogDriver->getILSRegistrationFormStructure(AbstractIlsDriver::ILS_REG_MODE_STAFF);
		$structure = $this->applyPreviousInput($structure, $previousInput);

		$interface->assign('structure', $structure);
		$interface->assign('submitUrl', '/Admin/StaffRegisterPatron');
		$interface->assign('saveButtonText', 'Register Patron');
		$interface->assign('formLabel', 'Register Patron');
		$interface->assign('isSelfRegistration', false);
		$interface->assign('initializationJs', $this->getInitializationJs());
		$interface->assign('staffRegForm', $interface->fetch('DataObjectUtil/objectEditForm.tpl'));
		$interface->assign('categoryMeta', $this->_catalogDriver->getPatronCategoryMetadata());
		$interface->assign('childNeedsGuarantor', $this->_catalogDriver->getChildNeedsGuarantor());

		if ($error !== null) {
			$interface->assign('error', translate(['text' => $error, 'isAdminFacing' => true]));
		}
		$this->display('staffRegisterPatron.tpl', 'Register Patron');
	}

	private function applyPreviousInput(array $structure, array $previousInput): array {
		foreach ($structure as $key => &$entry) {
			if (isset($entry['type']) && $entry['type'] === 'section') {
				$entry['properties'] = $this->applyPreviousInput($entry['properties'], $previousInput);
				continue;
			}
			if (isset($previousInput[$key])) {
				$entry['default'] = $previousInput[$key];
			}
		}
		return $structure;
	}

	private function handleSubmit(): void {
		$structure = $this->_catalogDriver->getILSRegistrationFormStructure(AbstractIlsDriver::ILS_REG_MODE_STAFF);
		$input = $this->sanitiseInput($_POST, $structure);

		$branchcode = $input['borrower_branchcode'] ?? null;
		if (empty($branchcode)) {
			$this->renderForm('Home library is required.', $input);
			return;
		}

		if (!$this->canRegisterPatronForBranch($branchcode)) {
			$this->renderForm('You do not have permission to register patrons for the selected home library.', $input);
			return;
		}

		$result = $this->_catalogDriver->registerPatronToILS(AbstractIlsDriver::ILS_REG_MODE_STAFF, $input);
		if (empty($result['success'])) {
			$message = $result['message'] ?? 'Could not register the patron.';
			$this->renderForm($message, $input);
			return;
		}

		global $interface;
		$interface->assign('result', $result);
		$this->display('staffRegisterPatronResult.tpl', 'Patron Registered');
	}

	private function sanitiseInput(array $raw, array $structure): array {
		$allowed = array_intersect_key($raw, array_flip($this->extractFieldKeys($structure)));
		return $this->coerceInputTypes($allowed, $structure);
	}

	private function coerceInputTypes(array $input, array $structure): array {
		foreach ($structure as $key => $field) {
			if (($field['type'] ?? '') === 'section') {
				$input = $this->coerceInputTypes($input, $field['properties'] ?? []);
				continue;
			}
			if (array_key_exists($key, $input)) {
				$input[$key] = $this->coerceField($input[$key], $field);
			}
		}
		return $input;
	}

	private function coerceField(mixed $value, array $field): mixed {
		if (($field['type'] ?? '') === 'checkbox') {
			return empty($value) ? 0 : 1;
		}
		$value = trim((string)$value);
		if (isset($field['maxLength'])) {
			$value = mb_substr($value, 0, (int)$field['maxLength']);
		}
		return $value;
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

	public function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateStaffRegFormForCategory();';
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
