<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';


class CommunityEngagement_Rewards extends ObjectEditor {

	function getObjectType(): string {
		return 'Reward';
	}

	function getToolName(): string {
		return 'Rewards';
	}

	function getModule(): string {
		return 'CommunityEngagement';
	}

	function getPageTitle(): string {
		return 'Rewards';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Reward();
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
		return Reward::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#communityEngagement', 'Community Engagement');
		$breadcrumbs[] = new Breadcrumb('/CommunityEngagement/Rewards', 'Rewards');
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

	function getInitializationJs(): string {
		return 'AspenDiscovery.CommunityEngagement.updateRewardFields();';
	}

}