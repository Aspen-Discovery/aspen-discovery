<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';

class WebBuilder_QuickPolls extends ObjectEditor {
	function getObjectType(): string {
		return 'QuickPoll';
	}

	function getToolName(): string {
		return 'QuickPolls';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Quick Polls';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new QuickPoll();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Quick Polls')) {
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryQuickPoll', 'formId');
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

	function getObjectStructure($context = ''): array {
		return QuickPoll::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof QuickPoll && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/QuickPoll?id=' . $existingObject->id : $existingObject->urlAlias,
			];
			$objectActions[] = [
				'text' => 'View Submissions',
				'url' => '/WebBuilder/QuickPollSubmissions?pollId=' . $existingObject->id,
			];
			$objectActions[] = [
				'text' => 'View Graph',
				'url' => '/WebBuilder/QuickPollSubmissionsGraph?pollId=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPolls', 'Quick Polls');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Quick Polls',
			'Administer Library Quick Polls',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Quick Polls',
		]);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}
}