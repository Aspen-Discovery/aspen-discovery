<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/ExtraCredit.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class CommunityEngagement_ExtraCredits extends ObjectEditor {

	function getObjectType(): string {
		return 'ExtraCredit';
	}

	function getToolName(): string {
		return 'ExtraCredits';
	}

	function getModule(): string {
		return 'CommunityEngagement';
	}

	function getPageTitle(): string {
		return 'Extra Credit Activities';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new ExtraCredit();
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return ExtraCredit::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#communityEngagement', 'Community Engagement');
		$breadcrumbs[] = new Breadcrumb('/CommunityEngagement/Extra Credits', 'Extra Credit');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'communityEngagement';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer Community Engagement Module',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer Community Engagement Module',
		]);
	}
}