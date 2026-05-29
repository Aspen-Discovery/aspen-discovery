<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/UserLists/LibraryUserListFacetSetting.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/UserLists/UserListFacetGroup.php';

class UserLists_UserListFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'UserListFacetGroup';
	}

	function getModule(): string {
		return 'UserLists';
	}

	function getToolName(): string {
		return 'UserListFacets';
	}

	function getPageTitle(): string {
		return 'User List Facets';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new UserListFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$hasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer User List Facet Settings')) {
			$hasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryUserListFacetSetting', 'userListFacetGroupId');
		}
		$list = [];
		if ($hasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return UserListFacetGroup::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#user_lists', 'User Lists');
		$breadcrumbs[] = new Breadcrumb('/UserLists/UserListFacets', 'User List Facets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'user_lists';
	}

	public function getViewPermissions() : array {
		return ['Administer User List Facet Settings'];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer User List Facet Settings']);
	}

	public function getRequiredModule(): ?string {
		return 'User Lists';
	}
}