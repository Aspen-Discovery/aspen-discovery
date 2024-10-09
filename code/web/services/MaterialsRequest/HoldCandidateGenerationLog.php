<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/ObjectEditor.php');
require_once(ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidateGenerationLogEntry.php');

class MaterialsRequest_HoldCandidateGenerationLog extends ObjectEditor {

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('', 'Hold Candidate Generation Log');
		return $breadcrumbs;
	}

	function canView() : bool {
		return UserAccount::userHasPermission('View Materials Requests Reports');
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function getObjectType(): string {
		return 'MaterialsRequestHoldCandidateGenerationLogEntry';
	}

	function getToolName(): string {
		return 'HoldCandidateGenerationLog';
	}

	function getPageTitle(): string {
		return 'Hold Candidate Generation Log';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new MaterialsRequestHoldCandidateGenerationLogEntry();

		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getObjectStructure($context = ''): array {
		return MaterialsRequestHoldCandidateGenerationLogEntry::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getDefaultSort(): string {
		return 'startTime';
	}
}