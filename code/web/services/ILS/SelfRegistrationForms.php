<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationForm.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/CarlXSelfRegistrationForm.php';
require_once ROOT_DIR . '/sys/SelfRegistrationForms/SierraSelfRegistrationForm.php';

class ILS_SelfRegistrationForms extends ObjectEditor {
	function getObjectType(): string {
		$ils = '';
		$accountProfiles = new AccountProfile();
		$accountProfiles->find();
		while ($accountProfiles->fetch()) {
			if ($accountProfiles->ils != 'na') {
				$ils = $accountProfiles->ils;
			}
		}

		if ($ils == 'carlx') {
			return 'CarlXSelfRegistrationForm';
		} else if ($ils == 'sierra') {
			return 'SierraSelfRegistrationForm';
		} else {
			return 'SelfRegistrationForm';
		}

	}

	function getModule(): string {
		return "ILS";
	}

	function getToolName(): string {
		return 'SelfRegistrationForms';
	}

	function getPageTitle(): string {
		return 'Self Registration Forms';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$ils = '';
		$accountProfiles = new AccountProfile();
		$accountProfiles->find();
		while ($accountProfiles->fetch()) {
			if ($accountProfiles->ils != 'na') {
				$ils = $accountProfiles->ils;
			}
		}

		if ($ils == 'carlx') {
			$object = new CarlXSelfRegistrationForm();
		} elseif ($ils == 'symphony') {
			$object = new SelfRegistrationForm();
		} else if ($ils == 'sierra') {
			$object = new SierraSelfRegistrationForm();
		}

		$list = [];

		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		$ils = '';
		$accountProfiles = new AccountProfile();
		$accountProfiles->find();
		while ($accountProfiles->fetch()) {
			if ($accountProfiles->ils != 'na') {
				$ils = $accountProfiles->ils;
			}
		}

		if ($ils == 'carlx') {
			return CarlXSelfRegistrationForm::getObjectStructure($context);
		} else if ($ils == 'sierra') {
			return SierraSelfRegistrationForm::getObjectStructure($context);
		} else {
			return SelfRegistrationForm::getObjectStructure($context);
		}

	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/SelfRegistrationForms', 'Self Registration Forms');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ils_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Self Registration Forms');
	}

	function canAddNew(): bool {
		return UserAccount::userHasPermission('Administer Self Registration Forms');
	}

	function canCopy(): bool {
		return $this->canAddNew();
	}
}