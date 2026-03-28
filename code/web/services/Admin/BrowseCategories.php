<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

class Admin_BrowseCategories extends ObjectEditor {

	function getObjectType(): string {
		return 'BrowseCategory';
	}

	function getToolName(): string {
		return 'BrowseCategories';
	}

	function getPageTitle(): string {
		return 'Browse Categories';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new BrowseCategory();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Browse Categories')) {
			$validLibraries = Library::getLibraryList(true);
			$libraryIds = empty($validLibraries) ? [-1] : array_keys($validLibraries);
			$object->whereAdd("sharing = 'everyone'");
			$object->whereAdd("sharing = 'library' AND libraryId in (" . implode(',' ,$libraryIds) . ')', 'OR');
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'label asc';
	}

	function getObjectStructure($context = ''): array {
		return BrowseCategory::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/279642122/Browse+Categories';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateBrowseSearchForSource();return AspenDiscovery.Admin.updateBrowseCategoryFields();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/BrowseCategories', 'Browse Categories');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Browse Categories',
			'Administer Library Browse Categories',
			'Administer Selected Browse Category Groups'
		];
	}

	function canBatchEdit() : bool {
		return UserAccount::userHasPermission([
			'Administer All Browse Categories',
		]);
	}

	protected function getDefaultRecordsPerPage() : int {
		return 100;
	}

	protected function showQuickFilterOnPropertiesList() : bool {
		return true;
	}

	function getNumObjects(): int {
		if ($this->_numObjects == null) {
			if (!UserAccount::userHasPermission('Administer All Browse Categories')) {
				/** @var DataObject $object */
				$validLibraries = Library::getLibraryList(true);
				$libraryIds = empty($validLibraries) ? [-1] : array_keys($validLibraries);

				$objectType = $this->getObjectType();
				$object = new $objectType();
				$object->whereAdd("sharing = 'everyone'");
				$object->whereAdd("sharing = 'library' AND libraryId IN (" . implode(',' ,$libraryIds) . ')', 'OR');
				$this->applyFilters($object);
				$this->_numObjects = $object->count();
			} elseif (UserAccount::userHasPermission('Administer All Browse Categories')) {
				/** @var DataObject $object */
				$objectType = $this->getObjectType();
				$object = new $objectType();
				$this->applyFilters($object);
				$this->_numObjects = $object->count();
			}
		}
		return $this->_numObjects;
	}

	public function hasRecordLocking() : bool {
		return true;
	}
}