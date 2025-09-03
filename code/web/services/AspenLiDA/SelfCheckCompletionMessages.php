<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckCompletionMessage.php';

class AspenLiDA_SelfCheckCompletionMessages extends ObjectEditor {
	function getObjectType(): string {
		return 'SelfCheckCompletionMessage';
	}

	function getModule(): string {
		return "AspenLiDA";
	}

	function getToolName(): string {
		return 'SelfCheckCompletionMessages';
	}

	function getPageTitle(): string {
		return 'Self Check Completion Messages';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new SelfCheckCompletionMessage();
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
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return SelfCheckCompletionMessage::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/SelfCheckCompletionMessages', 'Self Check Completion Messages');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Aspen LiDA Self-Check Settings');
	}
}