<?php

use JetBrains\PhpStorm\NoReturn;

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Administration/Role.php';
require_once ROOT_DIR . '/sys/Administration/Permission.php';
require_once ROOT_DIR . '/sys/Administration/PermissionGroup.php';
require_once ROOT_DIR . '/sys/Administration/PermissionGroupPermission.php';

class Admin_PermissionsReport extends Admin_Admin {
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
		}
		$permissions = [];
		$permission = new Permission();
		$permission->orderBy([
			'sectionName',
			'name',
		]);
		$permission->find();
		while ($permission->fetch()) {
			if (!empty($permission->requiredModule) && !array_key_exists($permission->requiredModule, $enabledModules)) {
				continue;
			}
			if (!array_key_exists($permission->sectionName, $permissions)) {
				$permissions[$permission->sectionName] = [];
			}
			$permissions[$permission->sectionName][$permission->id] = $permission->name;
		}
		$interface->assign('roles', $roles);
		$interface->assign('permissionsBySection', $permissions);

		if (isset($_REQUEST['exportToCSV'])) {
			$this->exportToCsv($roles, $permissions);
		}

		$this->display('permissionsByRole.tpl', 'Permissions By Role');

	}

	/**
	 * @param Role[] $roles
	 * @param array $permissionSections
	 * @return void
	 */
	#[NoReturn]
	function exportToCsv(array $roles, array $permissionSections) : void {
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename="PermissionsByRole.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'w');

		$header[] = '';

		foreach ($roles as $role) {
			$header[] = $role->name;
		}
		fputcsv($fp, $header);

		foreach ($permissionSections as $permissionSectionName => $permissions) {
			$row = [$permissionSectionName];
			fputcsv($fp, $row);
			foreach ($permissions as $permission) {
				$row = [$permission];
				foreach ($roles as $role) {
					$row[] = $role->hasPermission($permission) ? 'X' : '';
				}
				fputcsv($fp, $row);
			}

		}
		fclose($fp);
		exit;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Administrators', 'Administrators');
		$breadcrumbs[] = new Breadcrumb('', 'Permissions By Role');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Permissions');
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}