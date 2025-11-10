<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Administration/Role.php';
require_once ROOT_DIR . '/sys/Administration/Permission.php';
require_once ROOT_DIR . '/sys/Administration/PermissionGroup.php';
require_once ROOT_DIR . '/sys/Administration/PermissionGroupPermission.php';

class Admin_Permissions extends Admin_Admin {
	function launch(): void {
		global $interface;
		global $enabledModules;

		$roles = [];
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		/** @var Role $selectedRole */
		$selectedRole = null;
		while ($role->fetch()) {
			$roles[$role->roleId] = clone $role;
			if ($selectedRole == null) {
				$selectedRole = $roles[$role->roleId];
			}
			if (isset($_REQUEST['roleId']) && $_REQUEST['roleId'] == $role->roleId) {
				$selectedRole = $roles[$role->roleId];
			}
		}
		$interface->assign('selectedRole', $selectedRole);

		$permissionLabelsForSortingBySection = [];

		// Load definitions for mutually exclusive permission groups.
		$permissionGroups = self::loadPermissionGroups($permissionLabelsForSortingBySection);
		$interface->assign('permissionGroups', $permissionGroups);
		if (isset($_REQUEST['submit']) && $selectedRole != null) {
			if (isset($_REQUEST['permissionGroup'])) {
				foreach ($_REQUEST['permissionGroup'] as $groupKey => $selectedPermId) {
					if (isset($permissionGroups[$groupKey])) {
						// Remove any other permissions in this group.
						foreach ($permissionGroups[$groupKey]['permissions'] as $permName) {
							$permObj = new Permission();
							$permObj->name = $permName;
							if ($permObj->find(true)) {
								unset($_REQUEST['permission'][$permObj->id]);
							}
						}
						// Apply the selected permission if one was selected (i.e., not "None").
						if (!empty($selectedPermId)) {
							$_REQUEST['permission'][$selectedPermId] = 1;
						}
					}
				}
			}
			$selectedPermissions = [];
			foreach ($_REQUEST['permission'] as $permissionId => $selected) {
				if ($selected) {
					$selectedPermissions[] = $permissionId;
				}
			}
			$selectedRole->setActivePermissions($selectedPermissions);
		}
		$interface->assign('roles', $roles);
		$interface->assign('numRoles', count($roles));
		$permissions = [];
		$permission = new Permission();
		$permission->orderBy([
			'sectionName',
			'name',
		]);
		$permission->find();
		$selectedSections = [];
		while ($permission->fetch()) {
			if (!empty($permission->requiredModule) && !array_key_exists($permission->requiredModule, $enabledModules)) {
				continue;
			}
			//Determine if we should skip this permission because it's in a permission group or because it's deprecated (but not deleted)
			if ($permission->name == 'Administer ProPay') {
				continue;
			}else{
				$inGroup = false;
				foreach ($permissionGroups as $group) {
					if (array_key_exists($permission->id, $group['permissions'])) {
						$inGroup = true;
						break;
					}
				}
				if ($inGroup) {
					continue;
				}
			}

			if (!array_key_exists($permission->sectionName, $permissions)) {
				$permissions[$permission->sectionName] = [];
			}
			if (!array_key_exists($permission->sectionName, $permissionLabelsForSortingBySection)) {
				$permissionLabelsForSortingBySection[$permission->sectionName] = [];
			}
			if ($selectedRole->hasPermission($permission->name)) {
				$selectedSections[$permission->sectionName] = $permission->sectionName;
			}
			$permissions[$permission->sectionName][$permission->id] = clone $permission;
			$permissionLabelsForSortingBySection[$permission->sectionName][$permission->name] = [
				'type' => 'permission',
				'id' => $permission->id
			];
		}

		$interface->assign('permissions', $permissions);
		$interface->assign('selectedSections', $selectedSections);
		foreach ($permissionLabelsForSortingBySection as $sectionName => $permissions) {
			ksort($permissionLabelsForSortingBySection[$sectionName], SORT_NATURAL | SORT_FLAG_CASE);
		}
		ksort($permissionLabelsForSortingBySection, SORT_NATURAL | SORT_FLAG_CASE);
		$interface->assign('permissionLabelsForSortingBySection', $permissionLabelsForSortingBySection);

		$this->display('permissions.tpl', 'Permissions');

	}

	/**
	 * Loads mutually exclusive permission groups from the database.
	 * Each group contains sectionName, label, description, and a list of permission names.
	 *
	 * @return array<string,array{sectionName:string,label:string,description:string,permissions:string[]}>
	 */
	private static function loadPermissionGroups(&$permissionLabelsForSortingBySection): array {
		$groups = [];
		$groupLookup = [];

		$groupObj = new PermissionGroup();
		$groupObj->find();
		while ($groupObj->fetch()) {
			$groups[$groupObj->groupKey] = [
				'sectionName' => $groupObj->sectionName,
				'label' => $groupObj->label,
				'description' => $groupObj->description,
				'permissions' => [],
			];
			$groupLookup[$groupObj->id] = $groupObj->groupKey;
			if (!array_key_exists($groupObj->sectionName, $permissionLabelsForSortingBySection)) {
				$permissionLabelsForSortingBySection[$groupObj->sectionName] = [];
			}
			$permissionLabelsForSortingBySection[$groupObj->sectionName][$groupObj->label] = [
				'type' => 'group',
				'id' => $groupObj->groupKey
			];
		}

		$mapping = new PermissionGroupPermission();
		$mapping->find();
		while ($mapping->fetch()) {
			if (isset($groupLookup[$mapping->groupId])) {
				$groupKey = $groupLookup[$mapping->groupId];
				$permObj = new Permission();
				$permObj->id = $mapping->permissionId;
				if ($permObj->find(true)) {
					$groups[$groupKey]['permissions'][$permObj->id] = clone $permObj;
				}
			}
		}
		return $groups;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Administrators', 'Administrators');
		$breadcrumbs[] = new Breadcrumb('', 'Permissions');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Permissions');
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}