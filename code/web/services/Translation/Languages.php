<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Translation/Language.php';

class Translation_Languages extends ObjectEditor {
	function getObjectType(): string {
		return 'Language';
	}

	function getToolName(): string {
		return 'Languages';
	}

	function getModule(): string {
		return 'Translation';
	}

	function getPageTitle(): string {
		return 'Interface Languages';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Language();
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
		return 'displayName asc';
	}

	function getObjectStructure($context = ''): array {
		return Language::getObjectStructure($context);
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
		return 'https://help.aspendiscovery.org/help/admin/translate';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#translations', 'Languages and Translations');
		$breadcrumbs[] = new Breadcrumb('/Translation/Languages', 'Languages');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'translations';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Languages');
	}
}