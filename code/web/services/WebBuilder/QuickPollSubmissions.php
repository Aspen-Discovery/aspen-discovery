<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmission.php';

class WebBuilder_QuickPollSubmissions extends ObjectEditor {
	function getObjectType(): string {
		return 'QuickPollSubmission';
	}

	function getToolName(): string {
		return 'QuickPollSubmissions';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Quick Poll Submissions';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new QuickPollSubmission();
		if (!empty($_REQUEST['pollId'])) {
			$pollId = $_REQUEST['pollId'];
			$object->pollId = $pollId;
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

	function getDefaultSort(): string {
		return 'dateSubmitted desc';
	}

	function getObjectStructure($context = ''): array {
		return QuickPollSubmission::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canEdit(DataObject $object) {
		return false;
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof QuickPollSubmission && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View Quick Poll',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/QuickPoll?id=' . $existingObject->pollId : $existingObject->urlAlias,
			];
			$objectActions[] = [
				'text' => 'Edit Form',
				'url' => '/WebBuilder/QuickPolls?objectAction=edit&id=' . $existingObject->pollId,
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
		if (!empty($this->activeObject) && $this->activeObject instanceof QuickPollSubmission) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPolls?id=' . $this->activeObject->pollId, 'Quick Poll');
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPollSubmissions?pollId=' . $this->activeObject->pollId, 'All Quick Poll Submissions');
		}
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Quick Polls',
			'Administer Library Quick Polls',
		]);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}
}