<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomFormSubmission.php';

class WebBuilder_CustomFormSubmissions extends ObjectEditor {
	function getObjectType(): string {
		return 'CustomFormSubmission';
	}

	function getToolName(): string {
		return 'CustomFormSubmissions';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Form Submissions';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		global $interface;
		$object = new CustomFormSubmission();
		if (isset($_REQUEST['formId'])) {
			$formId = $_REQUEST['formId'];
			$object->formId = $formId;
		} else {
			$user = UserAccount::getActiveUserObj();
			$user->updateMessage = "Please select a form to view submissions.";
			$interface->assign('updateMessage', $user->updateMessage);
			$user->update();
			header('Location: /WebBuilder/CustomForms');
		}
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	/**
	 * Get a count of the number of objects so we can paginate as needed
	 */
	function getNumObjects(): int {
		if ($this->_numObjects == null) {
			$object = new CustomFormSubmission();
			if (isset($_REQUEST['formId'])) {
				$formId = $_REQUEST['formId'];
				$object->formId = $formId;
			}
			$this->applyFilters($object);
			$this->_numObjects = $object->count();
		}
		return $this->_numObjects;
	}

	function getDefaultSort(): string {
		return 'dateSubmitted desc';
	}

	/**
	 * Library, user, and form field columns are computed and cannot be used in ORDER BY.
	 * Fall back to the default sort if the requested or previously saved sort is not a
	 * sortable database column, otherwise the query fails and the list renders empty.
	 */
	function getSort(): string {
		$sort = trim(parent::getSort());
		$sortParts = preg_split('/\s+/', $sort);
		$sortField = $sortParts[0] ?? '';
		$sortDirection = strtolower($sortParts[1] ?? 'asc');
		$sortableProperties = array_column($this->getSortableFields($this->getObjectStructure()), 'property');
		if (count($sortParts) > 2 || !in_array($sortField, $sortableProperties) || !in_array($sortDirection, ['asc', 'desc'])) {
			return $this->getDefaultSort();
		}
		return $sort;
	}

	function showReturnToList(): bool {
		return true;
	}

	function getObjectStructure($context = ''): array {
		return CustomFormSubmission::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canEdit() : bool {
		return false;
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof CustomFormSubmission && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View Form',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/Form?id=' . $existingObject->formId : $existingObject->urlAlias,
			];
			$objectActions[] = [
				'text' => 'Edit Form',
				'url' => '/WebBuilder/CustomForms?objectAction=edit&id=' . $existingObject->formId,
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		if (!empty($this->activeObject) && $this->activeObject instanceof CustomFormSubmission) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomForms?id=' . $this->activeObject->formId, 'Form');
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomFormSubmissions?formId=' . $this->activeObject->formId, 'All Form Submissions');
		}
		return $breadcrumbs;
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Custom Forms',
			'Administer Library Custom Forms',
		];
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	public function canAddNew() : bool {
		return false;
	}

	function getHiddenFields() : array {
		if (!empty($_REQUEST['formId'])) {
			return ['formId' => $_REQUEST['formId']];
		}else{
			return [];
		}
	}

	public function getRequiredModule(): ?string {
		return 'Web Builder';
	}
}