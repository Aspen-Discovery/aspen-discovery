<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLink.php';

class AspenLiDA_HomeScreenLinks extends ObjectEditor {
	function getObjectType(): string {
		return 'HomeScreenLink';
	}

	function getToolName(): string {
		return 'HomeScreenLinks';
	}

	function getPageTitle(): string {
		return 'Home Screen Links';
	}

	function getModule(): string {
		return 'AspenLiDA';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new HomeScreenLink();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links')) {
			// Administer Library Aspen LiDA Home Screen Links: Include the links for the home library.
			$validLibraries = Library::getLibraryList(true);
			$libraryIds = empty($validLibraries) ? [-1] : array_keys($validLibraries);
			$object->whereAdd("sharing = 'everyone'");
			$object->whereAdd("sharing = 'library' AND libraryId IN (" . implode(',' ,$libraryIds) . ')', 'OR');
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'title asc';
	}

	function getObjectStructure($context = ''): array {
		return HomeScreenLink::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return '';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.getUrlOptions(); AspenDiscovery.Admin.getDeepLinkFullPath(); AspenDiscovery.Admin.toggleHomeScreenIconTypeFields()';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/HomeScreenLinks', 'Home Screen Links');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Aspen LiDA Home Screen Links',
			'Administer Library Aspen LiDA Home Screen Links',
			'Administer Selected Aspen LiDA Home Screen Link Groups'
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Aspen LiDA Home Screen Links',
		]);
	}

	protected function getDefaultRecordsPerPage(): int {
		return 100;
	}

	protected function showQuickFilterOnPropertiesList(): bool {
		return true;
	}

	function getNumObjects(): int {
		if ($this->_numObjects == null) {
			if (!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links')) {
				/** @var DataObject $object */
				$validLibraries = Library::getLibraryList(true);
				$libraryIds = empty($validLibraries) ? [-1] : array_keys($validLibraries);
				$objectType = $this->getObjectType();
				$object = new $objectType();
				$object->whereAdd("sharing = 'everyone'");
				$object->whereAdd("sharing = 'library' AND libraryId IN (" . implode(',', $libraryIds) . ')', 'OR');
				$this->applyFilters($object);
				$this->_numObjects = $object->count();
			} elseif (UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links')) {
				/** @var DataObject $object */
				$objectType = $this->getObjectType();
				$object = new $objectType();
				$this->applyFilters($object);
				$this->_numObjects = $object->count();
			}
		}
		return $this->_numObjects;
	}

	public function hasRecordLocking(): bool {
		return true;
	}

	public function getRequiredModule(): ?string {
		return 'Aspen LiDA';
	}
}
