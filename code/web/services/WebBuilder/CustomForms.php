<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';

class WebBuilder_CustomForms extends ObjectEditor {
	function getObjectType(): string {
		return 'CustomForm';
	}

	function getToolName(): string {
		return 'CustomForms';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Custom WebBuilder Forms';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new CustomForm();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Custom Forms')) {
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryCustomForm', 'formId');
		}
		$objectList = [];
		if ($userHasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$objectList[$object->id] = clone $object;
			}
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'title asc';
	}

	function getObjectStructure(): array {
		return CustomForm::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof CustomForm && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/Form?id=' . $existingObject->id : $existingObject->urlAlias,
			];
			$objectActions[] = [
				'text' => 'View Submissions',
				'url' => '/WebBuilder/CustomFormSubmissions?formId=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/webbuilder/customforms';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomForms', 'Custom Forms');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Custom Forms',
			'Administer Library Custom Forms',
		]);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}
}