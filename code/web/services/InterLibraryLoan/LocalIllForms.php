<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/InterLibraryLoan/LocalIllForm.php';

class InterLibraryLoan_LocalIllForms extends ObjectEditor {
	function getObjectType(): string {
		return 'LocalIllForm';
	}

	function getToolName(): string {
		return 'LocalIllForms';
	}

	function getModule(): string {
		return 'InterLibraryLoan';
	}

	function getPageTitle(): string {
		return 'Local InterLibrary Loan Forms';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new LocalIllForm();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return LocalIllForm::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ill_integration', 'Interlibrary Loan');
		$breadcrumbs[] = new Breadcrumb('/InterLibraryLoan/LocalIllForms', 'Local ILL Forms');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ill_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Local ILL Forms',
			'Administer Library Local ILL Forms',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Local ILL Forms',
		]);
	}
}
