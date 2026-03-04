<?php /** @noinspection PhpMissingFieldTypeInspection */

class Role extends DataObject {
	public $__table = 'roles';// table name
	public $__primaryKey = 'roleId';
	public $roleId;
	public $name;
	public $description;
	protected $_permissions;
	protected $_assignedFromPType;

	public function getUniquenessFields(): array {
		return ['name'];
	}

	public function getNumericColumnNames(): array {
		return ['roleId'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$permissionsList = [];
		$structure = [
			'roleId' => [
				'property' => 'roleId',
				'type' => 'label',
				'label' => 'Role Id',
				'description' => 'The unique id of the role within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'The full name of the role.',
			],
			'description' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 100,
				'description' => 'The full name of the role.',
			],

			'permissions' => [
				'property' => 'permissions',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Permissions',
				'description' => 'Define permissions for the role',
				'values' => $permissionsList,
				'forcesReindex' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	static function getLookup() : array {
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		$roleList = [];
		while ($role->fetch()) {
			$roleList[$role->roleId] = translate([
					'text' => $role->name,
					'inAttribute' => true,
					'isAdminFacing' => true,
					'isAdminEnteredData' => true,
				]) . ' - ' . translate([
					'text' => $role->description,
					'inAttribute' => true,
					'isAdminFacing' => true,
					'isAdminEnteredData' => true,
				]);
		}
		return $roleList;
	}

	function getPermissions() : array {
		if ($this->_permissions == null) {
			$this->_permissions = [];
			$loadDefaultPermissions = false;
			try {
				require_once ROOT_DIR . '/sys/Administration/Permission.php';
				require_once ROOT_DIR . '/sys/Administration/RolePermissions.php';
				$rolePermissions = new RolePermissions();
				$rolePermissions->roleId = $this->roleId;
				$rolePermissionIds = $rolePermissions->fetchAll('permissionId');
				$permission = new Permission();
				$permission->whereAddIn('id', $rolePermissionIds, false);
				$this->_permissions = $permission->fetchAll('name');
			} catch (Exception) {
				$loadDefaultPermissions = true;
			}
			//If we don't have permissions in the database, load defaults (this happens during conversion)
			if ($loadDefaultPermissions || count($this->_permissions) == 0) {
				$this->_permissions = $this->getDefaultPermissions();
			}
		}
		return $this->_permissions;
	}

	function setActivePermissions($permissions) : void {
		$this->clearOneToManyOptions('RolePermissions', 'roleId');
		foreach ($permissions as $permissionId) {
			require_once ROOT_DIR . '/sys/Administration/RolePermissions.php';
			$rolePermission = new RolePermissions();
			$rolePermission->roleId = $this->roleId;
			$rolePermission->permissionId = $permissionId;
			$rolePermission->insert();
		}
	}

	function hasPermission($permission) : bool {
		return in_array($permission, $this->getPermissions());
	}

	public function getDefaultPermissions() : array {
		return match ($this->name) {
			'opacAdmin' => [
				'Administer Account Profiles',
				'Administer All Browse Categories',
				'Administer All Collection Spotlights',
				'Administer All Grouped Work Display Settings',
				'Administer All Grouped Work Facets',
				'Administer All Layout Settings',
				'Administer All Libraries',
				'Administer All Locations',
				'Administer All Placards',
				'Administer All Themes',
				'Administer Boundless',
				'Administer Cloud Library',
				'Administer CloudSource OA',
				'Administer EBSCO EDS',
				'Administer Community Engagement Module',
				'Administer Summon',
				'Administer Gale',
				'Administer Genealogy',
				'Administer Hoopla',
				'Administer IP Addresses',
				'Administer Indexing Profiles',
				'Administer Languages',
				'Administer LibraryMarket LibraryCalendar Settings',
				'Administer List Indexing Settings',
				'Administer Modules',
				'Administer Open Archives',
				'Administer OverDrive',
				'Administer Patron Types',
				'Administer Amazon SES',
				'Administer SendGrid',
				'Administer All Side Loads',
				'Administer Springshare LibCal',
				'Administer System Variables',
				'Administer Third Party Enrichment API Keys',
				'Administer Translation Maps',
				'Administer Website Indexing Settings',
				'Administer Wikipedia Integration',
				'Block Patron Account Linking',
				'Download MARC Records',
				'Edit All Lists',
				'Force Reindexing of Records',
				'Include Lists In Search Results',
				'Import Materials Requests',
				'Manually Group and Ungroup Works',
				'Moderate User Reviews',
				'Run Database Maintenance',
				'Set Grouped Work Display Information',
				'Translate Aspen',
				'Upload Covers',
				'Upload List Covers',
				'Upload PDFs',
				'Upload Supplemental Files',
				'View Archive Authorship Claims',
				'View Archive Material Requests',
				'View Community Engagement Dashboard',
				'View Dashboards',
				'View Indexing Logs',
				'View ILS records in native OPAC',
				'View ILS records in native Staff Client',
				'View New York Times Lists',
				'View Offline Holds Report',
				'View OverDrive Test Interface',
				'View System Reports',
				'View Community Engagement Admin View',
			],
			'userAdmin' => [
				'Administer Permissions',
				'Administer Users',
			],
			'Library Admin' => [
				'Administer Home Library Locations',
				'Administer Home Library',
				'Administer Home Location',
				'Administer Library Browse Categories',
				'Administer Library Collection Spotlights',
				'Administer Library Grouped Work Display Settings',
				'Administer Library Grouped Work Facets',
				'Administer Library Layout Settings',
				'Administer Library Placards',
				'Administer Library Themes',
				'Block Patron Account Linking',
				'View New York Times Lists',
				'View Offline Holds Report',
			],
			'Library Manager' => [
				'Administer Home Library Locations',
				'Administer Home Library',
				'Administer Library Browse Categories',
				'Administer Library Collection Spotlights',
				'Block Patron Account Linking',
				'View New York Times Lists',
			],
			'Location Manager' => [
				'Administer Home Location',
				'Administer Library Browse Categories',
				'Administer Library Collection Spotlights',
				'Block Patron Account Linking',
			],
			'Translator' => [
				'Administer Languages',
				'Translate Aspen',
			],
			'Aspen Materials Requests' => [
				'Administer Materials Requests',
				'Manage Library Materials Requests',
				'View Materials Requests Reports',
			],
			'Super Cataloger' => [
				'Administer Indexing Profiles',
				'Administer Translation Maps',
				'Administer Wikipedia Integration',
				'Download MARC Records',
				'Force Reindexing of Records',
				'Manually Group and Ungroup Works',
				'Set Grouped Work Display Information',
				'Upload Covers',
				'Upload List Covers',
				'Upload PDFs',
				'Upload Supplemental Files',
				'View Dashboards',
				'View ILS records in native OPAC',
				'View ILS records in native Staff Client',
				'View Indexing Logs',
			],
			'Genealogy Contributor' => [
				'Administer Genealogy',
			],
			'Cataloging' => [
				'Administer Wikipedia Integration',
				'Download MARC Records',
				'Force Reindexing of Records',
				'Manually Group and Ungroup Works',
				'Upload Covers',
				'Upload List Covers',
				'Upload PDFs',
				'Upload Supplemental Files',
				'View ILS records in native OPAC',
				'View ILS records in native Staff Client',
				'View Indexing Logs',
			],
			'Content Editor' => [
				'Administer Library Browse Categories',
				'Administer Library Collection Spotlights',
				'Administer Library Placards',
				'View New York Times Lists',
			],
			'List Publisher' => [
				'Include Lists In Search Results',
			],
			default => [],
		};
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$links['permissions'] = $this->getPermissions();
		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		$permissions = [];
		if (array_key_exists('permissions', $jsonData)) {
			foreach ($jsonData['permissions'] as $permissionString) {
				$permission = new Permission();
				$permission->name = $permissionString;
				if ($permission->find(true)) {
					$permissions[] = $permission->id;
				}
			}
			$result = true;
		}
		$this->setActivePermissions($permissions);
		return $result;
	}

	public function setAssignedFromPType(bool $flag) : void {
		$this->_assignedFromPType = $flag;
	}

	public function isAssignedFromPType(): bool {
		if (empty($this->_assignedFromPType)) {
			return false;
		} else {
			return $this->_assignedFromPType;
		}
	}
}