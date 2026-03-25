<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesCollection.php';

class OpenArchives_Collections extends ObjectEditor {
	function getObjectType(): string {
		return 'OpenArchivesCollection';
	}

	function getToolName(): string {
		return 'Collections';
	}

	function getModule(): string {
		return 'OpenArchives';
	}

	function getPageTitle(): string {
		return 'Open Archives Collections';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$list = [];

		$object = new OpenArchivesCollection();
		$object->deleted = 0;
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return OpenArchivesCollection::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/265650193/Open+Archives';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#open_archives', 'Open Archives');
		$breadcrumbs[] = new Breadcrumb('/OpenArchives/Collections', 'Collections');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'open_archives';
	}

	public function getViewPermissions() : array {
		return ['Administer Open Archives'];
	}

	public function getRequiredModule(): ?string {
		return 'Open Archives';
	}
}