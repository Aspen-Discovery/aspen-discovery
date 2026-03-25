<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

class Admin_BrowseCategoryGroups extends ObjectEditor {

	function getObjectType(): string {
		return 'BrowseCategoryGroup';
	}

	function getToolName(): string {
		return 'BrowseCategoryGroups';
	}

	function getPageTitle(): string {
		return 'Browse Category Groups';
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer All Browse Categories');
	}

	function canAddNew(): bool {
		return UserAccount::userHasPermission('Administer All Browse Categories');
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new BrowseCategoryGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Browse Categories')) {
			if (UserAccount::userHasPermission('Administer Selected Browse Category Groups')) {
				//Get a list of groups the user can edit
				require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupUser.php';
				$browseCategoryGroupUser = new BrowseCategoryGroupUser();
				$browseCategoryGroupUser->userId = UserAccount::getActiveUserId();
				$allowedGroups = $browseCategoryGroupUser->fetchAll('browseCategoryGroupId');
				if (count($allowedGroups) == 0) {
					return [];
				}
				$object->whereAddIn('id', $allowedGroups, false);
			} else {
				// Administer Library Browse Categories: Include the group for the home library and any location groups.
				$browseCategoryGroups = [];
				$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
				if ($library && $library->browseCategoryGroupId > 0) {
					$browseCategoryGroups[] = $library->browseCategoryGroupId;
				}
				require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
				$locations = Location::getLocationListAsObjects(true);
				foreach ($locations as $tmpLocation) {
					if ($tmpLocation->browseCategoryGroupId > 0) {
						$browseCategoryGroups[] = $tmpLocation->browseCategoryGroupId;
					}
				}
				if (!empty($browseCategoryGroups)) {
					$object->whereAddIn('id', array_unique($browseCategoryGroups), false);
				} else {
					return [];
				}
			}
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return BrowseCategoryGroup::getObjectStructure($context);
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

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/BrowseCategoryGroups', 'Browse Category Groups');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Browse Categories',
			'Administer Library Browse Categories',
			'Administer Selected Browse Category Groups',
		];
	}

	function canBatchEdit(): bool {
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
				if (UserAccount::userHasPermission('Administer Selected Browse Category Groups')) {
					//Get a list of groups the user can edit
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupUser.php';
					$browseCategoryGroupUser = new BrowseCategoryGroupUser();
					$browseCategoryGroupUser->userId = UserAccount::getActiveUserId();
					$allowedGroups = $browseCategoryGroupUser->fetchAll('browseCategoryGroupId');

					/** @var DataObject $object */
					$objectType = $this->getObjectType();
					$object = new $objectType();
					$this->applyFilters($object);
					if (empty($allowedGroups)) {
						return 0;
					}
					$object->whereAddIn('id', $allowedGroups, false);
					$this->_numObjects = $object->count();
				} else {
					// Administer Library Browse Categories: Include home library and location groups.
					$browseCategoryGroups = [];
					$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
					if ($library && $library->browseCategoryGroupId > 0) {
						$browseCategoryGroups[] = $library->browseCategoryGroupId;
					}
					require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
					$locations = Location::getLocationListAsObjects(true);
					foreach ($locations as $tmpLocation) {
						if ($tmpLocation->browseCategoryGroupId > 0) {
							$browseCategoryGroups[] = $tmpLocation->browseCategoryGroupId;
						}
					}
					if (empty($browseCategoryGroups)) {
						$this->_numObjects = 0;
					} else {
						$objectType = $this->getObjectType();
						$object = new $objectType();
						$this->applyFilters($object);
						$object->whereAddIn('id', array_unique($browseCategoryGroups), false);
						$this->_numObjects = $object->count();
					}
				}
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

	public function canCopy() : bool {
		return $this->canAddNew();
	}

	public function hasRecordLocking() : bool {
		return true;
	}
}