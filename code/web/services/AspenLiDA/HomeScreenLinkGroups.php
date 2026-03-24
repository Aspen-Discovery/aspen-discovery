<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroup.php';

class AspenLiDA_HomeScreenLinkGroups extends ObjectEditor {
	function getObjectType(): string {
		return 'HomeScreenLinkGroup';
	}

	function getToolName(): string {
		return 'HomeScreenLinkGroups';
	}

	function getPageTitle(): string {
		return 'Home Screen Link Groups';
	}

	function getModule(): string {
		return 'AspenLiDA';
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links');
	}

	function canAddNew(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links');
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new HomeScreenLinkGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Aspen LiDA Home Screen Links')) {
			if (UserAccount::userHasPermission('Administer Home Screen Links')) {
				//Get a list of groups the user can edit
				require_once ROOT_DIR . '/sys/AspenLiDA/HomeScreenLinkGroupUser.php';
				$homeScreenLinkGroupUser = new HomeScreenLinkGroupUser();
				$homeScreenLinkGroupUser->userId = UserAccount::getActiveUserId();
				$allowedGroups = $homeScreenLinkGroupUser->fetchAll('homeScreenLinkGroupId');

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
				$homeScreenLinkGroups = [];
				$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
				if ($library && $library->lidaHomeScreenLinkGroupId > 0) {
					$homeScreenLinkGroups[] = $library->lidaHomeScreenLinkGroupId;
				}
				require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
				$locations = Location::getLocationListAsObjects(true);
				foreach ($locations as $tmpLocation) {
					if ($tmpLocation->lidaHomeScreenLinkGroupId > 0) {
						$homeScreenLinkGroups[] = $tmpLocation->lidaHomeScreenLinkGroupId;
					}
				}
				if (!empty($homeScreenLinkGroups)) {
					$object->whereAddIn('id', array_unique($homeScreenLinkGroups), false);
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
		return HomeScreenLinkGroup::getObjectStructure($context);
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

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/HomeScreenLinkGroups', 'Home Screen Link Groups');
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
				// Administer Library Aspen LiDA Home Screen Links: Include home library and location groups.
				$homeScreenLinkGroups = [];
				$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
				if ($library && $library->lidaHomeScreenLinkGroupId > 0) {
					$homeScreenLinkGroups[] = $library->lidaHomeScreenLinkGroupId;
				}
				require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
				$locations = Location::getLocationListAsObjects(true);
				foreach ($locations as $tmpLocation) {
					if ($tmpLocation->lidaHomeScreenLinkGroupId > 0) {
						$homeScreenLinkGroups[] = $tmpLocation->lidaHomeScreenLinkGroupId;
					}
				}
				if (empty($homeScreenLinkGroups)) {
					$this->_numObjects = 0;
				} else {
					$objectType = $this->getObjectType();
					$object = new $objectType();
					$this->applyFilters($object);
					$object->whereAddIn('id', array_unique($homeScreenLinkGroups), false);
					$this->_numObjects = $object->count();
				}
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

	public function canCopy(): bool {
		return $this->canAddNew();
	}

	public function hasRecordLocking(): bool {
		return true;
	}

	public function getRequiredModule(): ?string {
		return 'Aspen LiDA';
	}
}
